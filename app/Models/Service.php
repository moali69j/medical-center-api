<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = ['name', 'credits_required'];

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