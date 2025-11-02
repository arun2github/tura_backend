# API Test for Save Personal Details

## Test Configuration

**URL:** `https://laravelv2.turamunicipalboard.com/api/savePersonalDetails`
**Method:** `POST`
**Authentication:** Bearer Token

## Headers
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xhcmF2ZWx2Mi50dXJhbXVuaWNpcGFsYm9hcmQuY29tL2FwaS9sb2dpbiIsImlhdCI6MTc2MTkyMDExMSwiZXhwIjoxNzYxOTIzNzExLCJuYmYiOjE3NjE5MjAxMTEsImp0aSI6ImR4TzRBbUxqeWgxSDYxOWQiLCJzdWIiOiIyIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.y5mo4zRg_ja5XFupWY_nI_1_KR0_HTndPiuXdm7JNF0
Content-Type: application/json
```

## Request Body
```json
{
    "job_id": 1,
    "salutation": "Mr.",
    "full_name": "Test User Application",
    "date_of_birth": "1990-05-15",
    "marital_status": "Single",
    "gender": "Male",
    "category": "General",
    "caste": "General",
    "religion": "Christianity",
    "identification_mark": "Scar on left hand",
    "permanent_address1": "123 Test Street",
    "permanent_address2": "Apartment 4B",
    "permanent_landmark": "Near Test Hospital",
    "permanent_village": "Test Village",
    "permanent_state": "Meghalaya",
    "permanent_district": "West Garo Hills",
    "permanent_pincode": "794001",
    "present_address1": "456 Current Avenue",
    "present_address2": "Floor 2",
    "present_landmark": "Behind Test Mall",
    "present_village": "Current Village",
    "present_state": "Meghalaya",
    "present_district": "West Garo Hills",
    "present_pincode": "794002"
}
```

## Expected Response
**Success (201):**
```json
{
    "success": true,
    "message": "Personal details saved successfully",
    "data": {
        "id": 123,
        "user_id": 2,
        "job_id": 1,
        "salutation": "Mr.",
        "full_name": "Test User Application",
        // ... all other fields
        "inserted_at": "2025-10-31T...",
        "updated_at": "2025-10-31T..."
    }
}
```

**Authentication Error (401):**
```json
{
    "success": false,
    "message": "Token expired. Please login again.",
    "error_code": "TOKEN_EXPIRED"
}
```

**Validation Error (400):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "job_id": ["The job id field is required."]
    }
}
```