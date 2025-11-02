<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vacancy_count' => 'integer',
        'pay_scale' => 'integer',
        'fee_general' => 'decimal:2',
        'fee_sc_st' => 'decimal:2',
    ];
}