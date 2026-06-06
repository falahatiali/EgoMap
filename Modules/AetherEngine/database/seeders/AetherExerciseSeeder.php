<?php

namespace Modules\AetherEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AetherEngine\Models\AetherExercise;

class AetherExerciseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->exercises() as $exercise) {
            AetherExercise::query()->updateOrCreate(
                ['slug' => $exercise['slug']],
                $exercise,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exercises(): array
    {
        return [
            ['slug' => 'barbell-squat', 'name' => 'Barbell Back Squat', 'muscle_group' => 'quads', 'equipment_required' => ['barbell'], 'difficulty' => 3, 'instructions' => 'Brace core, sit hips back, depth to parallel.', 'contraindications' => ['knee', 'lower_back'], 'alternative_slugs' => ['goblet-squat', 'leg-press']],
            ['slug' => 'goblet-squat', 'name' => 'Goblet Squat', 'muscle_group' => 'quads', 'equipment_required' => ['dumbbell', 'kettlebell'], 'difficulty' => 2, 'instructions' => 'Hold weight at chest, squat with upright torso.', 'contraindications' => ['knee'], 'alternative_slugs' => ['bodyweight-squat']],
            ['slug' => 'leg-press', 'name' => 'Leg Press', 'muscle_group' => 'quads', 'equipment_required' => ['machine'], 'difficulty' => 2, 'instructions' => 'Feet shoulder-width, control eccentric.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['goblet-squat']],
            ['slug' => 'romanian-deadlift', 'name' => 'Romanian Deadlift', 'muscle_group' => 'hamstrings', 'equipment_required' => ['barbell', 'dumbbell'], 'difficulty' => 3, 'instructions' => 'Hinge at hips, soft knee bend, feel hamstring stretch.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['hip-thrust']],
            ['slug' => 'conventional-deadlift', 'name' => 'Conventional Deadlift', 'muscle_group' => 'back', 'equipment_required' => ['barbell'], 'difficulty' => 4, 'instructions' => 'Neutral spine, push floor away, lock hips at top.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['romanian-deadlift']],
            ['slug' => 'hip-thrust', 'name' => 'Hip Thrust', 'muscle_group' => 'glutes', 'equipment_required' => ['barbell', 'bench'], 'difficulty' => 2, 'instructions' => 'Drive hips up, pause at top, squeeze glutes.', 'contraindications' => [], 'alternative_slugs' => ['glute-bridge']],
            ['slug' => 'glute-bridge', 'name' => 'Glute Bridge', 'muscle_group' => 'glutes', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Lift hips, avoid lumbar hyperextension.', 'contraindications' => [], 'alternative_slugs' => ['hip-thrust']],
            ['slug' => 'walking-lunge', 'name' => 'Walking Lunge', 'muscle_group' => 'quads', 'equipment_required' => ['dumbbell', 'bodyweight'], 'difficulty' => 2, 'instructions' => 'Long stride, knee tracks over toes.', 'contraindications' => ['knee'], 'alternative_slugs' => ['step-up']],
            ['slug' => 'step-up', 'name' => 'Step-Up', 'muscle_group' => 'quads', 'equipment_required' => ['dumbbell', 'bench'], 'difficulty' => 2, 'instructions' => 'Drive through whole foot on box.', 'contraindications' => ['knee'], 'alternative_slugs' => ['walking-lunge']],
            ['slug' => 'leg-curl', 'name' => 'Lying Leg Curl', 'muscle_group' => 'hamstrings', 'equipment_required' => ['machine'], 'difficulty' => 2, 'instructions' => 'Control tempo, avoid hip lift.', 'contraindications' => [], 'alternative_slugs' => ['romanian-deadlift']],
            ['slug' => 'calf-raise', 'name' => 'Standing Calf Raise', 'muscle_group' => 'calves', 'equipment_required' => ['machine', 'dumbbell'], 'difficulty' => 1, 'instructions' => 'Full stretch and contraction each rep.', 'contraindications' => ['ankle'], 'alternative_slugs' => []],
            ['slug' => 'bench-press', 'name' => 'Barbell Bench Press', 'muscle_group' => 'chest', 'equipment_required' => ['barbell', 'bench'], 'difficulty' => 3, 'instructions' => 'Retract scapula, controlled bar path.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['dumbbell-press', 'push-up']],
            ['slug' => 'dumbbell-press', 'name' => 'Dumbbell Bench Press', 'muscle_group' => 'chest', 'equipment_required' => ['dumbbell', 'bench'], 'difficulty' => 2, 'instructions' => 'Neutral wrist, full ROM.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['push-up']],
            ['slug' => 'incline-dumbbell-press', 'name' => 'Incline Dumbbell Press', 'muscle_group' => 'chest', 'equipment_required' => ['dumbbell', 'bench'], 'difficulty' => 2, 'instructions' => '30–45° incline, control eccentric.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['push-up']],
            ['slug' => 'push-up', 'name' => 'Push-Up', 'muscle_group' => 'chest', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Rigid plank, chest to floor.', 'contraindications' => ['wrist', 'shoulder'], 'alternative_slugs' => ['dumbbell-press']],
            ['slug' => 'cable-fly', 'name' => 'Cable Fly', 'muscle_group' => 'chest', 'equipment_required' => ['cable'], 'difficulty' => 2, 'instructions' => 'Slight elbow bend, squeeze at midline.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['push-up']],
            ['slug' => 'pull-up', 'name' => 'Pull-Up', 'muscle_group' => 'back', 'equipment_required' => ['bodyweight'], 'difficulty' => 4, 'instructions' => 'Full hang to chin over bar.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['lat-pulldown', 'inverted-row']],
            ['slug' => 'lat-pulldown', 'name' => 'Lat Pulldown', 'muscle_group' => 'back', 'equipment_required' => ['cable', 'machine'], 'difficulty' => 2, 'instructions' => 'Drive elbows down, avoid shrugging.', 'contraindications' => [], 'alternative_slugs' => ['pull-up']],
            ['slug' => 'barbell-row', 'name' => 'Barbell Row', 'muscle_group' => 'back', 'equipment_required' => ['barbell'], 'difficulty' => 3, 'instructions' => 'Hinge torso, pull to lower ribs.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['dumbbell-row']],
            ['slug' => 'dumbbell-row', 'name' => 'Single-Arm Dumbbell Row', 'muscle_group' => 'back', 'equipment_required' => ['dumbbell', 'bench'], 'difficulty' => 2, 'instructions' => 'Pull elbow to hip, pause at top.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['inverted-row']],
            ['slug' => 'inverted-row', 'name' => 'Inverted Row', 'muscle_group' => 'back', 'equipment_required' => ['bodyweight'], 'difficulty' => 2, 'instructions' => 'Body straight, pull chest to bar.', 'contraindications' => [], 'alternative_slugs' => ['lat-pulldown']],
            ['slug' => 'seated-cable-row', 'name' => 'Seated Cable Row', 'muscle_group' => 'back', 'equipment_required' => ['cable'], 'difficulty' => 2, 'instructions' => 'Neutral spine, squeeze scapulae.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['dumbbell-row']],
            ['slug' => 'overhead-press', 'name' => 'Overhead Press', 'muscle_group' => 'shoulders', 'equipment_required' => ['barbell', 'dumbbell'], 'difficulty' => 3, 'instructions' => 'Ribs down, press vertically.', 'contraindications' => ['shoulder', 'lower_back'], 'alternative_slugs' => ['lateral-raise']],
            ['slug' => 'lateral-raise', 'name' => 'Lateral Raise', 'muscle_group' => 'shoulders', 'equipment_required' => ['dumbbell'], 'difficulty' => 1, 'instructions' => 'Lead with elbows, stop at shoulder height.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['band-pull-apart']],
            ['slug' => 'face-pull', 'name' => 'Face Pull', 'muscle_group' => 'shoulders', 'equipment_required' => ['cable', 'band'], 'difficulty' => 2, 'instructions' => 'External rotate at end range.', 'contraindications' => [], 'alternative_slugs' => ['band-pull-apart']],
            ['slug' => 'band-pull-apart', 'name' => 'Band Pull-Apart', 'muscle_group' => 'shoulders', 'equipment_required' => ['band'], 'difficulty' => 1, 'instructions' => 'Pull band to chest, squeeze rear delts.', 'contraindications' => [], 'alternative_slugs' => ['face-pull']],
            ['slug' => 'barbell-curl', 'name' => 'Barbell Curl', 'muscle_group' => 'biceps', 'equipment_required' => ['barbell'], 'difficulty' => 2, 'instructions' => 'Elbows fixed, full extension.', 'contraindications' => ['wrist'], 'alternative_slugs' => ['hammer-curl']],
            ['slug' => 'hammer-curl', 'name' => 'Hammer Curl', 'muscle_group' => 'biceps', 'equipment_required' => ['dumbbell'], 'difficulty' => 1, 'instructions' => 'Neutral grip throughout.', 'contraindications' => ['wrist'], 'alternative_slugs' => ['band-curl']],
            ['slug' => 'band-curl', 'name' => 'Band Biceps Curl', 'muscle_group' => 'biceps', 'equipment_required' => ['band'], 'difficulty' => 1, 'instructions' => 'Constant tension curl.', 'contraindications' => [], 'alternative_slugs' => ['hammer-curl']],
            ['slug' => 'tricep-pushdown', 'name' => 'Cable Triceps Pushdown', 'muscle_group' => 'triceps', 'equipment_required' => ['cable'], 'difficulty' => 1, 'instructions' => 'Elbows pinned, full lockout.', 'contraindications' => ['elbow'], 'alternative_slugs' => ['diamond-push-up']],
            ['slug' => 'skull-crusher', 'name' => 'Skull Crusher', 'muscle_group' => 'triceps', 'equipment_required' => ['barbell', 'dumbbell'], 'difficulty' => 3, 'instructions' => 'Hinge at elbows only.', 'contraindications' => ['elbow', 'wrist'], 'alternative_slugs' => ['tricep-pushdown']],
            ['slug' => 'diamond-push-up', 'name' => 'Diamond Push-Up', 'muscle_group' => 'triceps', 'equipment_required' => ['bodyweight'], 'difficulty' => 2, 'instructions' => 'Hands close, elbows tucked.', 'contraindications' => ['wrist'], 'alternative_slugs' => ['tricep-pushdown']],
            ['slug' => 'plank', 'name' => 'Front Plank', 'muscle_group' => 'core', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Ribs to pelvis, breathe steadily.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['dead-bug']],
            ['slug' => 'dead-bug', 'name' => 'Dead Bug', 'muscle_group' => 'core', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Low back flat, opposite arm/leg extend.', 'contraindications' => [], 'alternative_slugs' => ['plank']],
            ['slug' => 'hanging-knee-raise', 'name' => 'Hanging Knee Raise', 'muscle_group' => 'core', 'equipment_required' => ['bodyweight'], 'difficulty' => 3, 'instructions' => 'Posterior pelvic tilt at top.', 'contraindications' => ['lower_back', 'shoulder'], 'alternative_slugs' => ['dead-bug']],
            ['slug' => 'pallof-press', 'name' => 'Pallof Press', 'muscle_group' => 'core', 'equipment_required' => ['cable', 'band'], 'difficulty' => 2, 'instructions' => 'Resist rotation, hold extension.', 'contraindications' => [], 'alternative_slugs' => ['plank']],
            ['slug' => 'burpee', 'name' => 'Burpee', 'muscle_group' => 'full_body', 'equipment_required' => ['bodyweight'], 'difficulty' => 3, 'instructions' => 'Smooth transitions, scale step-back if needed.', 'contraindications' => ['knee', 'wrist'], 'alternative_slugs' => ['mountain-climber']],
            ['slug' => 'mountain-climber', 'name' => 'Mountain Climber', 'muscle_group' => 'full_body', 'equipment_required' => ['bodyweight'], 'difficulty' => 2, 'instructions' => 'Hips level, quick controlled steps.', 'contraindications' => ['wrist'], 'alternative_slugs' => ['jumping-jack']],
            ['slug' => 'jumping-jack', 'name' => 'Jumping Jack', 'muscle_group' => 'cardio', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Land softly, steady rhythm.', 'contraindications' => ['knee', 'ankle'], 'alternative_slugs' => ['jump-rope']],
            ['slug' => 'jump-rope', 'name' => 'Jump Rope', 'muscle_group' => 'cardio', 'equipment_required' => ['cardio'], 'difficulty' => 2, 'instructions' => 'Light feet, wrists drive rotation.', 'contraindications' => ['ankle', 'knee'], 'alternative_slugs' => ['jumping-jack']],
            ['slug' => 'treadmill-run', 'name' => 'Treadmill Run', 'muscle_group' => 'cardio', 'equipment_required' => ['cardio'], 'difficulty' => 2, 'instructions' => 'Easy conversational pace for LISS.', 'contraindications' => ['knee', 'ankle'], 'alternative_slugs' => ['outdoor-run']],
            ['slug' => 'outdoor-run', 'name' => 'Outdoor Run', 'muscle_group' => 'cardio', 'equipment_required' => ['cardio'], 'difficulty' => 2, 'instructions' => 'Build duration before intensity.', 'contraindications' => ['knee', 'ankle'], 'alternative_slugs' => ['jumping-jack']],
            ['slug' => 'bike-erg', 'name' => 'Assault Bike', 'muscle_group' => 'cardio', 'equipment_required' => ['cardio', 'machine'], 'difficulty' => 3, 'instructions' => 'Intervals with full recovery.', 'contraindications' => ['knee'], 'alternative_slugs' => ['treadmill-run']],
            ['slug' => 'kettlebell-swing', 'name' => 'Kettlebell Swing', 'muscle_group' => 'full_body', 'equipment_required' => ['kettlebell'], 'difficulty' => 3, 'instructions' => 'Hip hinge power, arms relaxed.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['hip-thrust']],
            ['slug' => 'band-squat', 'name' => 'Banded Squat', 'muscle_group' => 'quads', 'equipment_required' => ['band'], 'difficulty' => 1, 'instructions' => 'Band under feet, constant tension squat.', 'contraindications' => ['knee'], 'alternative_slugs' => ['bodyweight-squat']],
            ['slug' => 'bodyweight-squat', 'name' => 'Bodyweight Squat', 'muscle_group' => 'quads', 'equipment_required' => ['bodyweight'], 'difficulty' => 1, 'instructions' => 'Feet flat, knees track toes.', 'contraindications' => ['knee'], 'alternative_slugs' => ['band-squat']],
            ['slug' => 'band-row', 'name' => 'Banded Row', 'muscle_group' => 'back', 'equipment_required' => ['band'], 'difficulty' => 1, 'instructions' => 'Anchor band, pull elbows back.', 'contraindications' => [], 'alternative_slugs' => ['inverted-row']],
            ['slug' => 'band-press', 'name' => 'Banded Chest Press', 'muscle_group' => 'chest', 'equipment_required' => ['band'], 'difficulty' => 1, 'instructions' => 'Press forward with stable core.', 'contraindications' => ['shoulder'], 'alternative_slugs' => ['push-up']],
            ['slug' => 'farmer-carry', 'name' => 'Farmer Carry', 'muscle_group' => 'full_body', 'equipment_required' => ['dumbbell', 'kettlebell'], 'difficulty' => 2, 'instructions' => 'Tall posture, steady walk.', 'contraindications' => ['lower_back'], 'alternative_slugs' => ['plank']],
            ['slug' => 'sled-push', 'name' => 'Sled Push', 'muscle_group' => 'full_body', 'equipment_required' => ['machine'], 'difficulty' => 3, 'instructions' => 'Low torso angle, drive through legs.', 'contraindications' => ['knee'], 'alternative_slugs' => ['walking-lunge']],
        ];
    }
}
