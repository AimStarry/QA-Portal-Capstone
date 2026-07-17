<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasCustomPrimaryKey;

class College extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'college_id';

    protected $fillable = [
        'name',
        'code',
        'former_name',
        'logo',
    ];

    /**
     * Get the programs offered under this school/college.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'college_id', 'college_id');
    }
}