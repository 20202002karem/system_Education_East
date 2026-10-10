<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** No update/delete (IN-01, IN-20). storage_path is internal and never serialised. */
class Attachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['owner_type', 'owner_id', 'uploaded_by', 'storage_path', 'sha256', 'size_bytes', 'mime_type', 'original_filename'];

    protected $hidden = ['storage_path'];
}
