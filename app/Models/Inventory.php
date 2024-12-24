<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    protected $fillable = ['material_id', 'current_quantity', 'min_quantity'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
