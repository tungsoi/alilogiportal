<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintComment extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = "complaint_comments";

    /**
     * Fields
     *
     * @var array
     */
    protected $fillable = [
        'complaint_id',
        'user_created_id',
        'content'
    ];
}
