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
        'venue_name',
        'venue_address',
        'photo_base64',
        'job_title',
        // Slot 1 fields
        'subject_slot_1',
        'exam_date_slot_1',
        'exam_start_time_slot_1',
        'exam_end_time_slot_1',
        'reporting_time_slot_1',
        // Slot 2 fields
        'subject_slot_2',
        'exam_date_slot_2',
        'exam_start_time_slot_2',
        'exam_end_time_slot_2',
        'reporting_time_slot_2',
        'pdf_downloaded_at',
        'status',
        'issued_at',
        'issued_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        // Slot 1 dates and times
        'exam_date_slot_1' => 'date',
        'exam_start_time_slot_1' => 'datetime',
        'exam_end_time_slot_1' => 'datetime',
        'reporting_time_slot_1' => 'datetime',
        // Slot 2 dates and times
        'exam_date_slot_2' => 'date',
        'exam_start_time_slot_2' => 'datetime',
        'exam_end_time_slot_2' => 'datetime',
        'reporting_time_slot_2' => 'datetime',
        'pdf_downloaded_at' => 'datetime',
        'issued_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'active'
    ];

    // Slot 1 accessors
    public function getSlot1ExamTimeAttribute()
    {
        if ($this->exam_start_time_slot_1 && $this->exam_end_time_slot_1) {
            return date('h:i A', strtotime($this->exam_start_time_slot_1)) . ' - ' . date('h:i A', strtotime($this->exam_end_time_slot_1));
        }
        return null;
    }

    public function getSlot1ReportingTimeAttribute()
    {
        if ($this->reporting_time_slot_1) {
            return date('h:i A', strtotime($this->reporting_time_slot_1));
        }
        return null;
    }

    public function getSlot1ExamDateAttribute()
    {
        if ($this->exam_date_slot_1) {
            return $this->exam_date_slot_1->format('d-m-Y');
        }
        return null;
    }

    // Slot 2 accessors
    public function getSlot2ExamTimeAttribute()
    {
        if ($this->exam_start_time_slot_2 && $this->exam_end_time_slot_2) {
            return date('h:i A', strtotime($this->exam_start_time_slot_2)) . ' - ' . date('h:i A', strtotime($this->exam_end_time_slot_2));
        }
        return null;
    }

    public function getSlot2ReportingTimeAttribute()
    {
        if ($this->reporting_time_slot_2) {
            return date('h:i A', strtotime($this->reporting_time_slot_2));
        }
        return null;
    }

    public function getSlot2ExamDateAttribute()
    {
        if ($this->exam_date_slot_2) {
            return $this->exam_date_slot_2->format('d-m-Y');
        }
        return null;
    }

    // Helper method to check if slot has data
    public function hasSlot1()
    {
        return !empty($this->subject_slot_1) && !empty($this->exam_date_slot_1);
    }

    public function hasSlot2()
    {
        return !empty($this->subject_slot_2) && !empty($this->exam_date_slot_2);
    }
}