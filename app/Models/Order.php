<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'search_code',
        'franchise_shipping_cost',
        'total_amount',
        'total_discount',
        'grand_total',
        'total_commision',
        'service_charge',
        'address',
        'resipient',
        'private_reference',
        'phone_one',
        'phone_two',
        'item_description',
        'customer_notes',
        'delivery_notes',
        'call_attempts',
        'created_by',
        'confirmed_by',
        'courier_reference',
        'total_weight',
        'payment_type',
        'courier',
        'city',
        'franchise',
        'order_status',
        'cod_recieved',
        'visible_to',
        'return_informed',
        'dispatched_date',
        'order_type',
        'damaged_return',
        'is_urgent',
        'franchise_settled',
        'customer',
        'email'
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
    protected $table = 'order';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    // public $timestamps = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'last_modified';


    public function courierdata()
    {
        return $this->hasOne(Courier::class,'id', 'courier');
    }

    public function franchisedata()
    {
        return $this->hasOne(Franchise::class,'id','franchise');
    }

    public function citydata()
    {
        return $this->hasOne(City::class,'id','city');
    }


}
