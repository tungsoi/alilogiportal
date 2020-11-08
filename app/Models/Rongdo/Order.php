<?php

namespace App\Models\Rongdo;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = "rongdo";

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
        'final_total_price',
        'min_deposit',
        'deposited',
        'admin_note',
        'order_type',
        'shop_name',
        'purchase_cn_to_vn_fee',
        'is_discounted',
        'customer_note',
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
        'support_warehouse_id',
        'supporter_order_id',
        'price_weight',
        'current_rate',
        'order_at',
        'to_vn_at',
        'transport_size_product',
        'price_negotiate',
        'transport_cublic_meter',
        'deposit_default',
        'purchase_service_fee_percent',
        'user_created_id',
        'deposited_at',
        'internal_note',
        'user_created_name',
        'customer_name',
        'discount_value',
        'discount_method',
        'discount_type',
        'success_at'
    ];

    // protected $casts = [
    //     'status'                     => 'integer',
    //     'purchase_total_items_price' => 'float',
    //     'purchase_service_fee'       => 'float',
    //     'purchase_cn_transport_fee'  => 'float',
    //     'purchase_vn_transport_fee'  => 'float',
    //     'purchase_returns_fee'       => 'float',
    //     'purchase_count_fee'         => 'float',
    //     'purchase_wood_fee'          => 'float',
    //     'final_total_price'          => 'float',
    //     'min_deposit'                => 'float',
    //     'deposited'                  => 'float',
    //     'purchase_cn_to_vn_fee'      => 'float',
    //     'cn_transport_fee'           => 'float',
    //     'vn_transport_code'          => 'float',
    //     'surcharge'                  => 'float',
    //     'price_negotiate'            => 'float',
    //     'deposit_default'            => 'float',
    // ];

    // protected $dates = [
    //     'confirm_date',
    //     'cn_order_date',
    //     'order_at',
    //     'to_vn_at',
    //     'deposited_at',
    // ];

    // protected $appends = [
    // 	'order_type_text',
	// 	'final_total_price_format',
	// 	'created_at_format',
	// 	'order_at_format',
	// 	'deposited_at_format',
	// ];
    // /**
    //  * Get order address
    //  * @return \Illuminate\Database\Eloquent\Relations\HasOne
    //  */
    // public function orderAdress()
    // {
    //     return $this->hasOne(OrderAddress::class);
    // }

    // /**
    //  * Get all order groups
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  */
    // public function orderGroup()
    // {
    //     return $this->hasMany(OrderGroup::class, 'order_id');
    // }

    // /**
    //  * @param $query
    //  * @param $filter
    //  * @return mixed
    //  */
    // public function scopeFilter($query, $filter)
    // {
    //     return $filter->apply($query);
    // }

    // /**
    //  * @param $query
    //  * @return mixed
    //  */
    // public function scopeByCurrentCustomer($query)
    // {
    //     return $query->whereCustomerId(auth()->id());
    // }

    // /**
    //  * @param $query
    //  * @return mixed
    //  */
    // public function scopeGetTransportOrder($query)
    // {
    //     return $query->where('order_type', TYPE_ORDER_TRANSPORT);
    // }

    // /**
    //  * Get customer relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function customer()
    // {
    //     return $this->belongsTo(Customer::class);
    // }

    // /**
    //  * Get warehouse relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function warehouse()
    // {
    //     return $this->belongsTo(WareHouse::class, 'warehouse_id');
    // }

    // /**
    //  * Get user relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function supporter()
    // {
    //     return $this->belongsTo(User::class, 'supporter_id');
    // }

    // /**
    //  * Get user relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function orderer()
    // {
    //     return $this->belongsTo(User::class, 'supporter_order_id');
    // }

    // /**
    //  * Get user relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function warehouseSupporter()
    // {
    //     return $this->belongsTo(User::class, 'support_warehouse_id');
    // }

    // /**
    //  * Get transport order item relationship
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\hasMany
    //  */
    // public function transportOrderItem()
    // {
    //     return $this->hasMany(TransportOrderItem::class);
    // }

    // /**
    //  * Get all order items
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  */
    // public function purchaseOrderItems()
    // {
    //     return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    // }

    // public function getOrderTypeTextAttribute()
    // {
    //     return !empty($this->order_type) ? config('data.order_type')[$this->order_type] : '';
    // }

    // public function getFinalTotalPriceFormatAttribute()
    // {
    //     return number_format($this->final_total_price);
    // }

    // public function getCreatedAtFormatAttribute()
    // {
    //     return optional($this->created_at)->format('d/m/Y');
    // }

    // public function getDepositedAtFormatAttribute()
    // {
    //     return optional($this->deposited_at)->format('d/m/Y');
    // }

    // public function getOrderAtFormatAttribute()
    // {
    //     return optional($this->order_at)->format('d/m/Y');
    // }

    // public function userCreated()
    // {
    //     return $this->hasOne(User::class, 'id', 'user_created_id');
    // }
}
