<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobEmploymentDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_employment_details';

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
        'user_id',
        'occupation_status',
        'is_government_employee',
        'state_where_employed',
        'appointment_type',
        'name_of_organization',
        'designation',
        'date_of_joining',
        'duration_in_months',
        'job_description',
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
        'is_government_employee' => 'boolean',
        'duration_in_months' => 'integer',
        'date_of_joining' => 'date',
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