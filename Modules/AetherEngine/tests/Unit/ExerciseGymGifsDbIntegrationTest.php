<?php

namespace Modules\AetherEngine\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbApi;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseCatalogProvider;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseIndex;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbExerciseMediaProvider;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbHttpClient;
use Modules\AetherEngine\Integrations\ExerciseGymGifsDb\ExerciseGymGifsDbResponseMapper;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogProviderRegistry;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogService;
use Tests\TestCase;

class ExerciseGymGifsDbIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'aether.exercise_api.exercise_gym_gifs_db.enabled' => true,
            'aether.exercise_api.exercise_gym_gifs_db.base_url' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0',
            'aether.exercise_api.exercise_gym_gifs_db.language' => 'en',
            'aether.exercise_catalog.default' => 'exercise_gym_gifs_db',
        ]);
    }

    public function test_global_index_maps_metadata(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/index.json' => Http::response([
                'name' => 'Exercise GIF API',
                'baseUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0',
                'generatedAt' => '2026-04-23T23:30:27.590Z',
                'languages' => ['en', 'es'],
                'defaultLanguage' => 'en',
                'totals' => ['en' => ['exercises' => 1323]],
                'endpoints' => ['languageRoot' => '{lang}/'],
            ]),
        ]);

        $index = $this->catalogProvider()->globalIndex();

        $this->assertNotNull($index);
        $this->assertSame('Exercise GIF API', $index->name);
        $this->assertSame(['en', 'es'], $index->languages);
        $this->assertSame(1323, $index->totals['en']['exercises']);
    }

    public function test_language_index_maps_muscles_and_endpoints(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/index.json' => Http::response([
                'name' => 'Exercise GIF API',
                'language' => 'en',
                'baseUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0',
                'generatedAt' => '2026-04-23T23:30:12.997Z',
                'totals' => ['exercises' => 1323, 'muscles' => 19],
                'endpoints' => ['search' => 'en/search.json'],
                'muscles' => [
                    ['muscle' => 'biceps', 'count' => 151, 'endpoint' => 'muscles/biceps.json'],
                ],
            ]),
        ]);

        $index = $this->catalogProvider()->languageIndex();

        $this->assertNotNull($index);
        $this->assertSame('en', $index->language);
        $this->assertSame('en/search.json', $index->endpoints['search']);
        $this->assertSame('Biceps', $index->muscles[0]->displayName);
        $this->assertSame(151, $index->muscles[0]->count);
    }

    public function test_catalog_search_uses_search_index(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/search.json' => Http::response([
                'language' => 'en',
                'count' => 2,
                'filters' => [
                    'biceps' => ['muscle' => 'biceps'],
                ],
                'items' => [
                    array_merge($this->barbellCurlExercise(), ['terms' => ['barbell', 'biceps', 'curl']]),
                    [
                        'id' => 'biceps/dumbbell-curl',
                        'slug' => 'dumbbell-curl',
                        'name' => 'Dumbbell Curl',
                        'muscle' => 'biceps',
                        'category' => 'strength',
                        'terms' => ['dumbbell', 'curl'],
                    ],
                ],
            ]),
        ]);

        $result = $this->catalogService()->search(new ExerciseSearchQuery(term: 'barbell curl', limit: 10));

        $this->assertSame(1, $result->total);
        $this->assertSame('Barbell Curl', $result->results[0]->name);
    }

    public function test_muscle_exercises_collection_maps_grouped_payload(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/muscles/hamstrings.json' => Http::response([
                'muscle' => 'hamstrings',
                'count' => 1,
                'exercises' => [
                    [
                        'id' => 'hamstrings/barbell-good-morning',
                        'slug' => 'barbell-good-morning',
                        'name' => 'Barbell Good Morning',
                        'muscle' => 'hamstrings',
                        'bodyPart' => 'legs',
                        'equipment' => 'barbell',
                        'category' => 'strength',
                        'secondaryMuscles' => [],
                        'instructions' => [],
                        'file' => 'hamstrings/barbell-good-morning.gif',
                        'gifUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/hamstrings/barbell-good-morning.gif',
                    ],
                ],
            ]),
        ]);

        $collection = $this->catalogProvider()->muscleExercises('hamstrings');

        $this->assertNotNull($collection);
        $this->assertSame('muscle', $collection->collectionType);
        $this->assertSame('hamstrings', $collection->collectionKey);
        $this->assertSame(1, $collection->count);
        $this->assertSame('Barbell Good Morning', $collection->exercises[0]->name);
    }

    public function test_equipment_exercises_collection_maps_grouped_payload(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/equipment/band.json' => Http::response([
                'equipment' => 'band',
                'count' => 1,
                'exercises' => [$this->barbellCurlExercise()],
            ]),
        ]);

        $collection = $this->catalogProvider()->equipmentExercises('band');

        $this->assertNotNull($collection);
        $this->assertSame('equipment', $collection->collectionType);
        $this->assertSame('band', $collection->collectionKey);
        $this->assertSame('Barbell Curl', $collection->exercises[0]->name);
    }

    public function test_body_parts_list_maps_named_counts(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/bodyparts.json' => Http::response([
                ['bodyPart' => 'arms', 'count' => 329, 'endpoint' => 'bodyparts/arms.json'],
                ['bodyPart' => 'back', 'count' => 204, 'endpoint' => 'bodyparts/back.json'],
                ['bodyPart' => 'chest', 'count' => 163, 'endpoint' => 'bodyparts/chest.json'],
            ]),
        ]);

        $bodyParts = $this->catalogProvider()->bodyParts();

        $this->assertCount(3, $bodyParts);
        $this->assertSame('arms', $bodyParts[0]->name);
        $this->assertSame('Arms', $bodyParts[0]->displayName);
        $this->assertSame(329, $bodyParts[0]->count);
    }

    public function test_body_part_exercises_collection_maps_grouped_payload(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/bodyparts/arms.json' => Http::response([
                'bodyPart' => 'arms',
                'count' => 329,
                'exercises' => [
                    $this->barbellCurlExercise(),
                ],
            ]),
        ]);

        $collection = $this->catalogProvider()->bodyPartExercises('arms');

        $this->assertNotNull($collection);
        $this->assertSame('bodyPart', $collection->collectionType);
        $this->assertSame('arms', $collection->collectionKey);
        $this->assertSame(329, $collection->count);
        $this->assertSame('Barbell Curl', $collection->exercises[0]->name);
    }

    public function test_categories_list_maps_named_counts(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/categories.json' => Http::response([
                ['category' => 'cardio', 'count' => 29, 'endpoint' => 'categories/cardio.json'],
                ['category' => 'strength', 'count' => 1224, 'endpoint' => 'categories/strength.json'],
                ['category' => 'stretching', 'count' => 56, 'endpoint' => 'categories/stretching.json'],
            ]),
        ]);

        $categories = $this->catalogProvider()->categories();

        $this->assertCount(3, $categories);
        $this->assertSame('cardio', $categories[0]->name);
        $this->assertSame('Cardio', $categories[0]->displayName);
        $this->assertSame(29, $categories[0]->count);
    }

    public function test_category_exercises_collection_maps_grouped_payload(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/categories/cardio.json' => Http::response([
                'category' => 'cardio',
                'count' => 29,
                'exercises' => [
                    [
                        'id' => 'cardio/burpee',
                        'slug' => 'burpee',
                        'name' => 'Burpee',
                        'muscle' => 'cardio',
                        'bodyPart' => 'cardio',
                        'equipment' => 'bodyweight',
                        'category' => 'cardio',
                        'secondaryMuscles' => ['pectorals', 'triceps'],
                        'instructions' => ['Adopt the starting position with proper body alignment.'],
                        'file' => 'cardio/burpee.gif',
                        'gifUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/cardio/burpee.gif',
                    ],
                ],
            ]),
        ]);

        $collection = $this->catalogProvider()->categoryExercises('cardio');

        $this->assertNotNull($collection);
        $this->assertSame('category', $collection->collectionType);
        $this->assertSame('cardio', $collection->collectionKey);
        $this->assertSame(29, $collection->count);
        $this->assertSame('Burpee', $collection->exercises[0]->name);
    }

    public function test_media_provider_resolves_gif_by_name_and_external_id(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/exercises.json' => Http::response([
                'count' => 1,
                'exercises' => [$this->barbellCurlExercise()],
            ]),
            '*ExerciseGymGifsDB@v1.1.0/api/en/exercises/biceps/barbell-curl.json' => Http::response($this->barbellCurlExercise()),
        ]);

        $provider = $this->mediaProvider();

        $byName = $provider->findMediaByName('Barbell Curl');

        $this->assertNotNull($byName);
        $this->assertSame('exercise_gym_gifs_db', $byName->source);
        $this->assertSame('biceps/barbell-curl', $byName->externalId);
        $this->assertStringContainsString('barbell-curl.gif', (string) $byName->gifUrl);

        $byId = $provider->findMediaByExternalId('biceps/barbell-curl');

        $this->assertNotNull($byId);
        $this->assertSame($byName->gifUrl, $byId->gifUrl);
    }

    public function test_api_builds_gif_url_from_muscle_and_slug(): void
    {
        $client = new ExerciseGymGifsDbHttpClient;

        $this->assertSame(
            'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/biceps/barbell-curl.gif',
            $client->gifUrl('biceps', 'barbell-curl'),
        );
    }

    public function test_catalog_list_supports_muscle_filter_and_pagination(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/exercises.json' => Http::response([
                'count' => 3,
                'exercises' => [
                    $this->barbellCurlExercise(),
                    [
                        'id' => 'biceps/dumbbell-curl',
                        'slug' => 'dumbbell-curl',
                        'name' => 'Dumbbell Curl',
                        'muscle' => 'biceps',
                        'bodyPart' => 'arms',
                        'equipment' => 'dumbbell',
                        'category' => 'strength',
                        'secondaryMuscles' => [],
                        'instructions' => [],
                        'file' => 'biceps/dumbbell-curl.gif',
                        'gifUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/biceps/dumbbell-curl.gif',
                    ],
                    [
                        'id' => 'quads/barbell-squat',
                        'slug' => 'barbell-squat',
                        'name' => 'Barbell Squat',
                        'muscle' => 'quads',
                        'bodyPart' => 'legs',
                        'equipment' => 'barbell',
                        'category' => 'strength',
                        'secondaryMuscles' => [],
                        'instructions' => [],
                        'file' => 'quads/barbell-squat.gif',
                        'gifUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/quads/barbell-squat.gif',
                    ],
                ],
            ]),
        ]);

        $result = $this->catalogService()->list(new ExerciseListQuery(limit: 1, offset: 0, muscles: 'biceps'));

        $this->assertSame(2, $result->total);
        $this->assertSame(1, $result->count);
        $this->assertSame('Barbell Curl', $result->results[0]->name);
    }

    public function test_catalog_find_returns_detail_with_gif_media(): void
    {
        Http::fake([
            '*ExerciseGymGifsDB@v1.1.0/api/en/exercises.json' => Http::response([
                'count' => 1,
                'exercises' => [$this->barbellCurlExercise()],
            ]),
        ]);

        $stableId = (new ExerciseGymGifsDbResponseMapper)->stableIntId('biceps/barbell-curl');
        $detail = $this->catalogService()->find($stableId);

        $this->assertNotNull($detail);
        $this->assertSame('Barbell Curl', $detail->name);
        $this->assertSame(['Biceps'], $detail->primaryMuscles);
        $this->assertStringContainsString('barbell-curl.gif', (string) $detail->media?->gifUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function barbellCurlExercise(): array
    {
        return [
            'id' => 'biceps/barbell-curl',
            'slug' => 'barbell-curl',
            'name' => 'Barbell Curl',
            'muscle' => 'biceps',
            'bodyPart' => 'arms',
            'equipment' => 'barbell',
            'category' => 'strength',
            'secondaryMuscles' => ['forearms'],
            'instructions' => [
                'Load the bar with an appropriate weight and adopt the starting position.',
                'Pre-engage the biceps before initiating the movement.',
            ],
            'file' => 'biceps/barbell-curl.gif',
            'gifUrl' => 'https://cdn.jsdelivr.net/gh/JahelCuadrado/ExerciseGymGifsDB@v1.1.0/biceps/barbell-curl.gif',
        ];
    }

    private function catalogService(): ExerciseCatalogService
    {
        return new ExerciseCatalogService(new ExerciseCatalogProviderRegistry([
            'exercise_gym_gifs_db' => $this->catalogProvider(),
        ]));
    }

    private function catalogProvider(): ExerciseGymGifsDbExerciseCatalogProvider
    {
        return new ExerciseGymGifsDbExerciseCatalogProvider(
            $this->api(),
            $this->exerciseIndex(),
            new ExerciseGymGifsDbHttpClient,
            new ExerciseGymGifsDbResponseMapper,
        );
    }

    private function api(): ExerciseGymGifsDbApi
    {
        return new ExerciseGymGifsDbApi(
            new ExerciseGymGifsDbHttpClient,
            new ExerciseGymGifsDbResponseMapper,
            $this->exerciseIndex(),
        );
    }

    private function mediaProvider(): ExerciseGymGifsDbExerciseMediaProvider
    {
        return new ExerciseGymGifsDbExerciseMediaProvider(
            $this->exerciseIndex(),
            new ExerciseGymGifsDbResponseMapper,
            new ExerciseGymGifsDbHttpClient,
        );
    }

    private function exerciseIndex(): ExerciseGymGifsDbExerciseIndex
    {
        return new ExerciseGymGifsDbExerciseIndex(
            new ExerciseGymGifsDbHttpClient,
            new ExerciseGymGifsDbResponseMapper,
        );
    }
}
