<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TuraJobPosting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_postings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_title_department',
        'vacancy_count',
        'category',
        'pay_scale',
        'qualification',
        'fee_general',
        'fee_sc_st',
        'fee_obc',
        'application_start_date',
        'application_end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fee_general' => 'decimal:2',
        'fee_sc_st' => 'decimal:2',
        'fee_obc' => 'decimal:2',
        'vacancy_count' => 'integer',
        'application_start_date' => 'date',
        'application_end_date' => 'date',
    ];

    /**
     * Category constants
     */
    const CATEGORIES = [
        'UR' => 'Unreserved',
        'OBC' => 'Other Backward Classes',
        'SC' => 'Scheduled Caste',
        'ST' => 'Scheduled Tribe',
        'EWS' => 'Economically Weaker Section',
    ];

    /**
     * Check if job is available for application
     * For now, we'll keep it simple - all jobs are available unless dates restrict them
     */
    public function isApplicationOpen()
    {
        // For basic job vacancy listing, we can make all jobs available
        // You can add date restrictions later if needed
        
        $now = now()->toDateString();
        
        // If start date is set and we haven't reached it yet
        if ($this->application_start_date && $now < $this->application_start_date) {
            return false;
        }

        // If end date is set and we've passed it
        if ($this->application_end_date && $now > $this->application_end_date) {
            return false;
        }

        // Otherwise, job is available for application
        return true;
    }

    /**
     * Get fee based on category
     */
    public function getFeeByCategory($category)
    {
        switch (strtolower($category)) {
            case 'sc':
            case 'st':
                return $this->fee_sc_st;
            case 'obc':
                return $this->fee_obc ?? $this->fee_general;
            case 'ur':
            case 'general':
            default:
                return $this->fee_general;
        }
    }

    /**
     * Scope for active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for jobs with open applications
     */
    public function scopeApplicationOpen($query)
    {
        $now = now()->toDateString();
        
        return $query->where('status', 'active')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('application_start_date')
                          ->orWhere('application_start_date', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('application_end_date')
                          ->orWhere('application_end_date', '>=', $now);
                    });
    }
}
