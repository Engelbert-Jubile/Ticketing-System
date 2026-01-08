<?php

// app/Models/Attachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $original_name
 * @property string $path
 * @property string|null $mime
 * @property int|null $size
 * @property int $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $attachable
 * @property-read string $disk
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereAttachableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereAttachableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attachment whereUploadedBy($value)
 *
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'original_name',
        'path',
        'size',
        'mime',
        'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    // helper untuk download
    public function getDiskAttribute(): string
    {
        return 'public';
    }
}
