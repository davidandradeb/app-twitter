<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $id_str
 * @property string $name
 * @property string $screen_name
 * @property string $profile_image_url
 * @property Carbon\Carbon $created_at
 * @property Carbon\Carbon $updated_at


 */
class TwitterUsers extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'id_str', 'name','screen_name','profile_image_url'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = ['created_at','updated_at'];

}
