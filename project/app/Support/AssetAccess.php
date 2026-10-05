<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\SiteManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * M2 read/write scope for assets (Batch 3 §9). Reads follow users.view_scope;
 * writes need chairman or an active edit_assets grant (never school_manager).
 * Kept separate from M1 AccessPolicy, which cannot express own_site/assigned_only.
 */
class AssetAccess
{
    /** null = unrestricted; array = allowed site ids (possibly empty). */
    public static function visibleSiteIds(User $user): ?array
    {
        if ($user->isChairman()) {
            return null;
        }

        return match ($user->view_scope) {
            'all' => null,
            'own_site' => SiteManager::where('user_id', $user->id)->whereNull('to')->pluck('site_id')->all(),
            'sites' => $user->siteScopes()->pluck('site_id')->all(),
            default => [], // assigned_only: nothing before M3
        };
    }

    public static function scopeVisible(Builder $query, User $user): Builder
    {
        $ids = self::visibleSiteIds($user);

        return $ids === null ? $query : $query->whereIn('current_site_id', $ids);
    }

    public static function canSeeSite(User $user, int $siteId): bool
    {
        $ids = self::visibleSiteIds($user);

        return $ids === null || in_array($siteId, $ids, true);
    }

    public static function canSee(User $user, Asset $asset): bool
    {
        return self::canSeeSite($user, (int) $asset->current_site_id);
    }

    public static function canEdit(User $user): bool
    {
        if ($user->isChairman()) {
            return true;
        }
        if ($user->role === 'school_manager') {
            return false;
        }

        return $user->hasActivePermission('edit_assets');
    }
}
