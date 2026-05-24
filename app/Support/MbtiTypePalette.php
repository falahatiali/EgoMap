<?php

namespace App\Support;

class MbtiTypePalette
{
    /** @var array<string, array{accent: string, soft: string, glow: string, group: string}> */
    private const PALETTES = [
        'intj' => ['accent' => '#7c3aed', 'soft' => '#ede9fe', 'glow' => 'rgba(124, 58, 237, 0.25)', 'group' => 'analyst'],
        'intp' => ['accent' => '#6366f1', 'soft' => '#eef2ff', 'glow' => 'rgba(99, 102, 241, 0.25)', 'group' => 'analyst'],
        'entj' => ['accent' => '#4f46e5', 'soft' => '#e0e7ff', 'glow' => 'rgba(79, 70, 229, 0.25)', 'group' => 'analyst'],
        'entp' => ['accent' => '#8b5cf6', 'soft' => '#f5f3ff', 'glow' => 'rgba(139, 92, 246, 0.25)', 'group' => 'analyst'],
        'infj' => ['accent' => '#10b981', 'soft' => '#ecfdf5', 'glow' => 'rgba(16, 185, 129, 0.25)', 'group' => 'diplomat'],
        'infp' => ['accent' => '#ec4899', 'soft' => '#fdf2f8', 'glow' => 'rgba(236, 72, 153, 0.25)', 'group' => 'diplomat'],
        'enfj' => ['accent' => '#14b8a6', 'soft' => '#f0fdfa', 'glow' => 'rgba(20, 184, 166, 0.25)', 'group' => 'diplomat'],
        'enfp' => ['accent' => '#f43f5e', 'soft' => '#fff1f2', 'glow' => 'rgba(244, 63, 94, 0.25)', 'group' => 'diplomat'],
        'istj' => ['accent' => '#2563eb', 'soft' => '#eff6ff', 'glow' => 'rgba(37, 99, 235, 0.25)', 'group' => 'sentinel'],
        'isfj' => ['accent' => '#0ea5e9', 'soft' => '#f0f9ff', 'glow' => 'rgba(14, 165, 233, 0.25)', 'group' => 'sentinel'],
        'estj' => ['accent' => '#1d4ed8', 'soft' => '#dbeafe', 'glow' => 'rgba(29, 78, 216, 0.25)', 'group' => 'sentinel'],
        'esfj' => ['accent' => '#3b82f6', 'soft' => '#eff6ff', 'glow' => 'rgba(59, 130, 246, 0.25)', 'group' => 'sentinel'],
        'istp' => ['accent' => '#d97706', 'soft' => '#fffbeb', 'glow' => 'rgba(217, 119, 6, 0.25)', 'group' => 'explorer'],
        'isfp' => ['accent' => '#f59e0b', 'soft' => '#fef3c7', 'glow' => 'rgba(245, 158, 11, 0.25)', 'group' => 'explorer'],
        'estp' => ['accent' => '#ea580c', 'soft' => '#ffedd5', 'glow' => 'rgba(234, 88, 12, 0.25)', 'group' => 'explorer'],
        'esfp' => ['accent' => '#f97316', 'soft' => '#fff7ed', 'glow' => 'rgba(249, 115, 22, 0.25)', 'group' => 'explorer'],
    ];

    /**
     * @return array{accent: string, soft: string, glow: string, group: string}
     */
    public static function for(string $code): array
    {
        return self::PALETTES[strtolower($code)] ?? [
            'accent' => '#6366f1',
            'soft' => '#eef2ff',
            'glow' => 'rgba(99, 102, 241, 0.25)',
            'group' => 'analyst',
        ];
    }
}
