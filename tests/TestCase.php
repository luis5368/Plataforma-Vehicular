<?php

namespace Tests;

use App\Models\User;
use App\Services\ActiveSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';
        // Never load the developer's private .env when running this suite.
        $app->useEnvironmentPath(__DIR__.'/Fixtures/environment')->loadEnvironmentFrom('.env.absent');
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    // actingAs bypasses the login controller; create the same session binding for feature tests.
    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        if ($user instanceof User && $user->is_active) {
            app(ActiveSession::class)->start($user, app('session')->driver());
        }

        return $this;
    }
}
