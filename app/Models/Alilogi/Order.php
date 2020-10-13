<?php

namespace App\Models\Alilogi;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{   
    protected $connection = 'alilogi';
    
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "orders";

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

    public function items() {
        return $this->hasMany('App\Models\TransportOrderItem', 'order_id', 'id');
    }

    public function paymentTypeText($order) {
        $item = $order->items->first();
        switch ($item->payment_type)
        {
            case self::KG:
                return self::KG_TEXT;
            case self::V:
                return self::V_TEXT;
            case self::M3:
                return self::M3_TEXT;
        }
    }

    public static function getPriceService($order, $paymentType = "") {
        if ($order->items->count() > 0) {
            return $order->items->where('payment_type', $paymentType)->first() 
                ? $order->items->where('payment_type', $paymentType)->first()->price_service 
                : 0;
        }

        return 0;
    }

    const KG = 1;
    const V = 0;
    const M3 = -1;
    const KG_TEXT = 'Khối lượng';
    const V_TEXT = 'Thể tích';
    const M3_TEXT = 'Mét khối';

    public function getSumKg() {
        return str_replace('.00', '', $this->items->where('payment_type', self::KG)->sum('kg'));
    }

    public function getSumKgDiscount() {
        return $this->discount_type == 1 ?  (- (int) $this->discount_value) : (int) $this->discount_value;
    }

    public function getSumKgAfterDiscount() {
        return $this->getSumKg() + $this->getSumKgDiscount();
    }

    public function getSumVolume() {
        return $this->items->where('payment_type', self::V)->sum('volume');
    }

    public function getSumCublicMeter() {
        return $this->items->where('payment_type', self::M3)->sum('cublic_meter');
    }

    public function userCreated() {
        return $this->hasOne('App\User', 'id', 'user_created_id');
    }

    public function getSumItemAdvanceDrag() {
        return number_format($this->items->sum('advance_drag'), 2);
    }

    public function getTotalPriceOrder() {
        $priceKg = $this->items->where('payment_type', self::KG)->first();
        $totalKg = $priceKg ? $priceKg->price_service * $this->getSumKg() : 0;


        $priceV = $this->items->where('payment_type', self::V)->first();
        $totalV = $priceV ? $priceV->price_service * $this->getSumVolume() : 0;


        $priceM3 = $this->items->where('payment_type', self::M3)->first();
        $totalM3 = $priceM3 ? $priceM3->price_service * $this->getSumCublicMeter() : 0;

        return (int) ($totalKg + $totalV + $totalM3);
    }

    public function getDetailSumKg() {
        return $this->getSumKgDiscount() > 0 ? (" + " . $this->getSumKgDiscount()) : $this->getSumKgDiscount();
    }

    public function getDetailMoneySumKg() {
        return number_format($this->getSumKg() * $this->getPriceService($this, self::KG))." + ".number_format($this->getSumKgDiscount() * $this->getPriceService($this, self::KG));
    }

    public function paymentCustomer() {
        return $this->hasOne('App\User', 'id', 'payment_customer_id');
    }

    public function transportCustomer() {
        return $this->hasOne('App\User', 'id', 'transport_customer_id');
    }
}
