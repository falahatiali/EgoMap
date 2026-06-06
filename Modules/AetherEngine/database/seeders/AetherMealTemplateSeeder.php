<?php

namespace Modules\AetherEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AetherEngine\Models\AetherMealTemplate;

class AetherMealTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->meals() as $meal) {
            AetherMealTemplate::query()->updateOrCreate(
                ['slug' => $meal['slug']],
                $meal,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function meals(): array
    {
        return [
            ['slug' => 'oatmeal-berries', 'name' => 'Oatmeal with Berries', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegetarian', 'omnivore'], 'calories' => 380, 'protein_g' => 14, 'carbs_g' => 58, 'fat_g' => 10, 'ingredients' => ['oats', 'blueberries', 'almond milk', 'honey'], 'instructions' => 'Cook oats, top with berries and honey.', 'prep_time_minutes' => 10],
            ['slug' => 'greek-yogurt-parfait', 'name' => 'Greek Yogurt Parfait', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegetarian', 'omnivore'], 'calories' => 320, 'protein_g' => 28, 'carbs_g' => 35, 'fat_g' => 8, 'ingredients' => ['greek yogurt', 'granola', 'strawberries'], 'instructions' => 'Layer yogurt, granola, and fruit.', 'prep_time_minutes' => 5],
            ['slug' => 'egg-white-omelette', 'name' => 'Egg White Omelette', 'meal_type' => 'breakfast', 'dietary_tags' => ['omnivore', 'halal', 'kosher'], 'calories' => 280, 'protein_g' => 32, 'carbs_g' => 8, 'fat_g' => 12, 'ingredients' => ['egg whites', 'spinach', 'tomato', 'olive oil'], 'instructions' => 'Whisk eggs, cook with vegetables.', 'prep_time_minutes' => 12],
            ['slug' => 'avocado-toast', 'name' => 'Avocado Toast', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 350, 'protein_g' => 10, 'carbs_g' => 42, 'fat_g' => 16, 'ingredients' => ['whole grain bread', 'avocado', 'lemon', 'chili flakes'], 'instructions' => 'Mash avocado on toasted bread.', 'prep_time_minutes' => 8],
            ['slug' => 'protein-smoothie', 'name' => 'Protein Smoothie', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegetarian', 'omnivore'], 'calories' => 300, 'protein_g' => 30, 'carbs_g' => 32, 'fat_g' => 6, 'ingredients' => ['whey protein', 'banana', 'spinach', 'almond milk'], 'instructions' => 'Blend until smooth.', 'prep_time_minutes' => 5],
            ['slug' => 'tofu-scramble', 'name' => 'Tofu Scramble', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 310, 'protein_g' => 22, 'carbs_g' => 18, 'fat_g' => 16, 'ingredients' => ['tofu', 'turmeric', 'peppers', 'onion'], 'instructions' => 'Crumble tofu and sauté with spices.', 'prep_time_minutes' => 15],
            ['slug' => 'overnight-oats', 'name' => 'Overnight Oats', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 360, 'protein_g' => 12, 'carbs_g' => 55, 'fat_g' => 11, 'ingredients' => ['oats', 'chia seeds', 'oat milk', 'peanut butter'], 'instructions' => 'Mix and refrigerate overnight.', 'prep_time_minutes' => 5],
            ['slug' => 'chicken-quinoa-bowl', 'name' => 'Chicken Quinoa Bowl', 'meal_type' => 'lunch', 'dietary_tags' => ['omnivore', 'halal'], 'calories' => 520, 'protein_g' => 42, 'carbs_g' => 48, 'fat_g' => 16, 'ingredients' => ['chicken breast', 'quinoa', 'broccoli', 'olive oil'], 'instructions' => 'Grill chicken, serve over quinoa and veg.', 'prep_time_minutes' => 25],
            ['slug' => 'turkey-wrap', 'name' => 'Turkey Whole-Wheat Wrap', 'meal_type' => 'lunch', 'dietary_tags' => ['omnivore'], 'calories' => 450, 'protein_g' => 35, 'carbs_g' => 40, 'fat_g' => 14, 'ingredients' => ['turkey slices', 'whole wheat wrap', 'lettuce', 'hummus'], 'instructions' => 'Fill wrap with protein and greens.', 'prep_time_minutes' => 10],
            ['slug' => 'lentil-soup', 'name' => 'Lentil Vegetable Soup', 'meal_type' => 'lunch', 'dietary_tags' => ['vegan', 'vegetarian', 'halal'], 'calories' => 400, 'protein_g' => 22, 'carbs_g' => 58, 'fat_g' => 8, 'ingredients' => ['lentils', 'carrots', 'celery', 'tomato'], 'instructions' => 'Simmer lentils with vegetables 30 min.', 'prep_time_minutes' => 35],
            ['slug' => 'salmon-rice-bowl', 'name' => 'Salmon Rice Bowl', 'meal_type' => 'lunch', 'dietary_tags' => ['omnivore', 'kosher'], 'calories' => 560, 'protein_g' => 38, 'carbs_g' => 52, 'fat_g' => 20, 'ingredients' => ['salmon', 'brown rice', 'edamame', 'soy sauce'], 'instructions' => 'Bake salmon, serve with rice and edamame.', 'prep_time_minutes' => 30],
            ['slug' => 'tuna-salad', 'name' => 'Tuna Salad Plate', 'meal_type' => 'lunch', 'dietary_tags' => ['omnivore'], 'calories' => 420, 'protein_g' => 40, 'carbs_g' => 28, 'fat_g' => 16, 'ingredients' => ['tuna', 'mixed greens', 'cucumber', 'olive oil'], 'instructions' => 'Combine tuna with salad vegetables.', 'prep_time_minutes' => 12],
            ['slug' => 'chickpea-bowl', 'name' => 'Chickpea Power Bowl', 'meal_type' => 'lunch', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 480, 'protein_g' => 20, 'carbs_g' => 62, 'fat_g' => 16, 'ingredients' => ['chickpeas', 'brown rice', 'kale', 'tahini'], 'instructions' => 'Roast chickpeas, assemble bowl.', 'prep_time_minutes' => 20],
            ['slug' => 'beef-stir-fry', 'name' => 'Lean Beef Stir-Fry', 'meal_type' => 'lunch', 'dietary_tags' => ['omnivore', 'halal'], 'calories' => 510, 'protein_g' => 36, 'carbs_g' => 45, 'fat_g' => 18, 'ingredients' => ['lean beef', 'bell peppers', 'jasmine rice', 'ginger'], 'instructions' => 'Stir-fry beef and vegetables quickly.', 'prep_time_minutes' => 20],
            ['slug' => 'grilled-chicken-salad', 'name' => 'Grilled Chicken Salad', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore', 'halal', 'kosher'], 'calories' => 430, 'protein_g' => 40, 'carbs_g' => 22, 'fat_g' => 18, 'ingredients' => ['chicken breast', 'mixed greens', 'cherry tomatoes', 'balsamic'], 'instructions' => 'Grill chicken, toss with salad.', 'prep_time_minutes' => 18],
            ['slug' => 'baked-cod-veg', 'name' => 'Baked Cod with Vegetables', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore', 'kosher'], 'calories' => 390, 'protein_g' => 34, 'carbs_g' => 28, 'fat_g' => 12, 'ingredients' => ['cod', 'zucchini', 'lemon', 'herbs'], 'instructions' => 'Bake cod with vegetables at 200°C.', 'prep_time_minutes' => 25],
            ['slug' => 'turkey-meatballs-pasta', 'name' => 'Turkey Meatballs with Pasta', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore'], 'calories' => 540, 'protein_g' => 38, 'carbs_g' => 58, 'fat_g' => 14, 'ingredients' => ['ground turkey', 'whole wheat pasta', 'marinara', 'basil'], 'instructions' => 'Bake meatballs, toss with pasta.', 'prep_time_minutes' => 30],
            ['slug' => 'tofu-curry', 'name' => 'Tofu Coconut Curry', 'meal_type' => 'dinner', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 470, 'protein_g' => 24, 'carbs_g' => 48, 'fat_g' => 20, 'ingredients' => ['tofu', 'coconut milk', 'curry paste', 'rice'], 'instructions' => 'Simmer tofu in curry sauce.', 'prep_time_minutes' => 25],
            ['slug' => 'steak-sweet-potato', 'name' => 'Steak with Sweet Potato', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore', 'halal'], 'calories' => 580, 'protein_g' => 42, 'carbs_g' => 46, 'fat_g' => 22, 'ingredients' => ['sirloin steak', 'sweet potato', 'asparagus', 'butter'], 'instructions' => 'Pan-sear steak, roast sweet potato.', 'prep_time_minutes' => 35],
            ['slug' => 'shrimp-zoodles', 'name' => 'Shrimp Zucchini Noodles', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore', 'kosher'], 'calories' => 360, 'protein_g' => 32, 'carbs_g' => 18, 'fat_g' => 16, 'ingredients' => ['shrimp', 'zucchini', 'garlic', 'olive oil'], 'instructions' => 'Sauté shrimp with spiralized zucchini.', 'prep_time_minutes' => 15],
            ['slug' => 'stuffed-peppers', 'name' => 'Stuffed Bell Peppers', 'meal_type' => 'dinner', 'dietary_tags' => ['omnivore', 'halal'], 'calories' => 440, 'protein_g' => 30, 'carbs_g' => 40, 'fat_g' => 16, 'ingredients' => ['bell peppers', 'ground chicken', 'brown rice', 'tomato sauce'], 'instructions' => 'Stuff peppers and bake 25 min.', 'prep_time_minutes' => 40],
            ['slug' => 'protein-shake', 'name' => 'Post-Workout Protein Shake', 'meal_type' => 'snack', 'dietary_tags' => ['vegetarian', 'omnivore'], 'calories' => 220, 'protein_g' => 30, 'carbs_g' => 18, 'fat_g' => 4, 'ingredients' => ['whey protein', 'water', 'banana'], 'instructions' => 'Shake with cold water.', 'prep_time_minutes' => 3],
            ['slug' => 'apple-almond-butter', 'name' => 'Apple with Almond Butter', 'meal_type' => 'snack', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 250, 'protein_g' => 6, 'carbs_g' => 28, 'fat_g' => 14, 'ingredients' => ['apple', 'almond butter'], 'instructions' => 'Slice apple, serve with almond butter.', 'prep_time_minutes' => 3],
            ['slug' => 'cottage-cheese-fruit', 'name' => 'Cottage Cheese & Fruit', 'meal_type' => 'snack', 'dietary_tags' => ['vegetarian', 'omnivore'], 'calories' => 210, 'protein_g' => 24, 'carbs_g' => 20, 'fat_g' => 4, 'ingredients' => ['cottage cheese', 'pineapple'], 'instructions' => 'Combine and serve chilled.', 'prep_time_minutes' => 3],
            ['slug' => 'hummus-veggies', 'name' => 'Hummus with Veggie Sticks', 'meal_type' => 'snack', 'dietary_tags' => ['vegan', 'vegetarian', 'halal'], 'calories' => 200, 'protein_g' => 8, 'carbs_g' => 22, 'fat_g' => 10, 'ingredients' => ['hummus', 'carrots', 'celery'], 'instructions' => 'Serve hummus with raw vegetables.', 'prep_time_minutes' => 5],
            ['slug' => 'trail-mix', 'name' => 'Homemade Trail Mix', 'meal_type' => 'snack', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 280, 'protein_g' => 9, 'carbs_g' => 24, 'fat_g' => 18, 'ingredients' => ['almonds', 'walnuts', 'dried cranberries'], 'instructions' => 'Portion into single serving.', 'prep_time_minutes' => 2],
            ['slug' => 'rice-cakes-turkey', 'name' => 'Rice Cakes with Turkey', 'meal_type' => 'snack', 'dietary_tags' => ['omnivore'], 'calories' => 190, 'protein_g' => 18, 'carbs_g' => 20, 'fat_g' => 4, 'ingredients' => ['rice cakes', 'turkey slices'], 'instructions' => 'Top rice cakes with turkey.', 'prep_time_minutes' => 3],
            ['slug' => 'edamame-bowl', 'name' => 'Steamed Edamame', 'meal_type' => 'snack', 'dietary_tags' => ['vegan', 'vegetarian'], 'calories' => 180, 'protein_g' => 16, 'carbs_g' => 14, 'fat_g' => 8, 'ingredients' => ['edamame', 'sea salt'], 'instructions' => 'Steam edamame 5 minutes.', 'prep_time_minutes' => 6],
            ['slug' => 'falafel-plate', 'name' => 'Falafel Plate', 'meal_type' => 'lunch', 'dietary_tags' => ['vegan', 'vegetarian', 'halal'], 'calories' => 490, 'protein_g' => 18, 'carbs_g' => 58, 'fat_g' => 20, 'ingredients' => ['falafel', 'tahini', 'tabbouleh', 'pita'], 'instructions' => 'Serve falafel with sides.', 'prep_time_minutes' => 15],
            ['slug' => 'shakshuka', 'name' => 'Shakshuka', 'meal_type' => 'breakfast', 'dietary_tags' => ['vegetarian', 'halal', 'kosher'], 'calories' => 340, 'protein_g' => 18, 'carbs_g' => 26, 'fat_g' => 18, 'ingredients' => ['eggs', 'tomato sauce', 'peppers', 'onion'], 'instructions' => 'Poach eggs in spiced tomato base.', 'prep_time_minutes' => 20],
        ];
    }
}
