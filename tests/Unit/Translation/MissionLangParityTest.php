<?php

namespace Tests\Unit\Translation;

use Tests\TestCase;

class MissionLangParityTest extends TestCase
{
    public function test_fa_and_en_mission_files_share_the_same_keys(): void
    {
        $fa = require lang_path('fa/missions.php');
        $en = require lang_path('en/missions.php');

        $this->assertSame(
            array_keys($fa),
            array_keys($en),
            'fa/missions.php and en/missions.php must have identical keys.',
        );
    }

    public function test_nav_my_missions_is_persian_in_fa_locale(): void
    {
        $this->assertSame('مأموریت‌های من', __('nav.my_missions', locale: 'fa'));
        $this->assertNotSame('nav.my_missions', __('nav.my_missions', locale: 'fa'));
    }
}
