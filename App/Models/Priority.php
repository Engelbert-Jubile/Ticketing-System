<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Priority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Priority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Priority query()
 *
 * @mixin \Eloquent
 */
class Priority extends Model
{
    use HasFactory;

    /**
     * Tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'priorities'; // Sesuaikan jika nama tabel Anda berbeda

    /**
     * Kolom yang bisa diisi secara massal.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'bg_color',
        'text_color',
    ];
}
