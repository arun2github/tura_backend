# JWT Authentication Implementation

## Overview
All Job Application APIs now require JWT authentication. Users must login first and pass the JWT token with each request.

## Authentication Flow

### 1. User Login
```
POST /api/login
{
    "email": "user@example.com",
    "password": "password123"
}

Response:
{
    "status": "success",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "role": "user",
    "message": "Login successfull",
    "user_details": {...}
}
```

### 2. Using APIs with JWT Token

#### Method 1: Authorization Header (Recommended)
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Method 2: Request Body (Backward Compatible)
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "job_id": 1,
    "other_data": "..."
}
```

## Updated API Endpoints

### All Job APIs now require JWT authentication:
- `GET /api/jobs`
- `POST /api/getApplicationProgress`
- `POST /api/savePersonalDetails`
- `POST /api/saveEmploymentDetails`
- `POST /api/getEmploymentDetails`
- `POST /api/saveQualificationDetails`
- `POST /api/getQualificationDetails`
- `GET /api/getDocumentRequirements`
- `POST /api/uploadDocuments`
- `POST /api/getUploadedDocuments`
- `POST /api/downloadDocument`
- `POST /api/getCompleteApplicationDetails`
- `GET /api/getAvailableJobsForApplication/{user_id}`
- `POST /api/startJobApplication`

## Important Changes

### 1. No More user_id in Request Body
Previously:
```json
{
    "user_id": 123,
    "job_id": 1,
    "full_name": "John Doe"
}
```

Now:
```json
{
    "job_id": 1,
    "full_name": "John Doe"
}
```
**Note**: The user_id is automatically extracted from the JWT token.

### 2. Error Responses for Authentication Issues

#### Token Missing:
```json
{
    "success": false,
    "message": "Token not provided. Please login first.",
    "error_code": "TOKEN_MISSING"
}
```

#### Token Expired:
```json
{
    "success": false,
    "message": "Token expired. Please login again.",
    "error_code": "TOKEN_EXPIRED"
}
```

#### Invalid Token:
```json
{
    "success": false,
    "message": "Invalid token. Please login again.",
    "error_code": "TOKEN_INVALID"
}
```

## Example API Calls

### 1. Save Personal Details
```javascript
// Header
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...

// Request Body
{
    "job_id": 1,
    "salutation": "Mr.",
    "full_name": "John Doe",
    "date_of_birth": "1990-01-01",
    "marital_status": "Single",
    "gender": "Male",
    // ... other fields (no user_id needed)
}
```

### 2. Check Application Progress
```javascript
// Header
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...

// Request Body
{
    "job_id": 1  // user_id automatically from token
}
```

### 3. Get Available Jobs for Application
```javascript
// Header
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...

// GET request - user_id automatically from token
GET /api/getAvailableJobsForApplication/{user_id}
```

## Security Benefits

1. **Automatic User Identification**: User ID is extracted from JWT token, preventing impersonation
2. **Session Management**: Tokens can expire, requiring re-authentication
3. **Secure Authentication**: No need to pass user credentials with every request
4. **Authorization Control**: Only authenticated users can access job application features

## Migration Guide

### For Frontend Applications:
1. Store JWT token after successful login
2. Include token in Authorization header for all job API calls
3. Remove user_id from request bodies
4. Handle authentication error responses appropriately
5. Redirect to login page when token expires

### For Testing:
1. First call `/api/login` to get JWT token
2. Use the token in subsequent API calls
3. Test both Authorization header and request body token methods