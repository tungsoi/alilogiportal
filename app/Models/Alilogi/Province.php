<?php

namespace App\Models\Alilogi;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{

    protected $connection = 'alilogi';

    protected $table = 'provinces';

    protected $fillable = [
        'province_id',
        'name',
        'type'
    ];
}
