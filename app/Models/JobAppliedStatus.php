<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAppliedStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_applied_status';

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
        'job_id',
        'user_id',
        'status',
        'stage',
        'inserted_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inserted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Application stages
     */
    const STAGES = [
       
        'job_selection' => 0,
        'personal_details' => 1,
        'qualification' => 2,
        'employment' => 3,
        'file_upload' => 4,
        'application_summary' => 5,
        'payment' => 6,
        'print_application' => 7
    ];

    /**
     * Application statuses
     */
    const STATUSES = [
        'draft' => 'draft',
        'in_progress' => 'in_progress',
        'submitted' => 'submitted',
        'under_review' => 'under_review',
        'approved' => 'approved',
        'rejected' => 'rejected'
    ];

    /**
     * Get stage name from number
     */
    public static function getStageName($stageNumber)
    {
        $stages = array_flip(self::STAGES);
        return $stages[$stageNumber] ?? 'unknown';
    }

    /**
     * Get next stage number
     */
    public static function getNextStage($currentStage)
    {
        return min($currentStage + 1, 8); // Max stage is 8 (print_application)
    }

    /**
     * Check if application is completed
     */
    public function isCompleted()
    {
        return $this->stage >= self::STAGES['print_application'];
    }

    /**
     * Get current stage name
     */
    public function getCurrentStageName()
    {
        return self::getStageName($this->stage);
    }

    /**
     * Get next stage name
     */
    public function getNextStageName()
    {
        $nextStage = self::getNextStage($this->stage);
        return self::getStageName($nextStage);
    }

    /**
     * Update application stage
     */
    public function updateStage($newStage)
    {
        $this->stage = max($this->stage, $newStage); // Only move forward
        $this->updated_at = now();
        
        // Update status based on stage
        if ($newStage >= self::STAGES['print_application']) {
            $this->status = self::STATUSES['submitted'];
        } elseif ($newStage >= self::STAGES['payment']) {
            $this->status = self::STATUSES['under_review'];
        } else {
            $this->status = self::STATUSES['in_progress'];
        }
        
        $this->save();
        return $this;
    }

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