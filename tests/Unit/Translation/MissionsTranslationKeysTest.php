<?php

namespace Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MissionsTranslationKeysTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function usedMissionKeys(): array
    {
        $keys = [];
        $root = dirname(__DIR__, 3);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (! preg_match('/\.(php|blade\.php)$/', $path)) {
                continue;
            }

            if (str_contains($path, '/vendor/')
                || str_contains($path, '/storage/')
                || str_contains($path, '/node_modules/')
                || str_contains($path, '/.git/')) {
                continue;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            preg_match_all("/__\\(['\"]missions\\.([a-z0-9_]+)/", $contents, $matches);

            foreach ($matches[1] as $key) {
                $keys[$key] = [$key];
            }
        }

        ksort($keys);

        return $keys;
    }

    #[DataProvider('usedMissionKeys')]
    public function test_mission_key_exists_in_fa_and_en(string $key): void
    {
        $fa = require lang_path('fa/missions.php');
        $en = require lang_path('en/missions.php');

        $this->assertArrayHasKey(
            $key,
            $fa,
            "Missing fa translation for missions.{$key}",
        );
        $this->assertArrayHasKey(
            $key,
            $en,
            "Missing en translation for missions.{$key}",
        );
        $this->assertNotSame(
            "missions.{$key}",
            __("missions.{$key}", locale: 'fa'),
            "FA locale returned raw key for missions.{$key}",
        );
        $this->assertNotSame(
            "missions.{$key}",
            __("missions.{$key}", locale: 'en'),
            "EN locale returned raw key for missions.{$key}",
        );
    }
}
