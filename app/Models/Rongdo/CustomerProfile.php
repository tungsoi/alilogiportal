<?php

namespace App\Models\Rongdo;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomerProfile
 *
 * @package App\Models
 */
class CustomerProfile extends Model
{
    protected $connection = "rongdo";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id', 'gender', 'birthday', 'bank_account', 'bank_name', 'bank_account_owner', 'business_sector', 'remaining_amount', 'avatar_url', 'address'
    ];

    protected $casts = [
        'remaining_amount' => 'float'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
