<?php

namespace Tests\Unit;

use App\Support\MbtiContentCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MbtiContentCatalogTest extends TestCase
{
    #[Test]
    public function it_resolves_estj_profile_from_config(): void
    {
        $profile = MbtiContentCatalog::profile('estj', 'en');

        $this->assertNotNull($profile);
        $this->assertSame('The Executive', $profile['archetype']);
        $this->assertSame('sentinel', $profile['group']);
        $this->assertStringContainsString('natural organizer', $profile['narrative']);
    }

    #[Test]
    public function it_builds_rich_content_with_featured_people(): void
    {
        $content = MbtiContentCatalog::buildContentForType('estj', 'en');

        $this->assertNotEmpty($content['narrative']);
        $this->assertNotEmpty($content['communication_style']);
        $this->assertCount(3, $content['featured_people']);
        $this->assertSame('Judge Judy', $content['featured_people'][2]['name']);
        $this->assertSame(95, $content['featured_people'][2]['match_score']);
    }

    #[Test]
    public function it_enriches_dimensions_with_axis_labels(): void
    {
        $dimensions = MbtiContentCatalog::enrichDimensions([
            [
                'key' => 'ei',
                'left_label' => 'E',
                'right_label' => 'I',
                'preference' => 'E',
                'percent' => 60,
            ],
        ], 'en');

        $this->assertSame('Extraversion', $dimensions[0]['axis_name']);
        $this->assertStringContainsString('outer world', $dimensions[0]['axis_description']);
        $this->assertSame('Extraversion', $dimensions[0]['left_name']);
        $this->assertSame('Introversion', $dimensions[0]['right_name']);
    }

    #[Test]
    public function it_builds_persian_localized_content(): void
    {
        $content = MbtiContentCatalog::buildContentForType('estj', 'fa');

        $this->assertNotEmpty($content['tagline']);
        $this->assertNotSame(
            MbtiContentCatalog::buildContentForType('estj', 'en')['tagline'],
            $content['tagline'],
        );
    }

    #[Test]
    public function it_returns_empty_featured_people_for_unknown_type(): void
    {
        $people = MbtiContentCatalog::featuredPeople('xxxx', 'en');

        $this->assertSame([], $people);
    }

    #[Test]
    public function it_builds_translatable_outcome_content_for_seeding(): void
    {
        $content = MbtiContentCatalog::translatableOutcomeContent('estj');

        $this->assertArrayHasKey('en', $content);
        $this->assertArrayHasKey('fa', $content);
        $this->assertNotEmpty($content['en']['narrative'] ?? '');
        $this->assertNotEmpty($content['fa']['narrative'] ?? '');
        $this->assertNotEmpty($content['en']['featured_people'] ?? []);
    }
}
