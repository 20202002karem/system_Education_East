<?php

namespace Tests;

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Authenticate the following requests as $user with a real Sanctum token plus the
     * `sessions` row that the `session.activity` middleware requires (M1 criterion 8).
     * MINIMAL COMPATIBLE FIX: replaces Sanctum::actingAs(), which has no sessions row.
     */
    protected function signIn(User $user): static
    {
        $token = $user->createToken('api', ['*'], now()->addHour());
        Session::create([
            'id' => (string) $token->accessToken->id, 'user_id' => $user->id,
            'last_activity_at' => now('UTC'), 'expires_at' => now()->addHour(), 'ip' => '127.0.0.1',
        ]);
        $this->app['auth']->forgetGuards();

        return $this->withToken($token->plainTextToken);
    }
}
