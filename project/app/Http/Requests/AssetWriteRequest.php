<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Support\AssetAccess;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for asset writes. Order (authorize runs before validation):
 * asset out of the actor's scope -> 404 (IN-08); no edit right -> 403.
 */
abstract class AssetWriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $asset = $this->route('asset');
        if ($asset instanceof Asset && ! AssetAccess::canSee($user, $asset)) {
            abort(404);
        }

        return $this->allowed();
    }

    protected function allowed(): bool
    {
        return AssetAccess::canEdit($this->user());
    }
}
