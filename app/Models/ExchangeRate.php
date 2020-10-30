<?php

namespace App\Models;

use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use AdminBuilder;

    /**
     * Table name
     *
     * @var string
     */
    protected $table = "exchange_rates";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'vnd',
        'ndt'
    ];
}
