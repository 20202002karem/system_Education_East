<?php

namespace Database\Seeders;

use App\Models\IntakeChannel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * M1 seed data. This is intentionally NOT sample/demo data (SRS/instructions
 * forbid unneeded test data in migrations); it only seeds:
 *   - the D-21 tunable settings at their documented initial values,
 *   - the P-09 intake channel reference values (explicitly given in SRS §6),
 *   - a single bootstrap chairman account, required because AUTH-02 says
 *     ONLY the chairman can create/disable accounts — the very first chairman
 *     account cannot be created through the API and must be seeded once.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // D-21 tunables (values are [معتمد]; attachment_size_limit stays null — D-34 Pending).
        $settings = [
            'reopen_window_days' => '7',
            'approval_validity_days' => '14',
            'transfer_reminder_days' => '3',
            'late_approval_hours' => '72',
            'session_inactivity_minutes' => (string) config('m1.auth.inactivity_minutes'),
            'max_failed_login_attempts' => (string) config('m1.auth.max_failed_attempts'),
            'lockout_minutes' => (string) config('m1.auth.lockout_minutes'),
            'attachment_size_limit' => null, // D-34 Pending — intentionally no value
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // P-09 — initial intake channel values, given verbatim in SRS §6.
        foreach (['الهاتف', 'الورق', 'المراجعة الشخصية', 'النظام الإلكتروني'] as $name) {
            IntakeChannel::firstOrCreate(['name' => $name]);
        }

        // Bootstrap chairman account — CHANGE THE PASSWORD IMMEDIATELY.
        // mfa_enabled is intentionally left false: the chairman must complete
        // TOTP enrollment out-of-band before they can log in (AuthController
        // refuses login with mfa_not_provisioned until mfa_enabled=true and
        // mfa_secret is set — this app does not auto-enroll 2FA on seed).
        if (! User::where('role', 'chairman')->exists()) {
            $chairman = User::create([
                'name' => 'رئيس القسم',
                'email' => env('BOOTSTRAP_CHAIRMAN_EMAIL', 'chairman@moehe.example'),
                'role' => 'chairman',
                'view_scope' => 'all',
            ]);
            $chairman->forceFill([
                'password_hash' => Hash::make(env('BOOTSTRAP_CHAIRMAN_PASSWORD', 'ChangeMe-12345')),
            ])->save();
        }
    }
}
