<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\LogsModelActivity;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory, LogsModelActivity;

    protected $fillable = [
        'name',
        'domain',
        'slug',
        'primary_color',
        'secondary_color',
        'accent_color',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Vendor $vendor) {
            // Detach users on delete
            $vendor->users()->detach();
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
