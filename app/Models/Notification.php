<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCustomPrimaryKey;

class Notification extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'type',
        'message',
        'link',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
