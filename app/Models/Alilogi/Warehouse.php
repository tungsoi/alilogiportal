<?php

namespace App\Models\Alilogi;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $connection = 'alilogi';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = "ware_houses";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = ['name', 'address', 'is_active'];
}
