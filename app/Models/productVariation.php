<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\productAttributeValue;
use App\Models\variationAttributeMap;

class productVariation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'product',
        'title',
        'price',
        'cost',
        'sku',
        'stock',
        'image',
        'is_active'
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
    protected $table = 'product_variation';

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

    //     public function attribute()
    // {
    //     return $this->hasOneThrough(variationAttributeMap::class,productAttributeValue::class,'id','product_variation','id', 'attribute_value');
    // }

    public function attributemap()
    {
        return $this->hasMany(variationAttributeMap::class,'product_variation','id');
    }
}
