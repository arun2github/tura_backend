<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDocuments extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tura_job_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'job_id',
        
        // Photo
        'photo_base64',
        'photo_filename',
        'photo_extension',
        'photo_file_size',
        'photo_uploaded_at',
        
        // Signature
        'signature_base64',
        'signature_filename',
        'signature_extension',
        'signature_file_size',
        'signature_uploaded_at',
        
        // Caste Certificate
        'caste_certificate_base64',
        'caste_certificate_filename',
        'caste_certificate_extension',
        'caste_certificate_file_size',
        'caste_certificate_uploaded_at',
        
        // Proof of Age
        'proof_of_age_base64',
        'proof_of_age_filename',
        'proof_of_age_extension',
        'proof_of_age_file_size',
        'proof_of_age_uploaded_at',
        
        // Educational Qualification
        'educational_qualification_base64',
        'educational_qualification_filename',
        'educational_qualification_extension',
        'educational_qualification_file_size',
        'educational_qualification_uploaded_at',
        
        // PWD Certificate
        'pwd_certificate_base64',
        'pwd_certificate_filename',
        'pwd_certificate_extension',
        'pwd_certificate_file_size',
        'pwd_certificate_uploaded_at',
        
        // Sports Certificate
        'sports_certificate_base64',
        'sports_certificate_filename',
        'sports_certificate_extension',
        'sports_certificate_file_size',
        'sports_certificate_uploaded_at',
        
        // Experience Certificate
        'experience_certificate_base64',
        'experience_certificate_filename',
        'experience_certificate_extension',
        'experience_certificate_file_size',
        'experience_certificate_uploaded_at',
        
        // Proof of Citizenship
        'proof_of_citizenship_base64',
        'proof_of_citizenship_filename',
        'proof_of_citizenship_extension',
        'proof_of_citizenship_file_size',
        'proof_of_citizenship_uploaded_at',
        
        // Status tracking
        'is_mandatory_complete',
        'total_documents_uploaded',
        'application_submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'photo_uploaded_at' => 'datetime',
        'signature_uploaded_at' => 'datetime',
        'caste_certificate_uploaded_at' => 'datetime',
        'proof_of_age_uploaded_at' => 'datetime',
        'educational_qualification_uploaded_at' => 'datetime',
        'pwd_certificate_uploaded_at' => 'datetime',
        'sports_certificate_uploaded_at' => 'datetime',
        'experience_certificate_uploaded_at' => 'datetime',
        'proof_of_citizenship_uploaded_at' => 'datetime',
        'application_submitted_at' => 'datetime',
        'is_mandatory_complete' => 'boolean',
        'total_documents_uploaded' => 'integer',
    ];

    /**
     * Get document field mapping for easy access
     */
    public static function getDocumentFieldMapping()
    {
        return [
            'Photo' => 'photo',
            'Signature' => 'signature',
            'Caste/Tribe Certificate' => 'caste_certificate',
            'Proof of Age (As Certified by Board of School Education)' => 'proof_of_age',
            'Educational Qualifications' => 'educational_qualification',
            'PWD Certificate' => 'pwd_certificate',
            'Sports Certificate' => 'sports_certificate',
            'Experience Certificate' => 'experience_certificate',
            'Proof of Citizenship' => 'proof_of_citizenship',
        ];
    }

    /**
     * Get mandatory document fields
     */
    public static function getMandatoryFields()
    {
        return [
            'photo',
            'signature', 
            'caste_certificate',
            'proof_of_age',
            'educational_qualification'
        ];
    }

    /**
     * Check if all mandatory documents are uploaded
     */
    public function checkMandatoryComplete()
    {
        $mandatoryFields = self::getMandatoryFields();
        $allUploaded = true;
        
        foreach ($mandatoryFields as $field) {
            if (empty($this->{$field . '_base64'})) {
                $allUploaded = false;
                break;
            }
        }
        
        $this->is_mandatory_complete = $allUploaded;
        $this->save();
        
        return $allUploaded;
    }

    /**
     * Count uploaded documents
     */
    public function updateDocumentCount()
    {
        $count = 0;
        $fieldMapping = self::getDocumentFieldMapping();
        
        foreach ($fieldMapping as $docType => $field) {
            if (!empty($this->{$field . '_base64'})) {
                $count++;
            }
        }
        
        $this->total_documents_uploaded = $count;
        $this->save();
        
        return $count;
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