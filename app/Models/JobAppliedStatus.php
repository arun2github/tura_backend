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
        'application_id',
        'email',
        'payment_amount',
        'payment_status',
        'email_sent',
        'priority',
        'category_applied',
        'payment_transaction_id',
        'payment_date',
        'email_sent_at',
        'job_applied_email_sent',
        'payment_confirmation_email_sent',
        'inserted_at',
        'updated_at',
        'created_at'
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
     * Boot method to automatically generate application_id when creating a new record
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->application_id && $model->job_id) {
                // Use the generateApplicationId from JobController
                $controller = new \App\Http\Controllers\JobController();
                $model->application_id = $controller->generateApplicationId($model->job_id);
            }
        });
    }

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

    /**
     * Generate unique application ID based on job title and timestamp
     */
    public function generateApplicationId($jobTitle)
    {
        // Clean job title - remove special characters and spaces, take first 3 words
        $cleanTitle = preg_replace('/[^a-zA-Z0-9\s]/', '', $jobTitle);
        $titleWords = explode(' ', $cleanTitle);
        $shortTitle = strtoupper(implode('', array_slice($titleWords, 0, 3)));
        
        // Generate timestamp-based unique ID
        $timestamp = now()->format('ymdHis'); // YYMMDDHHMMSS format
        $randomSuffix = strtoupper(substr(uniqid(), -3)); // 3 random characters
        
        // Format: JOBTITLE-YYMMDDHHMMSS-XXX
        $applicationId = "{$shortTitle}-{$timestamp}-{$randomSuffix}";
        
        // Ensure uniqueness by checking if it already exists
        $counter = 1;
        $originalId = $applicationId;
        while (self::where('application_id', $applicationId)->exists()) {
            $applicationId = "{$originalId}-{$counter}";
            $counter++;
        }
        
        return $applicationId;
    }

    /**
     * Mark job application email as sent
     */
    public function markJobApplicationEmailSent()
    {
        $this->job_applied_email_sent = true;
        $this->save();
        return $this;
    }

    /**
     * Mark payment confirmation email as sent
     */
    public function markPaymentConfirmationEmailSent()
    {
        $this->payment_confirmation_email_sent = true;
        $this->save();
        return $this;
    }

    /**
     * Check if job application email was sent
     */
    public function isJobApplicationEmailSent()
    {
        return $this->job_applied_email_sent;
    }

    /**
     * Check if payment confirmation email was sent
     */
    public function isPaymentConfirmationEmailSent()
    {
        return $this->payment_confirmation_email_sent;
    }
}