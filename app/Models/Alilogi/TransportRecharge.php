<?php

namespace App\Models\Alilogi;

use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class TransportRecharge extends Model
{
    use AdminBuilder;

    protected $connection = 'alilogi';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = "transport_recharges";

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
        'content',
        'order_type' // 1: order, 2: transport
    ];

    const TYPE_ORDER = 1;
    const TYPE_TRANSPORT = 2;
    
    const RECHARGE = [
        self::RECHARGE_MONEY    =>  'Nạp tiền mặt',
        self::RECHARGE_BANK     =>  'Nạp tiền chuyển khoản',
        self::REFUND        =>  'Hoàn tiền',
        self::DEDUCTION     =>  'Trừ tiền',
    ];

    const ALL_RECHARGE = [
        self::RECHARGE_MONEY    =>  'Nạp tiền mặt',
        self::RECHARGE_BANK     =>  'Nạp tiền chuyển khoản',
        self::REFUND        =>  'Hoàn tiền',
        self::DEDUCTION     =>  'Trừ tiền',
        self::PAYMENT     =>  self::RECHARGE_PAYMENT,
    ];

    const RECHARE_SEARCH = [
        self::RECHARGE_MONEY    =>  'Nạp tiền mặt',
        self::RECHARGE_BANK     =>  'Nạp tiền chuyển khoản',
    ];

    const REFUND = 2;
    const DEDUCTION = 3;
    const RECHARGE_MONEY = 0;
    const RECHARGE_BANK = 1;
    const PAYMENT = 4;
    const RECHARGE_PAYMENT = 'Thanh toán đơn hàng';
    const DEPOSITE_ORDER = 5;
    const DEPOSITE_ORDER_TEXT = "Đặt cọc đơn hàng mua hộ";
    const PAYMENT_ORDER = 6;
    const PAYMENT_ORDER_TEXT = "Thanh toán đơn hàng mua hộ";

    const UP = [self::RECHARGE_MONEY, self::RECHARGE_BANK];
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
