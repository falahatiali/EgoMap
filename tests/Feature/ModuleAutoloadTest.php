<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleAutoloadTest extends TestCase
{
    /**
     * @return array<string, bool>
     */
    private function enabledModules(): array
    {
        $path = base_path('modules_statuses.json');

        $this->assertTrue(File::exists($path));

        /** @var array<string, bool> $statuses */
        $statuses = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return array_filter($statuses);
    }

    public function test_enabled_module_providers_are_autoloadable(): void
    {
        foreach ($this->enabledModules() as $module => $enabled) {
            $manifestPath = base_path("Modules/{$module}/module.json");

            $this->assertTrue(
                File::exists($manifestPath),
                "Missing module manifest for enabled module [{$module}]."
            );

            /** @var array{providers?: list<string>} $manifest */
            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

            foreach ($manifest['providers'] ?? [] as $provider) {
                $this->assertTrue(
                    class_exists($provider),
                    "Provider [{$provider}] for module [{$module}] is not autoloadable."
                );
            }
        }
    }

    public function test_enabled_modules_are_registered_in_composer_autoload(): void
    {
        $autoloadMap = require base_path('vendor/composer/autoload_psr4.php');

        foreach (array_keys($this->enabledModules()) as $module) {
            $prefix = "Modules\\{$module}\\";

            $this->assertArrayHasKey(
                $prefix,
                $autoloadMap,
                "Composer autoload is missing PSR-4 prefix [{$prefix}]. Run composer dump-autoload."
            );
        }
    }
}
