<?php

namespace App\Models\Alilogi;

use Carbon\Carbon;
use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class TransportOrderItem extends Model
{   
    use AdminBuilder;

    protected $connection = 'alilogi';
    
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "transport_order_items";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'cn_code',
        'price_service',
        'kg',
        'volume',
        'advance_drag',
        'total_price',
        'warehouse_cn',
        'warehouse_cn_date',
        'warehouse_vn',
        'warehouse_vn_date',
        'transporting_vn',
        'transporting_vn_date',
        'size_product',
        'product_width',
        'product_height',
        'product_length',
        'transport_customer_id',
        'is_payment',
        'user_id_updated',
        'payment_type',
        'cublic_meter',
        'note',
        'payment_date',
        'customer_name'
    ];

    const KG = 1;
    const V = 0;
    const M3 = -1;
    const KG_TEXT = 'Khối lượng';
    const V_TEXT = 'Thể tích';
    const M3_TEXT = 'Mét khối';

    const STATUS = [
        self::IS_PAYMENT   =>  'Đã thanh toán',
        self::NOT_PAYMENT_VN   =>  'Chưa thanh toán Hà Nội nhận',
        self::CN_REV   =>  'Trung Quốc nhận',
        self::VN_REV   =>  'Hà Nội nhận'
    ];

    const IS_PAYMENT = 0;
    const NOT_PAYMENT_VN = 1;
    const CN_REV = 2;
    const VN_REV = 3;

    /**
     * Relationship with one customer
     *
     * @return void
     */
    public function customer() {
        return $this->hasOne('App\User', 'id', 'transport_customer_id');
    }

    /**
     * Relationship with one user create
     *
     * @return void
     */
    public function userUpdated() {
        return $this->hasOne('App\User', 'id', 'user_id_updated');
    }

    /**
     * Get payment type text
     *
     * @return void
     */
    public function paymentTypeText() {
        switch ($this->payment_type)
        {
            case self::KG:
                return self::KG_TEXT;
            case self::V:
                return self::V_TEXT;
            case self::M3:
                return self::M3_TEXT;
        }
    }

     /**
     * Relationship with one order
     *
     * @return void
     */
    public function order() {
        return $this->hasOne('App\Models\Order', 'id', 'order_id');
    }

    
    /**
     * Calculate volume of this order item by use w, h, l
     *
     * @param integer $productWidth
     * @param integer $productHeight
     * @param integer $productLength
     * @return void
     */
    public static function calculateVolume($productWidth = 0, $productHeight = 0, $productLength = 0) {
        return number_format( ( $productWidth * $productHeight * $productLength)/6000, 2 );
    }

    /**
     * Calculate culic meter of this order item by use w, h, l
     *
     * @param integer $productWidth
     * @param integer $productHeight
     * @param integer $productLength
     * @return void
     */
    public static function calculateCublicMeter($productWidth = 0, $productHeight = 0, $productLength = 0) {
        return number_format( ( $productWidth * $productHeight * $productLength)/1000000, 3 );
    }
}
