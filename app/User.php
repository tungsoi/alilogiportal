<?php

namespace App;

use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Encore\Admin\Auth\Database\HasPermissions;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable, AdminBuilder, HasPermissions;

    const STATUS = [
        0   =>  'Khoá',
        1   =>  'Hoạt động'
    ];

    protected $connection = "alilogi";

    /**
     * Table name
     *
     * @var string
     */
    protected $table = "admin_users";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'name',
        'avatar',
        'email',
        'phone_number',
        'wallet',
        'address',
        'is_customer',
        'symbol_name',
        'ware_house_id',
        'is_active',
        'password',
        'note',
        'wallet_order',
        'province_id',
        'district_id',
        'staff_sale_id',
        'customer_percent_service'
    ];

    /**
     * Hidden column
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = []){
        $connection = $this->connection ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
    }

    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */
    public function getAvatarAttribute($avatar){
        if(url()->isValidUrl($avatar)){
            return $avatar;
        }

        $disk = config('admin.upload.disk');

        if($avatar && array_key_exists($disk, config('filesystems.disks'))){
            return Storage::disk(config('admin.upload.disk'))->url($avatar);
        }

        $default = config('admin.default_avatar') ?: '/bamboo-admin/AdminLTE/dist/img/user2-160x160.jpg';

        return admin_asset($default);
    }

    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany{
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany{
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }

    public function getAccessToken(){
        $token = json_decode($this->token, true);
        if($token && isset($token['access_token'])){
            return $token['access_token'];
        }
        return '';
    }

    public function warehouse() {
        return $this->hasOne('App\Models\Alilogi\Warehouse', 'id', 'ware_house_id');
    }

    // public function getSumWeightByCustomerSymbolName($symbolName) {
    //     $transportCustomer = RongDoUser::where('name', $symbolName)->first();
    //     if ($transportCustomer != "") {
    //         return TransportOrderItem::where('transport_customer_id', $transportCustomer->id)->sum('kg');
    //     }

    //     return 0;
    // }
    
    // public function getSumCublicMeterByCustomerSymbolName($symbolName) {
    //     $items = RongDoUser::where('name', $symbolName)->get();
    //     if ($items != "") {
    //         $sum = 0;
    //         foreach ($items as $item) {
    //             $sum += !is_null($item->cublic_meter) ? $item->cublic_meter : round( ( $item->product_width * $item->product_height * $item->product_length)/1000000, 4 );
    //         }
    //         return $sum;
    //     }
    //     return 0;
    // }

    // public function getSumMoneyPayment($symbolName) {
    //     $transportCustomer = RongDoUser::where('name', $symbolName)->first();
    //     if ($transportCustomer != "") {
    //         return TransportOrderItem::where('transport_customer_id', $transportCustomer->id)->where('is_payment', 1)->sum('total_price');
    //     }

    //     return 0;
    // }

    public static function customers() {
        return self::where('is_customer', 1)->get();
    }

    public function saleStaff()
    {
        # code...
        return $this->hasOne(User::class, 'id', 'staff_sale_id');
    }
}
