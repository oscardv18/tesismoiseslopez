<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['date', 'client_id', 'assigned_price'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    public function materialOrder(): HasMany
    {
        return $this->hasMany(MaterialOrder::class);
    }
}
