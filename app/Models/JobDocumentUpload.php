<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDocumentUpload extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_documents_upload';

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
        'job_id',
        'document_type',
        'file_base64',
        'file_name',
        'file_extension',
        'file_size',
        'is_mandatory',
        'uploaded_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uploaded_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Get document type requirements
     */
    public static function getDocumentRequirements()
    {
        return [
            'Photo' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => true
            ],
            'Signature' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => true
            ],
            'Caste/Tribe Certificate' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => true
            ],
            'Proof of Age (As Certified by Board of School Education)' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => true
            ],
            'Educational Qualifications' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 5120, // 5MB in KB
                'max_size_mb' => 5,
                'mandatory' => true
            ],
            'PWD Certificate' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => false
            ],
            'Sports Certificate' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => false
            ],
            'Experience Certificate' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => false
            ],
            'Proof of Citizenship' => [
                'types' => ['jpg', 'jpeg', 'png', 'pdf'],
                'max_size' => 2048, // 2MB in KB
                'max_size_mb' => 2,
                'mandatory' => false
            ]
        ];
    }

    /**
     * Get mandatory document types
     */
    public static function getMandatoryDocumentTypes()
    {
        $requirements = self::getDocumentRequirements();
        $mandatory = [];
        
        foreach ($requirements as $docType => $requirements) {
            if ($requirements['mandatory']) {
                $mandatory[] = $docType;
            }
        }
        
        return $mandatory;
    }

    /**
     * Check if all mandatory documents are uploaded for a user and job
     */
    public static function checkMandatoryComplete($userId, $jobId)
    {
        $mandatoryTypes = self::getMandatoryDocumentTypes();
        $uploadedMandatory = self::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->where('is_mandatory', true)
            ->pluck('document_type')
            ->toArray();
            
        $missingMandatory = array_diff($mandatoryTypes, $uploadedMandatory);
        
        return [
            'is_complete' => empty($missingMandatory),
            'missing_documents' => $missingMandatory,
            'uploaded_mandatory' => $uploadedMandatory,
            'total_mandatory' => count($mandatoryTypes),
            'uploaded_mandatory_count' => count($uploadedMandatory)
        ];
    }

    /**
     * Get document upload summary for a user and job
     */
    public static function getUploadSummary($userId, $jobId)
    {
        $totalDocuments = self::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->count();
            
        $mandatoryCount = self::where('user_id', $userId)
            ->where('job_id', $jobId)
            ->where('is_mandatory', true)
            ->count();
            
        $optionalCount = $totalDocuments - $mandatoryCount;
        $mandatoryCheck = self::checkMandatoryComplete($userId, $jobId);
        
        return [
            'total_documents' => $totalDocuments,
            'mandatory_uploaded' => $mandatoryCount,
            'optional_uploaded' => $optionalCount,
            'is_application_complete' => $mandatoryCheck['is_complete'],
            'missing_mandatory' => $mandatoryCheck['missing_documents']
        ];
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