<?php

namespace Modules\VirtueEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VirtueEngine\Enums\VirtueHabitCategory;
use Modules\VirtueEngine\Models\VirtueHabit;

class VirtueHabitSeeder extends Seeder
{
    public function run(): void
    {
        $habits = [
            // Communication
            [
                'slug' => 'sarcasm-and-taunts',
                'name' => 'Sarcasm & Taunts',
                'category' => VirtueHabitCategory::Communication,
                'description' => 'Using sarcasm, taunts, or indirect jabs instead of expressing feelings directly.',
                'ai_root_cause' => 'Sarcasm often comes from an inability to express hurt or frustration directly, combined with a fear of appearing vulnerable.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Pause before speaking', 'daily_practice' => 'When you feel sarcasm rising, take one slow breath and ask: "What am I actually feeling right now?"'],
                    ['order' => 2, 'action' => 'State feelings directly', 'daily_practice' => 'Replace "Yeah, great job…" with "I felt left out when…" once per day.'],
                    ['order' => 3, 'action' => 'Evening review', 'daily_practice' => 'Each night, note one moment you communicated honestly instead of sarcastically.'],
                ],
                'ai_affirmation' => 'I speak my truth with courage and kindness.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'interrupting-others',
                'name' => 'Interrupting Others',
                'category' => VirtueHabitCategory::Communication,
                'description' => 'Cutting people off mid-sentence or finishing their thoughts for them.',
                'ai_root_cause' => 'Interrupting usually signals excitement or anxiety about being heard, not a lack of respect — but it communicates the opposite.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Count to three', 'daily_practice' => 'After someone pauses, silently count "1-2-3" before speaking.'],
                    ['order' => 2, 'action' => 'Mirror back', 'daily_practice' => 'Repeat the last thing the person said before adding your thought.'],
                    ['order' => 3, 'action' => 'Daily reflection', 'daily_practice' => 'Ask yourself each evening: "Did I let everyone finish today?"'],
                ],
                'ai_affirmation' => 'I listen fully before I speak.',
                'sort_order' => 20,
            ],

            // Emotional
            [
                'slug' => 'quick-anger',
                'name' => 'Quick to Anger',
                'category' => VirtueHabitCategory::Emotional,
                'description' => 'Reacting with anger or frustration over minor things.',
                'ai_root_cause' => 'Quick anger often masks deeper feelings of helplessness, injustice, or unmet expectations — the outburst is faster than the feeling.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'The 5-second rule', 'daily_practice' => 'When you feel heat rising, physically step back or look away for 5 seconds.'],
                    ['order' => 2, 'action' => 'Name it to tame it', 'daily_practice' => 'Say internally: "I feel frustrated because…" — naming an emotion reduces its intensity.'],
                    ['order' => 3, 'action' => 'Post-anger journal', 'daily_practice' => 'Write one line about each anger episode: trigger → reaction → what I wish I had done.'],
                ],
                'ai_affirmation' => 'I choose my response; I am not my reaction.',
                'sort_order' => 30,
            ],
            [
                'slug' => 'sulking-and-silent-treatment',
                'name' => 'Sulking & Silent Treatment',
                'category' => VirtueHabitCategory::Emotional,
                'description' => 'Going quiet and withdrawing instead of discussing the problem.',
                'ai_root_cause' => 'Silence as punishment usually stems from feeling unheard or fearing conflict will make things worse.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Set a time limit', 'daily_practice' => 'Allow yourself 30 minutes of quiet to process, then commit to a short conversation.'],
                    ['order' => 2, 'action' => 'Use a starter phrase', 'daily_practice' => 'Begin with: "I need to tell you something but I find it hard — can we talk?"'],
                    ['order' => 3, 'action' => 'Recognise the pattern', 'daily_practice' => 'Notice each time you go quiet and ask: "Is this solving anything?"'],
                ],
                'ai_affirmation' => 'I face discomfort with words, not walls.',
                'sort_order' => 40,
            ],

            // Social
            [
                'slug' => 'gossiping',
                'name' => 'Gossiping',
                'category' => VirtueHabitCategory::Social,
                'description' => 'Talking about others\' private matters or sharing judgements behind their backs.',
                'ai_root_cause' => 'Gossip often provides a temporary sense of connection and superiority, but erodes trust over time.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Ask yourself', 'daily_practice' => 'Before sharing something about someone, ask: "Would I say this to their face?"'],
                    ['order' => 2, 'action' => 'Redirect', 'daily_practice' => 'When gossip starts, change subject with: "Actually, how are you doing lately?"'],
                    ['order' => 3, 'action' => 'Gratitude flip', 'daily_practice' => 'For every negative thing you notice about someone, find one genuine positive.'],
                ],
                'ai_affirmation' => 'I build people up; I do not tear them down.',
                'sort_order' => 50,
            ],
            [
                'slug' => 'judging-too-quickly',
                'name' => 'Judging Too Quickly',
                'category' => VirtueHabitCategory::Social,
                'description' => 'Forming strong opinions about people based on first impressions or limited information.',
                'ai_root_cause' => 'Quick judgement is our brain\'s shortcut to safety — but in social settings it creates walls instead of bridges.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Add a question', 'daily_practice' => 'When a negative thought forms about someone, add "I wonder why they…?" to soften it.'],
                    ['order' => 2, 'action' => 'Find one similarity', 'daily_practice' => 'With each new person, find one thing you have in common.'],
                    ['order' => 3, 'action' => 'Weekly audit', 'daily_practice' => 'Review one snap judgement from the week — was it fair?'],
                ],
                'ai_affirmation' => 'I see the whole person, not just the surface.',
                'sort_order' => 60,
            ],

            // Internal
            [
                'slug' => 'negative-self-talk',
                'name' => 'Negative Self-Talk',
                'category' => VirtueHabitCategory::Internal,
                'description' => 'Repeatedly criticising yourself internally: "I\'m stupid", "I always mess up."',
                'ai_root_cause' => 'Persistent negative self-talk is often a protective mechanism absorbed in early life — the inner critic tries to pre-empt others\' criticism.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Catch and name it', 'daily_practice' => 'The moment you hear the inner critic, say "There is that voice again" — don\'t argue, just notice.'],
                    ['order' => 2, 'action' => 'Reframe once', 'daily_practice' => 'Replace "I always mess up" with "I made a mistake, and I can learn from this."'],
                    ['order' => 3, 'action' => 'Evening wins list', 'daily_practice' => 'Write three things you did well today — big or small.'],
                ],
                'ai_affirmation' => 'I speak to myself with the kindness I deserve.',
                'sort_order' => 70,
            ],
            [
                'slug' => 'comparing-to-others',
                'name' => 'Comparing Yourself to Others',
                'category' => VirtueHabitCategory::Internal,
                'description' => 'Constantly measuring your worth, success, or appearance against other people.',
                'ai_root_cause' => 'Comparison is our brain\'s attempt to calibrate "Am I okay?" — but comparing highlights gaps, not strengths.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Notice the trigger', 'daily_practice' => 'When comparison arises (often on social media), close the app and write one thing you are proud of.'],
                    ['order' => 2, 'action' => 'Compare to yesterday', 'daily_practice' => 'Replace "vs. others" with "vs. my past self" — am I better than last month?'],
                    ['order' => 3, 'action' => 'Celebrate others genuinely', 'daily_practice' => 'When someone succeeds, send one genuine "well done" — it rewires competition into connection.'],
                ],
                'ai_affirmation' => 'My only competition is who I was yesterday.',
                'sort_order' => 80,
            ],

            // Procrastination
            [
                'slug' => 'procrastinating-on-important-tasks',
                'name' => 'Procrastinating on Important Tasks',
                'category' => VirtueHabitCategory::Procrastination,
                'description' => 'Delaying meaningful work until the last moment, then rushing or not finishing.',
                'ai_root_cause' => 'Procrastination is usually about emotion, not time — fear of failure, perfectionism, or the task feeling overwhelming.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Two-minute rule', 'daily_practice' => 'If a task takes less than 2 minutes, do it now. For bigger ones, start with 2 minutes only.'],
                    ['order' => 2, 'action' => 'Shrink the task', 'daily_practice' => 'Break any avoided task into the next single physical action (not "write report" but "open doc").'],
                    ['order' => 3, 'action' => 'Schedule the hard thing first', 'daily_practice' => 'Put the task you dread most as item #1 in the morning, before any distractions.'],
                ],
                'ai_affirmation' => 'I start before I am ready and grow as I go.',
                'sort_order' => 90,
            ],
            [
                'slug' => 'phone-distraction',
                'name' => 'Phone Distraction',
                'category' => VirtueHabitCategory::Procrastination,
                'description' => 'Reaching for the phone during work, conversations, or important moments.',
                'ai_root_cause' => 'Phone use hijacks dopamine — every notification is a micro-reward that trains the brain to prefer stimulation over sustained effort.',
                'ai_steps' => [
                    ['order' => 1, 'action' => 'Physical distance', 'daily_practice' => 'During focused work, place phone in another room or face-down out of reach.'],
                    ['order' => 2, 'action' => 'Phone-free zones', 'daily_practice' => 'Designate one place (dining table, bedroom) as a phone-free zone, no exceptions.'],
                    ['order' => 3, 'action' => 'Batch checking', 'daily_practice' => 'Check social media only at 3 fixed times daily — not continuously.'],
                ],
                'ai_affirmation' => 'I am present where I am; my phone waits.',
                'sort_order' => 100,
            ],
        ];

        foreach ($habits as $habit) {
            VirtueHabit::query()->updateOrCreate(
                ['slug' => $habit['slug']],
                [
                    'name' => $habit['name'],
                    'category' => $habit['category'],
                    'description' => $habit['description'],
                    'ai_root_cause' => $habit['ai_root_cause'],
                    'ai_steps' => $habit['ai_steps'],
                    'ai_affirmation' => $habit['ai_affirmation'],
                    'is_predefined' => true,
                    'is_active' => true,
                    'sort_order' => $habit['sort_order'],
                ],
            );
        }
    }
}
