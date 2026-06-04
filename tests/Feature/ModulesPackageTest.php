<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModulesPackageTest extends TestCase
{
    public function test_modules_package_is_configured(): void
    {
        $this->assertSame('Modules', config('modules.namespace'));
        $this->assertTrue(File::isDirectory(base_path('Modules')));
        $this->assertTrue(File::exists(base_path('modules_statuses.json')));
    }

    public function test_module_list_command_runs(): void
    {
        $this->artisan('module:list')
            ->assertSuccessful();
    }
}
