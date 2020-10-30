<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRecharge extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "order_recharges";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'user_id_created',
        'money',
        'type_recharge',
        'content'
    ];

    const RECHARGE = [
        self::RECHARGE_MONEY    =>  'Nạp tiền mặt',
        self::RECHARGE_BANK     =>  'Nạp tiền chuyển khoản',
        self::DEDUCTION        =>  'Trừ tiền',
        self::PAYMENT => 'Thanh toán đơn hàng'
    ];

    const DEDUCTION = 2;
    const RECHARGE_MONEY = 0;
    const RECHARGE_BANK = 1;
    const PAYMENT = 4;

    const COLOR = [
        'warning',
        'success',
        'primary',
        'default',
        'danger'
    ];

    public function customer() {
        return $this->hasOne('App\User', 'id', 'customer_id');
    }

    public function userCreated() {
        return $this->hasOne('App\User', 'id', 'user_id_created');
    }
}
