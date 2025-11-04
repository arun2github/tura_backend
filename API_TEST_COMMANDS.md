# cURL Test Commands for File Upload API

## Test 1: Upload Documents API

### Basic Upload Test
```bash
curl -X POST https://laravelv2.turamunicipalboard.com/api/upload-documents \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  -F "user_id=1" \
  -F "job_id=1" \
  -F "document_types[0]=profile_photo" \
  -F "document_types[1]=resume" \
  -F "documents[0]=@test_photo.jpg" \
  -F "documents[1]=@test_resume.pdf"
```

### Test with Validation Errors (Missing Required Fields)
```bash
curl -X POST https://laravelv2.turamunicipalboard.com/api/upload-documents \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  -F "user_id=1"
```

### Test with Invalid Document Type
```bash
curl -X POST https://laravelv2.turamunicipalboard.com/api/upload-documents \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  -F "user_id=1" \
  -F "job_id=1" \
  -F "document_types[0]=invalid_type" \
  -F "documents[0]=@test_photo.jpg"
```

## Test 2: Download Document API

### Download by Document ID
```bash
curl -X GET https://laravelv2.turamunicipalboard.com/api/download-document/1 \
  -H "Accept: application/json"
```

### Download Non-existent Document
```bash
curl -X GET https://laravelv2.turamunicipalboard.com/api/download-document/99999 \
  -H "Accept: application/json"
```

## Test 3: CORS Testing

### Test CORS Preflight
```bash
curl -X OPTIONS https://laravelv2.turamunicipalboard.com/api/upload-documents \
  -H "Origin: https://turamunicipalboard.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type"
```

### Test CORS with Different Origins
```bash
curl -X POST https://laravelv2.turamunicipalboard.com/api/upload-documents \
  -H "Origin: https://turamunicipalboard.com" \
  -H "Accept: application/json" \
  -H "Content-Type: multipart/form-data" \
  -F "user_id=1" \
  -F "job_id=1" \
  -F "document_types[0]=profile_photo" \
  -F "documents[0]=@test_photo.jpg"
```

## Creating Test Files

### Create Test Photo (Linux/Mac)
```bash
# Create a simple test image
convert -size 200x200 xc:white -pointsize 20 -draw "text 50,100 'TEST'" test_photo.jpg
```

### Create Test PDF
```bash
# Create a simple PDF file
echo '%PDF-1.4
1 0 obj<</Type/Page/Parent 3 0 R/MediaBox[0 0 612 792]>>endobj
2 0 obj<</Type/Catalog/Pages 3 0 R>>endobj  
3 0 obj<</Type/Pages/Kids[1 0 R]/Count 1>>endobj
xref
0 4
0000000000 65535 f 
0000000009 00000 n 
0000000074 00000 n 
0000000120 00000 n 
trailer<</Size 4/Root 2 0 R>>
startxref
177
%%EOF' > test_resume.pdf
```

## PowerShell Commands (Windows)

### Upload Test (PowerShell)
```powershell
$uri = "https://laravelv2.turamunicipalboard.com/api/upload-documents"
$form = @{
    user_id = "1"
    job_id = "1"
    "document_types[0]" = "profile_photo"
    "documents[0]" = Get-Item "test_photo.jpg"
}

Invoke-RestMethod -Uri $uri -Method Post -Form $form -ContentType "multipart/form-data"
```

### Download Test (PowerShell)
```powershell
$uri = "https://laravelv2.turamunicipalboard.com/api/download-document/1"
$headers = @{
    "Accept" = "application/json"
}

Invoke-RestMethod -Uri $uri -Method Get -Headers $headers
```

## Expected Responses

### Successful Upload Response
```json
{
    "success": true,
    "message": "Document upload processing completed",
    "summary": {
        "total_submitted": 2,
        "successfully_uploaded": 2,
        "duplicates_updated": 0,
        "validation_errors": 0,
        "application_complete": true
    },
    "application_status": {
        "is_complete": true,
        "mandatory_uploaded": 2,
        "total_mandatory_required": 2,
        "missing_mandatory": [],
        "total_documents": 2
    },
    "data": {
        "uploaded_documents": [
            {
                "id": 1,
                "document_type": "profile_photo",
                "file_name": "test_photo.jpg",
                "file_extension": "jpg",
                "file_size_bytes": 15420,
                "file_size_kb": 15.06,
                "is_mandatory": true,
                "uploaded_at": "2025-11-04 12:00:00"
            }
        ]
    }
}
```

### Successful Download Response
```json
{
    "success": true,
    "document": {
        "id": 1,
        "document_type": "profile_photo",
        "file_name": "test_photo.jpg",
        "file_extension": "jpg",
        "file_size": 15420,
        "uploaded_at": "2025-11-04 12:00:00"
    },
    "file_base64": "data:image/jpeg;base64,/9j/4AAQSkZJRgABA..."
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "user_id": ["The user id field is required."],
        "documents": ["The documents field is required."]
    }
}
```

## Notes
- Replace `https://laravelv2.turamunicipalboard.com` with your actual domain
- Use valid user_id and job_id from your database
- Ensure test files exist in the current directory
- Check CORS headers in responses for CORS validation