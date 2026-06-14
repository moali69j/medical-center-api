<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'quantity', 'unit', 'threshold', 'is_measurable', 'cost_price'
    ];

    // لضمان تحويل الحقول تلقائياً للأنواع الصحيحة ومنع كراش الفرونت إند
    protected $casts = [
        'is_measurable' => 'boolean',
        'quantity' => 'float',
        'threshold' => 'float',
        'cost_price' => 'float',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_materials', 'inventory_item_id', 'service_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}