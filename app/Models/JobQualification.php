<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobQualification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_qualification';

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
        'additional_qualification',
        'additional__qualification_details',
        'institution_name',
        'board_university',
        'examination_passed',
        'honors_specialization',
        'general_elective_subjects',
        'year_of_passing',
        'month_of_passing',
        'division',
        'percentage_obtained',
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
        'year_of_passing' => 'integer',
        'percentage_obtained' => 'decimal:2',
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