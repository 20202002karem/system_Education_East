<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'admin@example.com')->firstOrFail();

$google2fa = new \PragmaRX\Google2FA\Google2FA();

$code = $google2fa->getCurrentOtp($user->mfa_secret);

echo "OTP: " . $code . PHP_EOL;