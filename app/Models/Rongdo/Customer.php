<?php

namespace App\Models\Rongdo;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $connection = 'rongdo';

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'is_active',
        'remember_token',
        'symbol_name',
        'service_percent',
        'price_weight_default',
        'supporter_id',
        'ware_house_id',
        'orderer_id'
    ];
}