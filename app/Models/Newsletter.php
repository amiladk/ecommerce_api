<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

        /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'email',
        'franchise',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */


    /*protected $hidden = [
        'password',
    ];*/

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'newsletter';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

}
