<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone_primary', 'phone_2', 'phone_3', 'phone_4', 'address'];

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function scopePhone($query, string $phone)
    {
        return $query->where(function ($q) use ($phone) {
            $q->where('phone_primary', $phone)
                ->orWhere('phone_2', $phone)
                ->orWhere('phone_3', $phone)
                ->orWhere('phone_4', $phone);
        });
    }
}
