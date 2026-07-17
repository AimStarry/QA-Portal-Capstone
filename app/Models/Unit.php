<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCustomPrimaryKey;

class Unit extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'name',
        'code',
        'logo',
    ];
}
