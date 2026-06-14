<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. استيراد الميزة
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use SoftDeletes; // 2. تفعيل الميزة داخل الموديل

    protected $fillable = ['name', 'credits_required'];

    protected $dates = ['deleted_at'];
    public function caseReports(): BelongsToMany
    {
        return $this->belongsToMany(CaseReport::class);
    }
    public function materials(): BelongsToMany
{
    return $this->belongsToMany(InventoryItem::class, 'service_materials')
                ->withPivot('quantity')
                ->withTimestamps();
}
}