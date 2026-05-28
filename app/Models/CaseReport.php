<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CaseReport extends Model
{
    protected $fillable = [
        'patient_id',
        'case_type',
        'blood_pressure',
        'sugar_level',
        'oxygen_saturation',
        'credit_price_at_time',
        'total_paid',
        'total_cost_of_materials',
        'center_share',
        'staff_share',
        'visit_notes'
    ];

    /**
     * علاقة الحالة بالمرضى (كل حالة تنتمي لمريض واحد)
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * علاقة الحالة بالخدمات (الحالة قد تضم عدة خدمات)
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'case_report_service')->withTimestamps();
    }

    /**
     * العلاقة التي كانت مفقودة:
     * علاقة الحالة بالمواد الإضافية المستهلكة خارج الخدمة
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'case_report_item')
                    ->withPivot('used_quantity')
                    ->withTimestamps();
    }
}