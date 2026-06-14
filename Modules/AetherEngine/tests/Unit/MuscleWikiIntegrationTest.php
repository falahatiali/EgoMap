<?php

namespace Modules\AetherEngine\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\AetherEngine\Data\External\ExerciseListQuery;
use Modules\AetherEngine\Data\External\ExerciseSearchQuery;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiExerciseCatalogProvider;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiExerciseMediaProvider;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiHttpClient;
use Modules\AetherEngine\Integrations\MuscleWiki\MuscleWikiResponseMapper;
use Modules\AetherEngine\Integrations\WorkoutX\WorkoutXExerciseMediaProvider;
use Modules\AetherEngine\Integrations\WorkoutX\WorkoutXHttpClient;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogProviderRegistry;
use Modules\AetherEngine\Services\ExerciseCatalog\ExerciseCatalogService;
use Modules\AetherEngine\Services\ExerciseMedia\ExerciseMediaProviderRegistry;
use Tests\TestCase;

class MuscleWikiIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'aether.exercise_api.musclewiki.enabled' => true,
            'aether.exercise_api.musclewiki.key' => 'mw_test_key',
            'aether.exercise_api.musclewiki.base_url' => 'https://api.musclewiki.com',
            'aether.exercise_api.musclewiki.default_gender' => 'male',
            'aether.exercise_catalog.default' => 'musclewiki',
        ]);
    }

    public function test_catalog_search_maps_bare_exercise_array(): void
    {
        Http::fake([
            'api.musclewiki.com/search*' => Http::response([
                [
                    'id' => 2148,
                    'name' => 'Footwork High Half Toe',
                    'primary_muscles' => ['Glutes', 'Quads'],
                    'category' => 'Pilates',
                ],
                [
                    'id' => 2164,
                    'name' => 'Footwork Lower And Lift',
                    'primary_muscles' => ['Calves'],
                    'category' => 'Pilates',
                ],
            ]),
        ]);

        $service = $this->catalogService();
        $result = $service->search(new ExerciseSearchQuery(term: 'footwork', limit: 5));

        $this->assertSame('musclewiki', $result->source);
        $this->assertSame(2, $result->total);
        $this->assertSame(2, $result->count);
        $this->assertCount(2, $result->results);
        $this->assertSame('Footwork High Half Toe', $result->results[0]->name);
    }

    public function test_catalog_list_maps_paginated_summaries(): void
    {
        Http::fake([
            'api.musclewiki.com/exercises*' => Http::response([
                'total' => 1899,
                'limit' => 2,
                'offset' => 0,
                'count' => 2,
                'results' => [
                    ['id' => 1, 'name' => 'Barbell Curl'],
                    ['id' => 2, 'name' => 'Dumbbell Curl'],
                ],
            ]),
        ]);

        $service = $this->catalogService();
        $result = $service->list(new ExerciseListQuery(limit: 2));

        $this->assertSame(1899, $result->total);
        $this->assertSame(2, $result->count);
        $this->assertSame('Barbell Curl', $result->results[0]->name);
    }

    public function test_media_provider_resolves_exercise_detail_videos(): void
    {
        Http::fake([
            'api.musclewiki.com/search*' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Barbell Curl',
                    'videos' => [
                        [
                            'gender' => 'male',
                            'angle' => 'side',
                            'url' => 'https://api.musclewiki.com/stream/videos/branded/male-Barbell-barbell-curl-side.mp4',
                            'og_image' => 'https://api.musclewiki.com/stream/images/og_images/og-male-Barbell-barbell-curl-side.jpg',
                        ],
                        [
                            'gender' => 'male',
                            'angle' => 'front',
                            'url' => 'https://api.musclewiki.com/stream/videos/branded/male-Barbell-barbell-curl-front.mp4',
                            'og_image' => 'https://api.musclewiki.com/stream/images/og_images/og-male-Barbell-barbell-curl-front.jpg',
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = new MuscleWikiExerciseMediaProvider(
            new MuscleWikiHttpClient,
            new MuscleWikiResponseMapper,
        );

        $media = $provider->findMediaByName('Barbell Curl');

        $this->assertNotNull($media);
        $this->assertSame('musclewiki', $media->source);
        $this->assertSame('1', $media->externalId);
        $this->assertStringContainsString('barbell-curl-front.mp4', (string) $media->videoUrl);
        $this->assertStringContainsString('og-male-Barbell-barbell-curl-front.jpg', (string) $media->imageUrl);
    }

    public function test_media_registry_falls_back_to_next_provider(): void
    {
        config([
            'aether.exercise_api.musclewiki.enabled' => false,
            'aether.exercise_api.workoutx.enabled' => true,
            'aether.exercise_api.workoutx.key' => 'wx_test_key',
            'aether.exercise_api.workoutx.base_url' => 'https://api.workoutx.com/v1',
            'aether.exercise_api.workoutx.priority' => 2,
        ]);

        Http::fake([
            'api.workoutx.com/*' => Http::response([
                'data' => [[
                    'id' => 99,
                    'gif_url' => 'https://cdn.example.test/curl.gif',
                ]],
            ]),
        ]);

        $registry = new ExerciseMediaProviderRegistry([
            new MuscleWikiExerciseMediaProvider(new MuscleWikiHttpClient, new MuscleWikiResponseMapper),
            new WorkoutXExerciseMediaProvider(new WorkoutXHttpClient),
        ]);

        $media = $registry->resolveByName('Barbell Curl');

        $this->assertNotNull($media);
        $this->assertSame('workoutx', $media->source);
        $this->assertSame('https://cdn.example.test/curl.gif', $media->gifUrl);
    }

    private function catalogService(): ExerciseCatalogService
    {
        $registry = new ExerciseCatalogProviderRegistry([
            'musclewiki' => new MuscleWikiExerciseCatalogProvider(
                new MuscleWikiHttpClient,
                new MuscleWikiResponseMapper,
            ),
        ]);

        return new ExerciseCatalogService($registry);
    }
}
