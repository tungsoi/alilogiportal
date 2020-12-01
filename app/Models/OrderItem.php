<?php

namespace App\Models;

use App\Models\Alilogi\Order;
use App\User;
use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use AdminBuilder;

    const PRODUCT_NOT_IN_CART   = 10;
    const STATUS_PURCHASE_ITEM_NOT_ORDER = 0;
    const STATUS_PURCHASE_ITEM_ORDERED = 1;
    const STATUS_PURCHASE_WAREHOUSE_TQ = 2;
    const STATUS_PURCHASE_WAREHOUSE_VN = 3;
    const STATUS_PURCHASE_OUT_OF_STOCK = 4;

    const PRODUCT_NOT_IN_CART_TEXT = 'Chưa lên đơn';
    const STATUS_PURCHASE_ITEM_NOT_ORDER_TEXT = 'Chưa đặt hàng';
    const STATUS_PURCHASE_ITEM_ORDERED_TEXT = 'Đã đặt hàng';
    const STATUS_PURCHASE_WAREHOUSE_TQ_TEXT = 'Đã về Trung Quốc';
    const STATUS_PURCHASE_WAREHOUSE_VN_TEXT = 'Đã về kho Việt Nam';
    const STATUS_PURCHASE_OUT_OF_STOCK_TEXT = 'Hết hàng';

    const STATUS = [
        self::PRODUCT_NOT_IN_CART => self::PRODUCT_NOT_IN_CART_TEXT,
        self::STATUS_PURCHASE_ITEM_NOT_ORDER    =>  self::STATUS_PURCHASE_ITEM_NOT_ORDER_TEXT,
        self::STATUS_PURCHASE_ITEM_ORDERED      =>  self::STATUS_PURCHASE_ITEM_ORDERED_TEXT,
        self::STATUS_PURCHASE_WAREHOUSE_VN      =>  self::STATUS_PURCHASE_WAREHOUSE_VN_TEXT,
        self::STATUS_PURCHASE_OUT_OF_STOCK      =>  self::STATUS_PURCHASE_OUT_OF_STOCK_TEXT
    ];

    const LABEL = [
        'default',
        'primary',
        'success',
        'success',
        'danger',
        'default',
        'default',
        'default',
        'default',
        'default',
        'warning',
    ];
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'items';

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
        'cn_code',
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
        'order_id',
        'customer_id',
        'order_at'
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

    public function customer()
    {
        # code...
        return $this->hasOne(User::class, 'id', 'customer_id');
    }
}
