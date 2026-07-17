<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomPrimaryKey;

class AccreditingBody extends Model
{
    use HasCustomPrimaryKey;
    protected $table = 'accrediting_bodies';
    protected $primaryKey = 'accrediting_body_id';

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'areas',
    ];

    protected $casts = [
        'areas' => 'array',
    ];
}
