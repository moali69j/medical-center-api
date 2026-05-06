<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CaseReport extends Model
{
    protected $fillable = [
        'patient_id', 'case_type', 'blood_pressure', 
        'sugar_level', 'oxygen_saturation', 
        'credit_price_at_time', 'total_paid', 'case_notes'
    ];

    // العلاقة مع المريض
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // الخدمات التي قُدمت في هذه الحالة
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    // المواد الإضافية التي استُهلكت في هذه الحالة
    public function extraItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'case_report_item')
                    ->withPivot('used_quantity')
                    ->withTimestamps();
    }
}