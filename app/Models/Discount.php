<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'discount_code',
        'description',
        'valid_until',
        'amount',
        'max_count',
        'current_count'
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
    protected $table = 'discount';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    public $timestamps = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    // const CREATED_AT = 'created_date';
    // const UPDATED_AT = 'last_modified';

    public function product()
    {
        return $this->hasOne(discountProductMap::class,'discount_id', 'id');
    }

    public function getDiscountProductMap()
    {
        return $this->hasMany(discountProductMap::class,'discount_id', 'id');
    }
}
