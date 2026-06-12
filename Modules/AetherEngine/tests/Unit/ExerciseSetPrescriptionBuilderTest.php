<?php

namespace Modules\AetherEngine\Tests\Unit;

use Modules\AetherEngine\Support\ExerciseSetPrescriptionBuilder;
use PHPUnit\Framework\TestCase;

class ExerciseSetPrescriptionBuilderTest extends TestCase
{
    public function test_builds_sets_from_rep_range(): void
    {
        $builder = new ExerciseSetPrescriptionBuilder;

        $sets = $builder->build(3, '8-12', 90);

        $this->assertCount(3, $sets);
        $this->assertSame(8, $sets[1]['target_reps_min']);
        $this->assertSame(12, $sets[1]['target_reps_max']);
        $this->assertSame(90, $sets[1]['rest_seconds']);
    }

    public function test_display_reps_formats_range(): void
    {
        $builder = new ExerciseSetPrescriptionBuilder;

        $this->assertSame('8-12', $builder->displayReps(8, 12));
        $this->assertSame('10', $builder->displayReps(10, 10));
    }
}
