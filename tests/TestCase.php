<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSafeTestingDatabase();
    }

    private function ensureSafeTestingDatabase(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database", '');

        if ($connection === 'sqlite') {
            if ($database === ':memory:' || str_contains($database, 'test')) {
                return;
            }

            throw new RuntimeException("Unsafe testing database for sqlite connection: [{$database}]. Use ':memory:' or a test-dedicated database.");
        }

        if ($database === '' || ! str_ends_with($database, '_test')) {
            throw new RuntimeException("Unsafe testing database for [{$connection}] connection: [{$database}]. Configure a dedicated database ending in '_test'.");
        }
    }
}
