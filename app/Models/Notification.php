<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCustomPrimaryKey;

class Notification extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'link',
        'is_read',
    ];

    /**
     * Get the user this notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
