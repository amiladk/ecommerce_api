<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\productAttributeValue;

class productAttribute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'slug',
        'name'
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
    protected $table = 'product_attribute';

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

    public function value()
    {
        return $this->hasMany(productAttributeValue::class,'product_attribute','id');
    }
}
