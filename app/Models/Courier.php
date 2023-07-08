<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'active',
        'label',
        'label_colour',
        'phone_one',
        'phone_two',
        'email',
        'tracking_url',
        'waybill_prefix',
        'last_waybill',
        'stop_waybill',
        'get_order_criteria',
        'get_order_criteria_2',

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
    protected $table = 'courier';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    public $timestamps = false;

    // public $timestamps = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    // const CREATED_AT = 'created_date';
    // const UPDATED_AT = 'last_modified';

}
