<?php

namespace App\Services;

/**
 * MD-01 (M1 criterion 8): password >= 5 chars, not a common password, must
 * not equal/contain the user's name. Length threshold and the common-password
 * list are intentionally minimal here — M1 scope is the mechanism, not a
 * large dictionary; the chairman can raise AUTH_MIN_PASSWORD_LENGTH via config/settings.
 */
class PasswordPolicy
{
    /** Small seed list; extend via config('m1.auth.common_passwords') if needed. */
    protected array $commonPasswords = [
        '12345', '123456', 'password', 'qwerty', '11111', '00000', 'admin123', 'letmein',
    ];

    public function validate(string $password, string $userName): array
    {
        $errors = [];
        $minLength = (int) config('m1.auth.min_password_length', 5);

        if (mb_strlen($password) < $minLength) {
            $errors[] = "كلمة المرور يجب ألا تقل عن {$minLength} محارف";
        }

        if (in_array(mb_strtolower($password), $this->commonPasswords, true)) {
            $errors[] = 'كلمة المرور شائعة جداً، اختر كلمة مرور أخرى';
        }

        if ($userName !== '' && str_contains(mb_strtolower($password), mb_strtolower($userName))) {
            $errors[] = 'كلمة المرور يجب ألا تطابق الاسم';
        }

        return $errors;
    }

    public function passes(string $password, string $userName): bool
    {
        return empty($this->validate($password, $userName));
    }
}
