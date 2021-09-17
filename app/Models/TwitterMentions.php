<?php

namespace App\Models;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $id_str_user
 * @property string $id_str_mention
 * @property string $mention
 * @property Carbon\Carbon $created_at
 * @property Carbon\Carbon $updated_at


 */
class TwitterMentions extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['id', 'id_str_user','id_str_mention','mention'];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = ['created_at','updated_at'];

}
