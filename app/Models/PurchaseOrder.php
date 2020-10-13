<?php

namespace App\Models;

use App\Models\Alilogi\Warehouse;
use App\User;
use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{   
    use AdminBuilder;

    const STATUS_UNSENT = 1;
    const STATUS_NEW_ORDER = 2;
    const STATUS_CONFIRMED = 3;
    const STATUS_DEPOSITED_ORDERING = 4;
    const STATUS_ORDERED = 5;
    const STATUS_IN_WAREHOUSE_TQ = 6;
    const STATUS_IN_WAREHOUSE_VN = 7;
    const STATUS_TRANSPORTING = 8;
    const STATUS_SUCCESS = 9;
    const STATUS_CANCEL = 10;

    const STATUS_UNSENT_TEXT = 'Chưa gửi';
    const STATUS_NEW_ORDER_TEXT = 'Đơn hàng mới';
    const STATUS_CONFIRMED_TEXT = 'Đã xác nhận';
    const STATUS_DEPOSITED_ORDERING_TEXT = 'Đã cọc - đang đặt';
    const STATUS_ORDERED_TEXT = 'Đã đặt hàng';
    const STATUS_IN_WAREHOUSE_TQ_TEXT = 'Về kho TQ';
    const STATUS_IN_WAREHOUSE_VN_TEXT = 'Về kho VN';
    const STATUS_TRANSPORTING_TEXT = 'Đã ship VN';
    const STATUS_SUCCESS_TEXT = 'Thành công';
    const STATUS_CANCEL_TEXT = 'Đã hủy';

    const STATUS = [
        self::STATUS_UNSENT => self::STATUS_UNSENT_TEXT,
        self::STATUS_NEW_ORDER => self::STATUS_NEW_ORDER_TEXT,
        self::STATUS_CONFIRMED => self::STATUS_CONFIRMED_TEXT,
        self::STATUS_DEPOSITED_ORDERING => self::STATUS_DEPOSITED_ORDERING_TEXT,
        self::STATUS_ORDERED => self::STATUS_ORDERED_TEXT,
        self::STATUS_IN_WAREHOUSE_TQ => self::STATUS_IN_WAREHOUSE_TQ_TEXT,
        self::STATUS_IN_WAREHOUSE_VN => self::STATUS_IN_WAREHOUSE_VN_TEXT,
        self::STATUS_TRANSPORTING => self::STATUS_TRANSPORTING_TEXT,
        self::STATUS_SUCCESS => self::STATUS_SUCCESS_TEXT,
        self::STATUS_CANCEL => self::STATUS_CANCEL_TEXT,
    ];
    
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "purchase_orders";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'supporter_id',
        'transport_receive_type',
        'purchase_cn_transport_code',
        'purchase_vn_transport_code',
        'purchase_total_items_price',
        'status',
        'confirm_date',
        'purchase_service_fee',
        'purchase_cn_transport_fee',
        'purchase_vn_transport_fee',
        'purchase_cn_to_vn_fee',
        'final_total_price',
        'min_deposit',
        'deposited',
        'admin_note',
        'customer_note',
        'order_type',
        'shop_name',
        'surcharge',  // phụ phí
        'warehouse_id',
        'is_count',
        'is_close_wood',
        'transport_price_service',
        'transport_kg',
        'transport_volume',
        'transport_pay_type',
        'transport_advance_drag',
        'transport_vn_code',
        'current_rate',
        'order_at',
        'to_vn_at',
        'transport_size_product',
        'support_warehouse_id',
        'supporter_order_id',
        'price_weight',
        'price_negotiate',
        'deposit_default',
        'transport_cublic_meter',
        'purchase_service_fee_percent',
        'user_created_id',
        'payment_customer_id',
        'deposited_at',
        'internal_note',
        'user_created_name',
        'customer_name',
        'discount_value',
        'discount_method',
        'discount_type',
        'success_at',
        'is_discounted',
        'transport_customer_id'
    ];

    public function customer() {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }

    public function warehouse() {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }
}
