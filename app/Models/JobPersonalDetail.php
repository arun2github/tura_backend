<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPersonalDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_personal_details';

    /**
     * Disable Laravel's default timestamp handling since we use custom timestamp columns
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salutation',
        'full_name',
        'date_of_birth',
        'marital_status',
        'gender',
        'category',
        'caste',
        'religion',
        'identification_mark',
        'permanent_address1',
        'permanent_address2',
        'permanent_landmark',
        'permanent_village',
        'permanent_state',
        'permanent_district',
        'permanent_block',
        'permanent_pincode',
        'present_address1',
        'present_address2',
        'present_landmark',
        'present_village',
        'present_state',
        'present_district',
        'present_block',
        'present_pincode',
        'user_id',
        'job_id',
        'inserted_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'datetime',
        'inserted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with User model
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with JobPosting model
     */
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }
}