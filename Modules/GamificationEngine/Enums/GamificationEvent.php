<?php

namespace Modules\GamificationEngine\Enums;

/**
 * Event names dispatched by app features; rules in DB listen on these strings.
 */
enum GamificationEvent: string
{
    /** User starts a No Contact / Ghost Mode protocol. */
    case GhostModeActivated = 'ghost_mode.activated';
    /** First visit per calendar day while protocol active. */
    case GhostModeDailyLogin = 'ghost_mode.daily_login';
    /** Alias for daily check-in rewards (no miss penalty). */
    case GhostModeDailyCheckin = 'ghost_mode.daily_checkin';
    /** Today's Ghost Mode mission marked complete. */
    case GhostModeMissionCompleted = 'ghost_mode.mission_completed';
    /** Panic / emergency button used successfully. */
    case GhostModePanicUsed = 'ghost_mode.panic_used';
    /** User confirmed blocking ex (no proof upload). */
    case GhostModeBlockConfirmed = 'ghost_mode.block_confirmed';
    /** Finished emergency breathing/support flow. */
    case GhostModeEmergencyCompleted = 'ghost_mode.emergency_completed';
    /** Saved a blackhole journal entry. */
    case GhostModeBlackholeWrite = 'ghost_mode.blackhole_write';
    /** Honest slip report; metadata.trigger selects penalty rule. */
    case GhostModeSlipReported = 'ghost_mode.slip_reported';
    /** Timer reached zero / protocol success. */
    case GhostModeProtocolCompleted = 'ghost_mode.protocol_completed';
    /** Mission engine marks enrollment complete; metadata.difficulty optional. */
    case MissionCompleted = 'mission.completed';
    /** Profile fields saved. */
    case ProfileUpdated = 'profile.updated';
    /** Internal: logged by purchaseShopItem. */
    case ShopPurchase = 'shop.purchase';
    /** Internal: logged by consumePerk. */
    case PerkConsumed = 'perk.consumed';
    /** Internal: logged by adjustWallet. */
    case AdminAdjustment = 'admin.adjustment';

    /** Interactive breathing + optional panic challenge completed successfully. */
    case PanicChallengeCompleted = 'panic_challenge.completed';

    /** Blackhole draft analyzed (before destruction). */
    case BlackholeAnalyzed = 'ghost_mode.blackhole_analyzed';

    /** User destroyed the AI rewrite suggestion instead of the raw draft. */
    case BlackholeRewriteAccepted = 'ghost_mode.blackhole_rewrite_accepted';

    /** Blackhole tier milestone (metadata.tier). */
    case BlackholeTierReached = 'ghost_mode.blackhole_tier_reached';

    /** Seven consecutive days with at least one blackhole write. */
    case BlackholeStreak7 = 'ghost_mode.blackhole_streak_7';

    /** User completed a Positive Alchemy daily commitment. */
    case AlchemyCommitmentCompleted = 'alchemy.commitment_completed';

    /** User finished a chosen recovery punishment after a slip. */
    case PunishmentCompleted = 'punishment.completed';

    /** @return list<string> All event values for validation and admin selects. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
