<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $id_str_user
 * @property string $id_str_message
 * @property string $type
 * @property string $created_timestamp
 * @property string $message
 * @property Carbon\Carbon $created_at
 * @property Carbon\Carbon $updated_at


 */
class TwitterMessages extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'id_str_user','id_str_message', 'type','created_timestamp','message'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = ['created_at','updated_at'];

}
