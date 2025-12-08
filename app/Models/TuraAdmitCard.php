<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuraAdmitCard extends Model
{
    protected $table = 'tura_admit_cards';

    protected $fillable = [
        'job_applied_status_id',
        'job_id',
        'user_id',
        'application_id',
        'admit_no',
        'roll_number',
        'full_name',
        'date_of_birth',
        'gender',
        'category',
        'email',
        'phone',
        'exam_date',
        'exam_start_time',
        'exam_end_time',
        'reporting_time',
        'venue_name',
        'venue_address',
        'photo_base64',
        'job_title',
        'pdf_downloaded_at',
        'status',
        'issued_at',
        'issued_by'
    ];

    protected $casts = [
        'exam_date' => 'date',
        'date_of_birth' => 'date',
        'exam_start_time' => 'datetime',
        'exam_end_time' => 'datetime',
        'reporting_time' => 'datetime',
        'pdf_downloaded_at' => 'datetime',
        'issued_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'active'
    ];

    public function getExamTimeAttribute()
    {
        if ($this->exam_start_time && $this->exam_end_time) {
            return date('h:i A', strtotime($this->exam_start_time)) . ' - ' . date('h:i A', strtotime($this->exam_end_time));
        }
        return null;
    }

    public function getFormattedReportingTimeAttribute()
    {
        if ($this->reporting_time) {
            return date('h:i A', strtotime($this->reporting_time));
        }
        return null;
    }

    public function getFormattedExamDateAttribute()
    {
        if ($this->exam_date) {
            return $this->exam_date->format('d-m-Y');
        }
        return null;
    }
}