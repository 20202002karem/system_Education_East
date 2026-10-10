<?php

namespace App\Support;

use App\Models\RequestCycle;
use App\Models\ServiceRequest;
use App\Models\SiteManager;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * M3 read scope for requests and tasks (Batch 3 §8, Batch 1 §6/§12).
 * - chairman / secretary: every request and task (global / "واسع").
 * - school_manager: requests whose origin_site_id is one of their current sites; no tasks.
 * - engineer: origin site inside view_scope (all / sites) OR assigned to them.
 * - technician (assigned_only): only what is/was assigned to them.
 * Server-side only; out-of-scope resources are answered with 404 (IN-08).
 */
class RequestAccess
{
    public static function isGlobal(User $user): bool
    {
        return in_array($user->role, ['chairman', 'secretary'], true);
    }

    /** null = unrestricted, array = allowed site ids (possibly empty). */
    public static function siteIds(User $user): ?array
    {
        if (self::isGlobal($user)) {
            return null;
        }
        if ($user->role === 'school_manager') {
            return SiteManager::where('user_id', $user->id)->whereNull('to')->pluck('site_id')->all();
        }

        return AssetAccess::visibleSiteIds($user);
    }

    public static function scopeRequests(Builder $query, User $user): Builder
    {
        $ids = self::siteIds($user);
        if ($ids === null) {
            return $query;
        }
        if ($user->role === 'school_manager') {
            return $query->whereIn('requests.origin_site_id', $ids);
        }

        return $query->where(function (Builder $w) use ($ids, $user) {
            $w->whereIn('requests.origin_site_id', $ids)
                ->orWhereIn('requests.id', RequestCycle::query()->where('assignee_user_id', $user->id)->select('request_id'));
        });
    }

    public static function canSeeRequest(User $user, ServiceRequest $request): bool
    {
        return self::scopeRequests(ServiceRequest::query()->whereKey($request->id), $user)->exists();
    }

    public static function canReadTasks(User $user): bool
    {
        return $user->role !== 'school_manager';
    }

    public static function scopeTasks(Builder $query, User $user): Builder
    {
        if (! self::canReadTasks($user)) {
            return $query->whereRaw('1 = 0');
        }
        $ids = self::siteIds($user);
        if ($ids === null) {
            return $query;
        }

        return $query->where(function (Builder $w) use ($ids, $user) {
            $w->where('tasks.assignee_id', $user->id)
                ->orWhereIn('tasks.site_id', $ids)
                ->orWhereIn('tasks.request_cycle_id', RequestCycle::query()
                    ->whereIn('request_id', ServiceRequest::query()->whereIn('origin_site_id', $ids)->select('id'))->select('id'));
        });
    }

    public static function canSeeTask(User $user, Task $task): bool
    {
        return self::scopeTasks(Task::query()->whereKey($task->id), $user)->exists();
    }

    /** Can the user cover this site as an assignee (BR-M3-04: active, within scope)? */
    public static function coversSite(User $assignee, int $siteId): bool
    {
        if ($assignee->role === 'technician' || $assignee->role === 'chairman') {
            return true;
        }
        $ids = AssetAccess::visibleSiteIds($assignee);

        return $ids === null || in_array($siteId, $ids, true);
    }
}
