<?php

namespace App\Enums;

/**
 * Central permission registry. Use these values everywhere (seeders, @can, middleware).
 *
 * Naming: {domain}.{resource?}.{action}
 * Section gating: content.{page}.{section}
 */
enum Permission: string
{
    // Admin
    case AdminAccess = 'admin.access';
    case AdminQuizzesManage = 'admin.quizzes.manage';
    case AdminMissionsManage = 'admin.missions.manage';
    case AdminGamificationManage = 'admin.gamification.manage';
    case AdminUsersManage = 'admin.users.manage';
    case AdminRolesManage = 'admin.roles.manage';

    // User area
    case DashboardAccess = 'dashboard.access';

    // Quizzes
    case QuizzesTake = 'quizzes.take';
    case QuizzesViewResults = 'quizzes.view-results';

    // Reports (feature-level)
    case ReportsViewFree = 'reports.view-free';
    case ReportsViewPremium = 'reports.view-premium';
    case ReportsDownload = 'reports.download';

    // Report page sections (same page, different blocks)
    case ReportsSectionStrengths = 'reports.section.strengths';
    case ReportsSectionBlindSpot = 'reports.section.blind-spot';
    case ReportsSectionTraps = 'reports.section.traps';
    case ReportsSectionRoadmap = 'reports.section.roadmap';

    // Missions (future)
    case MissionsAccess = 'missions.access';
    case MissionsDailyFull = 'missions.daily-full';

    // Billing
    case BillingManage = 'billing.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Permissions granted to free registered members.
     *
     * @return list<self>
     */
    public static function forMember(): array
    {
        return [
            self::DashboardAccess,
            self::QuizzesTake,
            self::QuizzesViewResults,
            self::ReportsViewFree,
            self::ReportsSectionStrengths,
            self::ReportsSectionBlindSpot,
        ];
    }

    /**
     * Extra permissions for Pro subscribers.
     *
     * @return list<self>
     */
    public static function forPro(): array
    {
        return [
            self::ReportsViewPremium,
            self::ReportsDownload,
            self::ReportsSectionTraps,
            self::ReportsSectionRoadmap,
            self::MissionsAccess,
            self::MissionsDailyFull,
            self::BillingManage,
        ];
    }

    /**
     * @return list<self>
     */
    public static function forAdmin(): array
    {
        return [
            self::AdminAccess,
            self::AdminQuizzesManage,
            self::AdminMissionsManage,
            self::AdminGamificationManage,
            self::AdminUsersManage,
        ];
    }
}
