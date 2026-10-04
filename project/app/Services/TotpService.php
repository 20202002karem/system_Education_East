<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP for chairman 2FA (AUTH-07 / M1 criterion 2). Secret is stored encrypted
 * in users.mfa_secret via the model's `encrypted` cast and is never returned
 * from any endpoint, never logged, and never written to audit_log — enforced
 * by never passing it into AuditLogger::record() anywhere in this codebase.
 */
class TotpService
{
    protected Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code, 1) !== false;
    }

    public function qrCodeUrl(string $secret, string $email): string
    {
        return $this->engine->getQRCodeUrl(
            config('app.name', 'MOEHE-EastGaza'),
            $email,
            $secret
        );
    }
}
