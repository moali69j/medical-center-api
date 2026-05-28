<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    // تأكد من وجود الحقل هنا في السطر الأخير
    protected $fillable = [
        'full_name', 
        'phone', 
        'national_id', 
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