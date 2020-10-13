<?php

namespace App\Models\Alilogi;

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

    protected $connection = 'alilogi';

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
}
