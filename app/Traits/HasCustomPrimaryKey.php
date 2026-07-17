<?php

namespace App\Traits;

trait HasCustomPrimaryKey
{
    /**
     * Get the value of the model's primary key via the 'id' attribute for backward compatibility.
     */
    public function getIdAttribute()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Set the value of the model's primary key via the 'id' attribute for backward compatibility.
     */
    public function setIdAttribute($value)
    {
        $this->setAttribute($this->primaryKey, $value);
    }
}
