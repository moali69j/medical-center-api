<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    // السماح بتعبئة هذه الحقول بالجملة (Mass Assignment)
    protected $fillable = [
        'full_name', 'phone', 'national_id', 'address', 
        'blood_type', 'chronic_diseases', 'current_medications', 'extra_notes'
    ];

    public function caseReports(): HasMany
    {
        return $this->hasMany(CaseReport::class);
    }
}