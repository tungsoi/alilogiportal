<?php

namespace App\Models;

use App\Models\Alilogi\Order;
use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use AdminBuilder;

    const STATUS_PURCHASE_ITEM_NOT_ORDER = 0;
    const STATUS_PURCHASE_ITEM_ORDERED = 1;
    const STATUS_PURCHASE_WAREHOUSE_TQ = 2;
    const STATUS_PURCHASE_WAREHOUSE_VN = 3;
    const STATUS_PURCHASE_OUT_OF_STOCK = 4;

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'purchase_order_items';

    /**
     * Fields
     *
     * @var array
     */
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
        'order_group_id',
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

    protected $casts = [
        'qty' => 'integer',
        'qty_reality' => 'integer',
        'price' => 'float',
        'current_rate' => 'float'
    ];

    protected $appends = [
        'array_property',
        'price_vn'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'order_id');
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
