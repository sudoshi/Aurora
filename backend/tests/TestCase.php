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
     * database. We force EVERY pgsql connection onto a *_test database before any trait
     * (DatabaseTruncation/RefreshDatabase) can touch it. Runs only inside the test suite.
     *
     * This must cover the named `app`/`clinical` connections too — not just the default —
     * because `exists:app.users` / `Rule::unique('app.phenotype_features')` validation rules
     * resolve `app`/`clinical` as CONNECTION names; if those still pointed at the live
     * `aurora` DB, validation would query prod (no test data) and writes could hit prod.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        foreach ((array) $app['config']->get('database.connections') as $name => $config) {
            if (($config['driver'] ?? null) !== 'pgsql') {
                continue;
            }

            $database = (string) ($config['database'] ?? '');
            if ($database !== '' && ! str_ends_with($database, '_test')) {
                $app['config']->set("database.connections.{$name}.database", $database.'_test');
                $app['db']->purge($name);
            }
        }

        return $app;
    }
}
