<?php

namespace App\Support;

use App\Models\Site;
use App\Models\User;

/**
 * Centralized authorization (per instructions §H — "AccessPolicy مركزية").
 * Flutter/Web are never the source of truth for permissions; every check here
 * is re-run server-side regardless of what the client sends.
 *
 * M1 Authorization Matrix (Batch 3 §4): in M1, essentially every write
 * operation across Users, Permission grants, Sites/Site managers, and
 * Settings/Reference data is chairman-only — there is no approval chain
 * above the chairman in M1 (D-01/D-02: no ministry-director role exists).
 * Any cell not explicitly approved in the docs defaults to denied (least
 * privilege), including "Sites — read" for non-chairman roles, which is
 * explicitly left "⏳ يحتاج قراراً" / closed-by-default in Batch 3 §4 and §9.
 */
class AccessPolicy
{
    public static function isChairman(?User $user): bool
    {
        return $user && $user->role === 'chairman' && $user->isActive();
    }

    /** Users, permission-grants, sites/site-managers writes, settings, reference data: chairman only. */
    public static function canManageAdmin(?User $user): bool
    {
        return self::isChairman($user);
    }

    /**
     * Sites — read. Chairman only in M1 (Batch 3 §4: all other roles are
     * "⏳ يحتاج قراراً" and therefore closed/403 by default until a future
     * milestone explicitly opens them — do not open this from the implementation).
     */
    public static function canReadSites(?User $user): bool
    {
        return self::isChairman($user);
    }

    /** Audit log read. Chairman only — secretary/engineer access is P-07, closed by default (Batch 3 §2.5/§9 item 5). */
    public static function canReadAuditLog(?User $user): bool
    {
        return self::isChairman($user);
    }

    /**
     * Scope isolation (IN-08). Returns whether $user may act on $site given
     * their view_scope. Not used to gate M1 endpoints directly (M1 write
     * endpoints are chairman-only and chairman.view_scope is implicitly
     * 'all'), but provided as the general-purpose primitive M2/M3 will reuse
     * so scope logic is designed once, per M1 criterion 3.
     */
    public static function userCanAccessSite(User $user, Site $site): bool
    {
        return match ($user->view_scope) {
            'all' => true,
            'own_site' => false, // own_site applies to school_manager against their own school record, not sites CRUD (out of scope in M1)
            'sites' => $user->siteScopes()->where('site_id', $site->id)->exists(),
            'assigned_only' => false, // resolved per-assignment in M3 (tasks/requests), not applicable to sites directly
            default => false,
        };
    }
}
