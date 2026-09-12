<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCustomPrimaryKey;

class Accreditation extends Model
{
    use HasFactory, HasCustomPrimaryKey;

    protected $primaryKey = 'accreditation_id';

    protected $fillable = [
        'program_id',
        'accrediting_body',
        'type',
        'level_or_tier',
        'last_visit',
        'expiry_date',
        'status',
        'certificate_link',
        'certificate_file',
    ];

    protected $appends = [
        'certificate_url',
        'is_certificate_file',
    ];

    /**
     * Get the resolved certificate URL (file asset URL or cloud link).
     */
    public function getCertificateUrlAttribute(): ?string
    {
        if ($this->certificate_file) {
            return asset('storage/' . $this->certificate_file);
        }
        return $this->certificate_link;
    }

    /**
     * Check if the certificate is a locally stored file (PDF/Image).
     */
    public function getIsCertificateFileAttribute(): bool
    {
        return !empty($this->certificate_file);
    }

    protected $casts = [
        'last_visit' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the program that owns the accreditation.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }
}
