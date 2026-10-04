<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'admin@example.com')->firstOrFail();

$totp = app(\App\Services\TotpService::class);

$secret = $totp->generateSecret();

$user->mfa_secret = $secret;
$user->mfa_enabled = true;
$user->save();

echo "MFA ENABLED" . PHP_EOL;
echo "SECRET: " . $secret . PHP_EOL;