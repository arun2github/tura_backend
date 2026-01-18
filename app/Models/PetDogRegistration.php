<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetDogRegistration extends Model
{
    use HasFactory;

    protected $table = 'pet_dog_registrations';

    protected $fillable = [
        'application_id',
        'owner_name',
        'identity_proof_type',
        'identity_proof_number',
        'identity_proof_document',
        'phone_number',
        'email',
        'dog_name',
        'dog_breed',
        'address',
        'ward_no',
        'district',
        'pincode',
        'registration_date',
        'vaccination_card_document',
        'dog_photo_document',
        'registration_fee',
        'metal_tag_fee',
        'total_fee',
        'payment_status',
        'status',
        'registration_certificate_path',
        'metal_tag_number',
        'rejection_reason',
        'approved_at',
        'user_id',
        'approved_by'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'registration_date' => 'date',
        'registration_fee' => 'decimal:2',
        'metal_tag_fee' => 'decimal:2',
        'total_fee' => 'decimal:2',
    ];

    // Generate unique application ID
    public static function generateApplicationId()
    {
        $year = date('Y');
        $prefix = "PDR-{$year}-";
        
        $latest = static::where('application_id', 'like', $prefix . '%')
                       ->orderBy('application_id', 'desc')
                       ->first();
        
        if ($latest) {
            $lastNumber = (int) substr($latest->application_id, strlen($prefix));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . $nextNumber;
    }

    // Check if dog is old enough (3+ months)
    public function isDogOldEnough()
    {
        // This would need dog birth date, for now return true
        return true;
    }

    // Generate metal tag number
    public static function generateMetalTagNumber()
    {
        $year = date('Y');
        $prefix = "TMB-DOG-{$year}-";
        
        $latest = static::where('metal_tag_number', 'like', $prefix . '%')
                       ->orderBy('metal_tag_number', 'desc')
                       ->first();
        
        if ($latest) {
            $lastNumber = (int) substr($latest->metal_tag_number, strlen($prefix));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
        
        return $prefix . $nextNumber;
    }

    // Scope for pending applications
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope for approved applications
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Scope for paid applications
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }
}
