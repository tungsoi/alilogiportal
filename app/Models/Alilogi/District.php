<?php

namespace App\Models\Alilogi;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $connection = 'alilogi';

    protected $table = 'districts';

    protected $fillable = [
        'district_id',
        'name',
        'type',
        'location',
        'province_id'
    ];
}
