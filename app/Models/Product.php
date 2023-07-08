<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title',
        'short_title',
        'sku',
        'allow_backorder',
        'stock',

        'short_description',
        'long_description',
        'sinhala_long_description',
        'moderator_description',
        'reserved_stock',
        'weight',

        'product_volume',
        'price',
        'cost',
        'commision',
        'wholesale_price',
        'visibility',

        'status',
        'reorder_level',
        'brand',
        'product_type',
        'child_products',
        'slug',

        'cover_image',
        'default_franchise',
        'priority',
        'discount_label',
        'discount_code',
        'old_price',

        'discount_valid_until',
        'featured',
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
    protected $table = 'product';

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

    //const CREATED_AT = 'creation_date';
    //const UPDATED_AT = 'updated_date';


    public function Franchise()
    {
        return $this->hasMany(franchiseProductMap::class,'product');
    }
}
