<?php

// M1 policy defaults (MD-01, D-21). These are the fallback values consumed
// by the code when a corresponding row in the `settings` table (chairman-
// editable, with settings_history) has not been set yet. In steady state the
// `settings` table is authoritative; these env-backed defaults exist so the
// system boots correctly on a freshly migrated database.
return [
    'auth' => [
        'max_failed_attempts' => (int) env('AUTH_MAX_FAILED_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),
        'inactivity_minutes' => (int) env('AUTH_INACTIVITY_MINUTES', 30),
        'token_ttl_minutes' => (int) env('AUTH_TOKEN_TTL_MINUTES', 480),
        'mfa_challenge_ttl_minutes' => (int) env('AUTH_MFA_CHALLENGE_TTL_MINUTES', 5),
        'min_password_length' => (int) env('AUTH_MIN_PASSWORD_LENGTH', 5),
    ],
    // D-21 tunables, seeded into `settings` by the seeder; kept here only as
    // the documented initial values.
    'defaults' => [
        'reopen_window_days' => 7,
        'approval_validity_days' => 14,
        'transfer_reminder_days' => 3,
        'late_approval_hours' => 72,
        'attachment_size_limit_mb' => null, // D-34 Pending — intentionally left null
    ],
];
