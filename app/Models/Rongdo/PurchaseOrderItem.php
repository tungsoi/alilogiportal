<?php

namespace App\Models\Rongdo;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $connection = "rongdo";

    protected $fillable = [
        'product_image',
        'product_name',
        'product_link',
        'product_id',
        'property',
        'qty',
        'price',
        'customer_note',
        'admin_note',
        'price_range',
        'cn_transport_code',
        'cn_order_number',
        'status',
        'order_id',
        'current_rate',
        'purchase_cn_transport_fee',
        'product_size',
        'product_color',
        'qty_reality',
        'weight',
        'shop_name',
        'type',
        'internal_note',
        'weight_date',
    ];

    // protected $casts = [
    //     'qty' => 'integer',
    //     'qty_reality' => 'integer',
    //     'price' => 'float',
    //     'current_rate' => 'float'
    // ];

    // protected $appends = [
    //     'array_property',
    //     'price_vn'
    // ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * @return float|int
     */
    public function totalPrice()
    {
        return $this->price_vn * $this->qty_reality;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderGroup()
    {
        return $this->belongsTo(OrderGroup::class, 'order_group_id');
    }

    public function getArrayPropertyAttribute()
    {
        return json_decode($this->property);
    }

    /**
     * @return float
     */
    public function getPriceVnAttribute()
    {
        return (float)($this->current_rate * $this->price);
    }
}
