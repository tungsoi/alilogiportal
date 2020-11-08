<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "complaints";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'order_number',
        'cn_code',
        'image',
        'reason',
        'solution',
        'sale_staff_id',
        'order_staff_id'
    ];

    public function saleStaff()
    {
        # code...

        return $this->hasOne(User::class, 'id', 'sale_staff_id');
    }

    public function orderStaff()
    {
        # code...

        return $this->hasOne(User::class, 'id', 'order_staff_id');
    }
}
