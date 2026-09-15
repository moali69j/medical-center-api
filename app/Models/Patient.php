<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'full_name', 
        'phone', 
        'national_id',
        'age', // 👈 أضفنا حقل العمر هنا ليكون قابلاً للتعبئة الجماعية
        'address', 
        'blood_type', 
        'chronic_diseases', 
        'current_medications', 
        'permanent_medical_notes' 
    ];

    public function caseReports(): HasMany
    {
        return $this->hasMany(CaseReport::class)->latest();
    }
}