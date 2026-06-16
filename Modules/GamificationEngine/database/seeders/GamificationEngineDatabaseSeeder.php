<?php

namespace Modules\GamificationEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationPerkType;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Enums\GamificationShopEffectType;
use Modules\GamificationEngine\Models\GamificationBadge;
use Modules\GamificationEngine\Models\GamificationPerk;
use Modules\GamificationEngine\Models\GamificationRule;
use Modules\GamificationEngine\Models\GamificationShopItem;

/**
 * Seeds badges, perks, shop items, and default reward/penalty rules for Ghost Mode & missions.
 */
class GamificationEngineDatabaseSeeder extends Seeder
{
    /** Run all gamification seeders. */
    public function run(): void
    {
        $this->seedBadges();
        $this->seedPerks();
        $this->seedShopItems();
        $this->seedRules();
        $this->call(GamificationPunishmentsSeeder::class);
    }

    private function seedBadges(): void
    {
        $badges = [
            ['slug' => 'first_day', 'name' => 'Journey Start', 'icon' => 'fa-flag-checkered'],
            ['slug' => 'week_warrior', 'name' => 'Week Warrior', 'icon' => 'fa-fire'],
            ['slug' => 'phoenix_rising', 'name' => 'Phoenix Rising', 'icon' => 'fa-dove'],
            ['slug' => 'phoenix_master', 'name' => 'Phoenix Master', 'icon' => 'fa-crown'],
            ['slug' => 'blackhole_master', 'name' => 'Blackhole Master', 'icon' => 'fa-circle-dot'],
            ['slug' => 'emergency_pro', 'name' => 'Emergency Pro', 'icon' => 'fa-heart-pulse'],
            ['slug' => 'streak_legend', 'name' => 'Streak Legend', 'icon' => 'fa-star'],
            ['slug' => 'mission_ace', 'name' => 'Mission Ace', 'icon' => 'fa-bullseye'],
            ['slug' => 'profile_complete', 'name' => 'Profile Complete', 'icon' => 'fa-user-check'],
            ['slug' => 'firefighter', 'name' => 'Firefighter', 'icon' => 'fa-fire-extinguisher'],
            ['slug' => 'panic_master', 'name' => 'Panic Master', 'icon' => 'fa-brain'],
            ['slug' => 'blackhole_apprentice', 'name' => 'Blackhole Apprentice', 'icon' => 'fa-graduation-cap'],
            ['slug' => 'shadow_speaker', 'name' => 'Shadow Speaker', 'icon' => 'fa-moon'],
            ['slug' => 'alchemy_apprentice', 'name' => 'Alchemy Apprentice', 'icon' => 'fa-flask'],

            // VirtueEngine badges
            ['slug' => 'virtue_first_win', 'name' => 'First Victory', 'icon' => 'fa-medal'],
            ['slug' => 'virtue_streak_7', 'name' => 'Virtue Streak', 'icon' => 'fa-fire-flame-curved'],
            ['slug' => 'virtue_master', 'name' => 'Virtue Master', 'icon' => 'fa-trophy'],
            ['slug' => 'virtue_honest', 'name' => 'Honest Heart', 'icon' => 'fa-heart'],
        ];

        foreach ($badges as $badge) {
            GamificationBadge::query()->updateOrCreate(
                ['slug' => $badge['slug']],
                [
                    'name' => $badge['name'],
                    'description' => null,
                    'icon' => $badge['icon'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedPerks(): void
    {
        $perks = [
            ['slug' => 'free_shield_repair', 'name' => 'Free Shield Repair', 'icon' => 'fa-shield-halved', 'type' => GamificationPerkType::Consumable, 'duration_days' => null],
            ['slug' => 'exclusive_badge_frame', 'name' => 'Exclusive Badge Frame', 'icon' => 'fa-frame', 'type' => GamificationPerkType::Permanent, 'duration_days' => null],
            ['slug' => 'emergency_voice_message', 'name' => 'Emergency Voice Message', 'icon' => 'fa-microphone', 'type' => GamificationPerkType::Permanent, 'duration_days' => null],
            ['slug' => 'fast_blackhole', 'name' => 'Fast Blackhole', 'icon' => 'fa-circle-dot', 'type' => GamificationPerkType::Permanent, 'duration_days' => null],
            ['slug' => 'panic_calm_24h', 'name' => '24h Panic Calm', 'icon' => 'fa-heart-pulse', 'type' => GamificationPerkType::Consumable, 'duration_days' => null],
            ['slug' => 'slip_discount_50', 'name' => '50% Slip Shield', 'icon' => 'fa-percent', 'type' => GamificationPerkType::Consumable, 'duration_days' => null],
        ];

        foreach ($perks as $perk) {
            GamificationPerk::query()->updateOrCreate(
                ['slug' => $perk['slug']],
                [
                    'name' => $perk['name'],
                    'icon' => $perk['icon'],
                    'description' => null,
                    'type' => $perk['type'],
                    'duration_days' => $perk['duration_days'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedShopItems(): void
    {
        $items = [
            [
                'slug' => 'streak_freeze',
                'name' => 'Streak Freeze',
                'icon' => 'fa-snowflake',
                'cost_coins' => 100,
                'effect_type' => GamificationShopEffectType::StreakFreeze,
                'effects' => ['charges' => 1],
                'sort_order' => 10,
            ],
            [
                'slug' => 'shield_repair',
                'name' => 'Shield Repair Kit',
                'icon' => 'fa-shield-halved',
                'cost_coins' => 50,
                'effect_type' => GamificationShopEffectType::ShieldRepair,
                'effects' => ['percent' => 10],
                'sort_order' => 20,
            ],
            [
                'slug' => 'emergency_boost',
                'name' => 'Emergency Boost',
                'icon' => 'fa-bolt',
                'cost_coins' => 30,
                'effect_type' => GamificationShopEffectType::EmergencyBoost,
                'effects' => ['hours' => 12],
                'sort_order' => 30,
            ],
            [
                'slug' => 'badge_frame',
                'name' => 'Custom Badge Frame',
                'icon' => 'fa-frame',
                'cost_coins' => 200,
                'effect_type' => GamificationShopEffectType::GrantPerk,
                'effects' => ['perk' => 'exclusive_badge_frame'],
                'sort_order' => 40,
            ],
            [
                'slug' => 'panic_boost',
                'name' => 'Panic Boost',
                'icon' => 'fa-bolt-lightning',
                'cost_coins' => 30,
                'effect_type' => GamificationShopEffectType::EmergencyBoost,
                'effects' => ['hours' => 24],
                'sort_order' => 50,
            ],
            [
                'slug' => 'blackhole_rewrite_kit',
                'name' => 'Rewrite Kit',
                'icon' => 'fa-pen-fancy',
                'cost_coins' => 20,
                'effect_type' => GamificationShopEffectType::GrantPerk,
                'effects' => ['perk' => 'panic_calm_24h'],
                'sort_order' => 60,
            ],
            [
                'slug' => 'emotional_shield',
                'name' => 'Emotional Shield',
                'icon' => 'fa-shield-heart',
                'cost_coins' => 100,
                'effect_type' => GamificationShopEffectType::GrantPerk,
                'effects' => ['perk' => 'free_shield_repair'],
                'sort_order' => 70,
            ],
        ];

        foreach ($items as $item) {
            GamificationShopItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => null,
                    'icon' => $item['icon'],
                    'cost_coins' => $item['cost_coins'],
                    'effect_type' => $item['effect_type'],
                    'effects' => $item['effects'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedRules(): void
    {
        $rules = [
            [
                'key' => 'ghost_activate',
                'name' => 'Ghost Mode activated',
                'event' => GamificationEvent::GhostModeActivated->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 20, 'coins' => 10, 'xp' => 25, 'badge' => 'first_day'],
                'max_per_day' => null,
                'priority' => 10,
            ],
            [
                'key' => 'ghost_daily_login',
                'name' => 'Daily login',
                'event' => GamificationEvent::GhostModeDailyLogin->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 2, 'coins' => 5, 'xp' => 0],
                'max_per_day' => 1,
                'priority' => 20,
            ],
            [
                'key' => 'ghost_emergency',
                'name' => 'Emergency mode completed',
                'event' => GamificationEvent::GhostModeEmergencyCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 5, 'coins' => 3, 'xp' => 15],
                'max_per_day' => 3,
                'priority' => 30,
            ],
            [
                'key' => 'ghost_blackhole',
                'name' => 'Blackhole write',
                'event' => GamificationEvent::GhostModeBlackholeWrite->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 2, 'coins' => 2, 'xp' => 5],
                'max_per_day' => 5,
                'priority' => 40,
            ],
            [
                'key' => 'ghost_slip_profile',
                'name' => 'Slip: checked profile',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'checked_profile', 'apply_penalty' => true],
                'effects' => ['points' => -5, 'coins' => -2, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 50,
            ],
            [
                'key' => 'ghost_slip_message',
                'name' => 'Slip: sent message',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'sent_message', 'apply_penalty' => true],
                'effects' => ['points' => -15, 'coins' => -5, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 51,
            ],
            [
                'key' => 'ghost_slip_weak',
                'name' => 'Slip: felt weak',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'felt_weak', 'apply_penalty' => true],
                'effects' => ['points' => -10, 'coins' => -2, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 52,
            ],
            [
                'key' => 'ghost_slip_other',
                'name' => 'Slip: other',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'other', 'apply_penalty' => true],
                'effects' => ['points' => -5, 'coins' => -2, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 53,
            ],
            [
                'key' => 'ghost_slip_honesty',
                'name' => 'Honest slip report',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 1, 'coins' => 0, 'xp' => 0],
                'max_per_day' => null,
                'priority' => 54,
            ],
            [
                'key' => 'ghost_protocol_complete',
                'name' => 'Protocol completed',
                'event' => GamificationEvent::GhostModeProtocolCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 500, 'coins' => 100, 'xp' => 200, 'badge' => 'phoenix_master', 'perk' => 'free_shield_repair'],
                'max_per_day' => null,
                'priority' => 60,
            ],
            [
                'key' => 'mission_completed_hard',
                'name' => 'Hard mission completed',
                'event' => GamificationEvent::MissionCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['difficulty' => 'hard'],
                'effects' => ['points' => 20, 'coins' => 5, 'xp' => 25, 'badge' => 'mission_ace'],
                'max_per_day' => null,
                'priority' => 70,
            ],
            [
                'key' => 'mission_completed_medium',
                'name' => 'Medium mission completed',
                'event' => GamificationEvent::MissionCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['difficulty' => 'medium'],
                'effects' => ['points' => 12, 'coins' => 3, 'xp' => 15],
                'max_per_day' => null,
                'priority' => 71,
            ],
            [
                'key' => 'mission_completed_easy',
                'name' => 'Easy mission completed',
                'event' => GamificationEvent::MissionCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['difficulty' => 'easy'],
                'effects' => ['points' => 6, 'coins' => 2, 'xp' => 8],
                'max_per_day' => null,
                'priority' => 72,
            ],
            [
                'key' => 'ghost_daily_streak',
                'name' => 'Daily login streak tick',
                'event' => GamificationEvent::GhostModeDailyLogin->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['increment_streak' => true],
                'max_per_day' => 1,
                'priority' => 21,
            ],
            [
                'key' => 'ghost_streak_7',
                'name' => '7-day streak milestone',
                'event' => GamificationEvent::GhostModeDailyLogin->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['streak_days' => 7],
                'effects' => ['points' => 15, 'coins' => 10, 'xp' => 20, 'badge' => 'week_warrior'],
                'max_per_day' => 1,
                'priority' => 22,
            ],
            [
                'key' => 'ghost_streak_30',
                'name' => '30-day streak milestone',
                'event' => GamificationEvent::GhostModeDailyLogin->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['streak_days' => 30],
                'effects' => ['points' => 50, 'coins' => 30, 'xp' => 60, 'badge' => 'streak_legend'],
                'max_per_day' => 1,
                'priority' => 23,
            ],
            [
                'key' => 'ghost_blackhole_master',
                'name' => 'Blackhole master (5th write today)',
                'event' => GamificationEvent::GhostModeBlackholeWrite->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['writes_today' => 5],
                'effects' => ['points' => 10, 'coins' => 5, 'badge' => 'blackhole_master'],
                'max_per_day' => 1,
                'priority' => 41,
            ],
            [
                'key' => 'ghost_emergency_pro',
                'name' => 'Emergency pro (3rd today)',
                'event' => GamificationEvent::GhostModeEmergencyCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['completions_today' => 3],
                'effects' => ['points' => 8, 'coins' => 4, 'badge' => 'emergency_pro'],
                'max_per_day' => 1,
                'priority' => 31,
            ],
            [
                'key' => 'ghost_protocol_phoenix',
                'name' => 'Protocol complete — Phoenix Rising',
                'event' => GamificationEvent::GhostModeProtocolCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['tier' => 'standard'],
                'effects' => ['points' => 200, 'coins' => 50, 'xp' => 100, 'badge' => 'phoenix_rising'],
                'max_per_day' => null,
                'priority' => 61,
            ],
            [
                'key' => 'profile_updated_reward',
                'name' => 'Profile updated',
                'event' => GamificationEvent::ProfileUpdated->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 5, 'coins' => 2, 'xp' => 5],
                'max_per_day' => 1,
                'priority' => 80,
            ],
            [
                'key' => 'profile_complete_badge',
                'name' => 'Profile fully complete',
                'event' => GamificationEvent::ProfileUpdated->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['complete' => true],
                'effects' => ['points' => 10, 'coins' => 5, 'badge' => 'profile_complete'],
                'max_per_day' => 1,
                'priority' => 81,
            ],
            [
                'key' => 'ghost_slip_streak_only',
                'name' => 'Slip: streak reset only (minor)',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'viewed_memory', 'apply_penalty' => true],
                'effects' => ['points' => -2, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 49,
            ],
            [
                'key' => 'ghost_slip_message_discounted',
                'name' => 'Slip: sent message (50% off)',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'sent_message', 'discounted' => true],
                'effects' => ['points' => -8, 'coins' => -3, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 48,
            ],
            [
                'key' => 'ghost_slip_weak_discounted',
                'name' => 'Slip: felt weak (50% off)',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'felt_weak', 'discounted' => true],
                'effects' => ['points' => -5, 'coins' => -1, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 48,
            ],
            [
                'key' => 'ghost_slip_profile_discounted',
                'name' => 'Slip: checked profile (50% off)',
                'event' => GamificationEvent::GhostModeSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => ['trigger' => 'checked_profile', 'discounted' => true],
                'effects' => ['points' => -3, 'coins' => -1, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 48,
            ],
            [
                'key' => 'panic_challenge_done',
                'name' => 'Panic challenge completed',
                'event' => GamificationEvent::PanicChallengeCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['success' => true],
                'effects' => ['coins' => 5, 'xp' => 10, 'streak_freeze_hours' => 24],
                'max_per_day' => 3,
                'priority' => 15,
            ],
            [
                'key' => 'panic_breathing_bonus',
                'name' => 'Breathing sync bonus',
                'event' => GamificationEvent::PanicChallengeCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['breathing' => true],
                'effects' => ['coins' => 2, 'metadata' => ['slip_penalty_discount_percent' => 5]],
                'max_per_day' => 3,
                'priority' => 16,
            ],
            [
                'key' => 'emergency_firefighter',
                'name' => 'Firefighter badge (10 emergencies)',
                'event' => GamificationEvent::GhostModeEmergencyCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['emergency_count' => 10],
                'effects' => ['badge' => 'firefighter'],
                'max_per_day' => null,
                'priority' => 32,
            ],
            [
                'key' => 'emergency_panic_master',
                'name' => 'Panic master badge (30 emergencies)',
                'event' => GamificationEvent::GhostModeEmergencyCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['emergency_count' => 30],
                'effects' => ['badge' => 'panic_master'],
                'max_per_day' => null,
                'priority' => 33,
            ],
            [
                'key' => 'blackhole_analyzed_high_risk',
                'name' => 'High-risk analysis bonus',
                'event' => GamificationEvent::BlackholeAnalyzed->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['high_risk' => 1],
                'effects' => ['coins' => 1],
                'max_per_day' => 5,
                'priority' => 42,
            ],
            [
                'key' => 'blackhole_rewrite_reward',
                'name' => 'Destroyed rational rewrite',
                'event' => GamificationEvent::BlackholeRewriteAccepted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['coins' => 3, 'xp' => 5],
                'max_per_day' => null,
                'priority' => 43,
            ],
            [
                'key' => 'blackhole_tier_5',
                'name' => 'Blackhole tier 5',
                'event' => GamificationEvent::BlackholeTierReached->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['tier' => 5],
                'effects' => ['coins' => 10, 'badge' => 'blackhole_apprentice'],
                'max_per_day' => null,
                'priority' => 44,
            ],
            [
                'key' => 'blackhole_tier_10',
                'name' => 'Blackhole tier 10',
                'event' => GamificationEvent::BlackholeTierReached->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['tier' => 10],
                'effects' => ['coins' => 25, 'perk' => 'fast_blackhole'],
                'max_per_day' => null,
                'priority' => 45,
            ],
            [
                'key' => 'blackhole_tier_20',
                'name' => 'Blackhole tier 20',
                'event' => GamificationEvent::BlackholeTierReached->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => ['tier' => 20],
                'effects' => ['coins' => 50, 'badge' => 'blackhole_master'],
                'max_per_day' => null,
                'priority' => 46,
            ],
            [
                'key' => 'alchemy_commitment_done',
                'name' => 'Positive Alchemy commitment',
                'event' => GamificationEvent::AlchemyCommitmentCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['coins' => 5, 'xp' => 10, 'badge' => 'alchemy_apprentice'],
                'max_per_day' => 1,
                'priority' => 5,
            ],
            [
                'key' => 'blackhole_streak_7_reward',
                'name' => '7-day blackhole streak',
                'event' => GamificationEvent::BlackholeStreak7->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['coins' => 30, 'badge' => 'shadow_speaker', 'increment_metadata' => ['streak_freeze_charges' => 1]],
                'max_per_day' => 1,
                'priority' => 47,
            ],
            [
                'key' => 'ghost_panic_button',
                'name' => 'Panic button used',
                'event' => GamificationEvent::GhostModePanicUsed->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 5, 'coins' => 1, 'xp' => 5],
                'max_per_day' => 10,
                'priority' => 25,
            ],
            [
                'key' => 'ghost_daily_checkin',
                'name' => 'Daily check-in',
                'event' => GamificationEvent::GhostModeDailyCheckin->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 2, 'coins' => 1, 'xp' => 3],
                'max_per_day' => 1,
                'priority' => 19,
            ],
            [
                'key' => 'ghost_mission_completed',
                'name' => 'Daily mission completed',
                'event' => GamificationEvent::GhostModeMissionCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 8, 'coins' => 3, 'xp' => 10],
                'max_per_day' => 1,
                'priority' => 18,
            ],
            [
                'key' => 'ghost_block_confirmed',
                'name' => 'Block confirmed',
                'event' => GamificationEvent::GhostModeBlockConfirmed->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 5, 'coins' => 2, 'xp' => 5],
                'max_per_day' => 1,
                'priority' => 17,
            ],
            [
                'key' => 'punishment_recovery',
                'name' => 'Recovery task completed',
                'event' => GamificationEvent::PunishmentCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => [
                    'points_from_metadata' => 'points_recovered',
                    'coins_from_metadata' => 'coins_recovered',
                    'xp' => 5,
                ],
                'max_per_day' => null,
                'priority' => 55,
            ],

            // ─── VirtueEngine rules ───────────────────────────────────────────────────
            [
                'key' => 'virtue_success_logged',
                'name' => 'Virtue: success logged',
                'event' => GamificationEvent::VirtueSuccessLogged->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 5, 'xp' => 8, 'badge' => 'virtue_first_win'],
                'max_per_day' => 5,
                'priority' => 90,
            ],
            [
                'key' => 'virtue_streak_7',
                'name' => 'Virtue: 7-day streak',
                'event' => GamificationEvent::VirtueStreak7->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 30, 'coins' => 15, 'xp' => 40, 'badge' => 'virtue_streak_7'],
                'max_per_day' => 1,
                'priority' => 91,
            ],
            [
                'key' => 'virtue_routine_completed',
                'name' => 'Virtue: routine completed',
                'event' => GamificationEvent::VirtueRoutineCompleted->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 200, 'coins' => 50, 'xp' => 150, 'badge' => 'virtue_master'],
                'max_per_day' => null,
                'priority' => 92,
            ],
            [
                'key' => 'virtue_slip_penalty',
                'name' => 'Virtue: slip reported (penalty)',
                'event' => GamificationEvent::VirtueSlipReported->value,
                'rule_type' => GamificationRuleType::Penalty,
                'conditions' => null,
                'effects' => ['points' => -8, 'reset_streak' => true],
                'max_per_day' => null,
                'priority' => 93,
            ],
            [
                'key' => 'virtue_slip_honesty',
                'name' => 'Virtue: honest slip (tiny reward)',
                'event' => GamificationEvent::VirtueSlipReported->value,
                'rule_type' => GamificationRuleType::Reward,
                'conditions' => null,
                'effects' => ['points' => 1, 'badge' => 'virtue_honest'],
                'max_per_day' => null,
                'priority' => 94,
            ],
        ];

        foreach ($rules as $rule) {
            GamificationRule::query()->updateOrCreate(
                ['key' => $rule['key']],
                [
                    'name' => $rule['name'],
                    'description' => null,
                    'event' => $rule['event'],
                    'rule_type' => $rule['rule_type'],
                    'conditions' => $rule['conditions'],
                    'effects' => $rule['effects'],
                    'max_per_day' => $rule['max_per_day'],
                    'priority' => $rule['priority'],
                    'is_active' => true,
                ],
            );
        }
    }
}
