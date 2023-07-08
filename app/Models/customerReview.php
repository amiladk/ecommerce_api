<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customerReview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'customer_review',
        'product',
        'customer',
        'rating',
        'customer_name',
        'review_title',
        'email',
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
    protected $table = 'customer_review';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    public $timestamps = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    //const CREATED_AT = 'creation_date';
    const UPDATED_AT = null;
}
