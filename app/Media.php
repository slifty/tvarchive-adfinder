<?php

namespace AdFinder;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    const STATUS_FAILED = -1;
    const STATUS_STABLE = 0;
    const STATUS_PENDING = 1;
    const STATUS_PROCESSING = 2;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'adfinder_media';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'duplitron_id',
        'archive_id',
        'status',
        'process',
        'media_path',
        'fingerprint_path',
    ];
}
