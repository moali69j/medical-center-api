<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['name', 'quantity', 'unit', 'threshold', 'is_measurable'];

    // العلاقة مع الحالات التي استهلكت هذه المادة
    public function caseReports(): BelongsToMany
    {
        return $this->belongsToMany(CaseReport::class, 'case_report_item')
                    ->withPivot('used_quantity')
                    ->withTimestamps();
    }
}