<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $exceptTables = [
        'migrations',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
    ];

    /**
     * Boot the application for tests, with a hard data-safety guard on the database.
     *
     * The php container exports a real OS env var DB_DATABASE=aurora (via docker-compose
     * env_file). Laravel's immutable dotenv will NOT let .env.testing override a real OS
     * env var, and `php artisan test` does not reliably honor phpunit.xml's <env force>.
     * Without this guard the DatabaseTruncation feature suite TRUNCATES the dev `aurora`
     * database. We force the test connection onto a *_test database before any trait
     * (DatabaseTruncation/RefreshDatabase) can touch it. Runs only inside the test suite.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = (string) $app['config']->get('database.default');
        $key = "database.connections.{$connection}.database";
        $database = (string) $app['config']->get($key);

        if ($database !== '' && ! str_ends_with($database, '_test')) {
            $app['config']->set($key, $database.'_test');
            $app['db']->purge($connection);
        }

        return $app;
    }
}
