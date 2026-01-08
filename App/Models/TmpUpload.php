<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $original_name
 * @property string $path
 * @property string|null $mime
 * @property int|null $size
 * @property int $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TmpUpload whereUploadedBy($value)
 *
 * @mixin \Eloquent
 */
class TmpUpload extends Model
{
    public $incrementing = false;

    protected $keyType = 'string'; // uuid

    protected $fillable = [
        'id', 'original_name', 'path', 'mime', 'size', 'uploaded_by',
    ];
}
