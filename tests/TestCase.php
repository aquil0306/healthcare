<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions for all tests
        $this->artisan('db:seed', ['--class' => 'Database\Seeders\RolePermissionSeeder']);
    }
}
