# Complete API Testing Guide - Personal Details

## 🔧 Setup Instructions

### Step 1: Open Postman
1. Create a new request
2. Set method to `POST`
3. Set URL to: `https://laravelv2.turamunicipalboard.com/api/savePersonalDetails`

### Step 2: Set Authentication
Click "Authorization" tab and configure:

1. **Type**: Select "Bearer Token" from dropdown
2. **Token**: Paste your JWT token (without "Bearer" prefix):
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xhcmF2ZWx2Mi50dXJhbXVuaWNpcGFsYm9hcmQuY29tL2FwaS9sb2dpbiIsImlhdCI6MTc2MTkyMDExMSwiZXhwIjoxNzYxOTIzNzExLCJuYmYiOjE3NjE5MjAxMTEsImp0aSI6ImR4TzRBbUxqeWgxSDYxOWQiLCJzdWIiOiIyIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.y5mo4zRg_ja5XFupWY_nI_1_KR0_HTndPiuXdm7JNF0
```

**Note**: Postman will automatically add "Bearer " prefix and create the Authorization header.

### Alternative: Manual Headers (if Authorization tab doesn't work)
If you prefer, you can also set headers manually:

| Key | Value |
|-----|-------|
| `Authorization` | `Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xhcmF2ZWx2Mi50dXJhbXVuaWNpcGFsYm9hcmQuY29tL2FwaS9sb2dpbiIsImlhdCI6MTc2MTkyMDExMSwiZXhwIjoxNzYxOTIzNzExLCJuYmYiOjE3NjE5MjAxMTEsImp0aSI6ImR4TzRBbUxqeWgxSDYxOWQiLCJzdWIiOiIyIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.y5mo4zRg_ja5XFupWY_nI_1_KR0_HTndPiuXdm7JNF0` |
| `Content-Type` | `application/json` |

### Step 3: Set Request Body
1. Click "Body" tab
2. Select "raw" 
3. Choose "JSON" from dropdown
4. Paste this JSON:

```json
{
    "job_id": 1,
    "salutation": "Mr.",
    "full_name": "Test User 2025",
    "date_of_birth": "1990-05-15",
    "marital_status": "Single",
    "gender": "Male",
    "category": "General",
    "caste": "General",
    "religion": "Christianity",
    "identification_mark": "Small scar on left hand",
    "permanent_address1": "123 Main Test Street",
    "permanent_address2": "Block A, Apartment 5",
    "permanent_landmark": "Near Central Hospital",
    "permanent_village": "Test Town",
    "permanent_state": "Meghalaya",
    "permanent_district": "West Garo Hills",
    "permanent_pincode": "794001",
    "present_address1": "456 Current Living Street",
    "present_address2": "Floor 3, Room 301",
    "present_landmark": "Behind Shopping Complex",
    "present_village": "Current Town",
    "present_state": "Meghalaya",
    "present_district": "West Garo Hills",
    "present_pincode": "794002"
}
```

### Step 4: Send Request
Click the blue "Send" button.

---

## ✅ Expected Results

### Success Response (Status: 201 Created)
```json
{
    "success": true,
    "message": "Personal details saved successfully",
    "data": {
        "id": 124,
        "user_id": 2,
        "job_id": 1,
        "salutation": "Mr.",
        "full_name": "Test User 2025",
        "date_of_birth": "1990-05-15",
        "marital_status": "Single",
        "gender": "Male",
        "category": "General",
        "caste": "General",
        "religion": "Christianity",
        "identification_mark": "Small scar on left hand",
        "permanent_address1": "123 Main Test Street",
        "permanent_address2": "Block A, Apartment 5",
        "permanent_landmark": "Near Central Hospital",
        "permanent_village": "Test Town",
        "permanent_state": "Meghalaya",
        "permanent_district": "West Garo Hills",
        "permanent_pincode": "794001",
        "present_address1": "456 Current Living Street",
        "present_address2": "Floor 3, Room 301",
        "present_landmark": "Behind Shopping Complex",
        "present_village": "Current Town",
        "present_state": "Meghalaya",
        "present_district": "West Garo Hills",
        "present_pincode": "794002",
        "inserted_at": "2025-10-31T15:30:45.000000Z",
        "updated_at": "2025-10-31T15:30:45.000000Z"
    }
}
```

---

## ❌ Possible Error Responses

### 1. Token Expired (Status: 401)
```json
{
    "success": false,
    "message": "Token expired. Please login again.",
    "error_code": "TOKEN_EXPIRED"
}
```
**Solution:** Login again to get a new token

### 2. Token Missing (Status: 401)
```json
{
    "success": false,
    "message": "Token not provided. Please login first.",
    "error_code": "TOKEN_MISSING"
}
```
**Solution:** Check Authorization header is correctly set

### 3. Invalid Token (Status: 401)
```json
{
    "success": false,
    "message": "Invalid token. Please login again.",
    "error_code": "TOKEN_INVALID"
}
```
**Solution:** Get a fresh token from login API

### 4. Validation Errors (Status: 400)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "job_id": ["The job id field is required."],
        "full_name": ["The full name field is required."],
        "permanent_pincode": ["The permanent pincode must be 6 digits."]
    }
}
```
**Solution:** Fix the validation errors and resend

### 5. Duplicate Application (Status: 409)
```json
{
    "success": false,
    "message": "Application already exists for this user and job combination",
    "error": "Duplicate application detected"
}
```
**Solution:** Try with a different job_id or update existing application

---

## 🔍 Troubleshooting Checklist

1. **✅ URL Correct:** `https://laravelv2.turamunicipalboard.com/api/savePersonalDetails`
2. **✅ Method:** POST
3. **✅ Authorization Header:** `Bearer {your_token}` (note the space after Bearer)
4. **✅ Content-Type:** `application/json`
5. **✅ JSON Valid:** Check your JSON syntax
6. **✅ All Required Fields:** Make sure all fields in the example are included
7. **✅ Token Valid:** Your token expires 1 hour after login

---

## 🔄 If Token Expired

If you get token expired error, first login again:

```
POST https://laravelv2.turamunicipalboard.com/api/login
Content-Type: application/json

{
    "email": "your-email@example.com",
    "password": "your-password"
}
```

Then use the new `access_token` from the response.

---

## 🧪 Alternative Test - Using Token in Body

If Authorization header doesn't work, try including token in request body:

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2xhcmF2ZWx2Mi50dXJhbXVuaWNpcGFsYm9hcmQuY29tL2FwaS9sb2dpbiIsImlhdCI6MTc2MTkyMDExMSwiZXhwIjoxNzYxOTIzNzExLCJuYmYiOjE3NjE5MjAxMTEsImp0aSI6ImR4TzRBbUxqeWgxSDYxOWQiLCJzdWIiOiIyIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.y5mo4zRg_ja5XFupWY_nI_1_KR0_HTndPiuXdm7JNF0",
    "job_id": 1,
    "salutation": "Mr.",
    "full_name": "Test User 2025",
    "date_of_birth": "1990-05-15",
    "marital_status": "Single",
    "gender": "Male",
    "category": "General",
    "caste": "General",
    "religion": "Christianity",
    "identification_mark": "Small scar on left hand",
    "permanent_address1": "123 Main Test Street",
    "permanent_address2": "Block A, Apartment 5",
    "permanent_landmark": "Near Central Hospital",
    "permanent_village": "Test Town",
    "permanent_state": "Meghalaya",
    "permanent_district": "West Garo Hills",
    "permanent_pincode": "794001",
    "present_address1": "456 Current Living Street",
    "present_address2": "Floor 3, Room 301",
    "present_landmark": "Behind Shopping Complex",
    "present_village": "Current Town",
    "present_state": "Meghalaya",
    "present_district": "West Garo Hills",
    "present_pincode": "794002"
}
```

This comprehensive test should work with your updated authentication system! 🚀