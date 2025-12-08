# Pet Dog Registration API - Postman Testing Guide

## Quick Setup

### 1. Environment Setup
Create a new environment in Postman with these variables:

```json
{
  "base_url": "https://laravelv2.turamunicipalboard.com/api",
  "local_url": "http://127.0.0.1:8000/api",
  "jwt_token": "",
  "application_id": ""
}
```

### 2. Import Collection
Import the `Pet_Dog_Registration_API_Collection.json` file into Postman.

## Testing Workflow

### Step 1: Authentication
```bash
POST {{base_url}}/register
Content-Type: application/json

{
    "firstname": "John",
    "lastname": "Doe", 
    "email": "john.doe@example.com",
    "password": "password123",
    "confirm_password": "password123",
    "phone_no": "9876543210",
    "dob": "1990-01-01",
    "ward_id": "1",
    "locality": "Chandmary"
}
```

**Expected Response:**
```json
{
    "status": true,
    "message": "User registered successfully",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com"
        }
    }
}
```

### Step 2: Submit Pet Dog Registration
```bash
POST {{base_url}}/pet-dog/submit
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
    "owner_name": "John Doe",
    "identity_proof_type": "aadhar",
    "identity_proof_number": "123456789012",
    "phone_number": "+91 9876543210",
    "email": "john.doe@example.com",
    "dog_name": "Buddy",
    "dog_breed": "Golden Retriever",
    "address": "House No. 123, Main Street, Tura, Meghalaya - 794001",
    "identity_proof_document": "data:image/jpeg;base64,/9j/4AAQSkZJRg...[base64 data]",
    "vaccination_card_document": "data:application/pdf;base64,JVBERi0xLjMK...[base64 data]",
    "dog_photo_document": "data:image/jpeg;base64,/9j/4AAQSkZJRg...[base64 data]"
}
```

**Expected Response:**
```json
{
    "status": true,
    "message": "Pet Dog Registration application submitted successfully",
    "application_id": "PDR-2025-0001",
    "registration_fee": 50.00,
    "metal_tag_fee": 200.00,
    "total_fee": 250.00,
    "stipulated_time": "2 Days",
    "data": {
        "application_id": "PDR-2025-0001",
        "owner_name": "John Doe",
        "dog_name": "Buddy",
        "dog_breed": "Golden Retriever",
        "status": "pending",
        "payment_status": "pending",
        "submitted_at": "2025-12-07 10:30:00"
    }
}
```

### Step 3: Get All Applications
```bash
POST {{base_url}}/pet-dog/getAllApplications
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
    "stage": "consumer",
    "page": 1,
    "limit": 10,
    "search": "",
    "status": ""
}
```

**Expected Response:**
```json
{
    "status": true,
    "message": "Pet Dog Registration applications retrieved successfully",
    "data": [
        {
            "application_id": "PDR-2025-0001",
            "application_submited_at": "2025-12-07 10:30:00",
            "application_for": "Pet Dog Registration",
            "status": "pending",
            "formNumber": "PDR",
            "form_id": 1,
            "payment": "Yes",
            "payment_status": "pending",
            "form": {
                "owner_name": "John Doe",
                "dog_name": "Buddy",
                "dog_breed": "Golden Retriever",
                "phone_number": "+91 9876543210",
                "email": "john.doe@example.com",
                "address": "House No. 123, Main Street, Tura, Meghalaya - 794001",
                "registration_fee": 50.00,
                "metal_tag_fee": 200.00,
                "total_fee": 250.00,
                "form_id": 1
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 1,
        "last_page": 1
    },
    "counts": {
        "pending": 1,
        "approved": 0,
        "rejected": 0
    }
}
```

### Step 4: Get Application Details
```bash
POST {{base_url}}/pet-dog/getDetails
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
    "application_id": "PDR-2025-0001"
}
```

**Expected Response:**
```json
{
    "status": true,
    "message": "Application details retrieved successfully",
    "data": {
        "application_id": "PDR-2025-0001",
        "owner_name": "John Doe",
        "identity_proof_type": "aadhar",
        "identity_proof_number": "123456789012",
        "phone_number": "+91 9876543210",
        "email": "john.doe@example.com",
        "dog_name": "Buddy",
        "dog_breed": "Golden Retriever",
        "address": "House No. 123, Main Street, Tura, Meghalaya - 794001",
        "registration_fee": 50.00,
        "metal_tag_fee": 200.00,
        "total_fee": 250.00,
        "status": "pending",
        "payment_status": "pending",
        "metal_tag_number": null,
        "submitted_at": "2025-12-07 10:30:00",
        "approved_at": null,
        "documents": {
            "identity_proof": "/storage/pet_dog_registrations/PDR-2025-0001/identity_proof.jpg",
            "vaccination_card": "/storage/pet_dog_registrations/PDR-2025-0001/vaccination_card.pdf",
            "dog_photo": "/storage/pet_dog_registrations/PDR-2025-0001/dog_photo.jpg",
            "registration_certificate": null
        }
    }
}
```

### Step 5: Update Payment Status
```bash
POST {{base_url}}/pet-dog/updatePayment
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
    "application_id": "PDR-2025-0001",
    "payment_status": "paid",
    "transaction_id": "TXN1733568000"
}
```

**Expected Response:**
```json
{
    "status": true,
    "message": "Payment status updated successfully",
    "data": {
        "application_id": "PDR-2025-0001",
        "payment_status": "paid",
        "status": "approved",
        "metal_tag_number": "TMB-DOG-2025-0001"
    }
}
```

## API Endpoints Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Register new user | ❌ |
| POST | `/login` | User login | ❌ |
| POST | `/pet-dog/submit` | Submit registration | ✅ |
| POST | `/pet-dog/getAllApplications` | Get all applications | ✅ |
| POST | `/pet-dog/getDetails` | Get application details | ✅ |
| POST | `/pet-dog/updatePayment` | Update payment status | ✅ |

## User Roles & Access

### Consumer (stage: "consumer")
- View their own applications only
- Submit new applications
- View application details
- Cannot approve/reject applications

### Employee (stage: "employee")  
- View all pending applications
- Process applications
- Update application status
- Cannot view approved applications by CEO

### CEO (stage: "ceo")
- View all applications approved by employees
- Final approval authority
- Access to all application data

## Required Documents (Base64 Encoded)

### 1. Identity Proof Document (Mandatory)
- **Format:** Base64 encoded string with data URI prefix
- **Supported Types:** JPEG, PNG, PDF
- **Max Size:** 2MB (before encoding)
- **Options:** Passport, PAN Card, Voter ID, Aadhar Card
- **Example:** `"data:image/jpeg;base64,/9j/4AAQSkZJRg..."`

### 2. Vaccination Card Document (Mandatory)
- **Format:** Base64 encoded string with data URI prefix  
- **Supported Types:** JPEG, PNG, PDF
- **Max Size:** 2MB (before encoding)
- **Requirement:** Certified by registered Veterinary Doctor
- **Example:** `"data:application/pdf;base64,JVBERi0xLjMK..."`

### 3. Dog Photo Document (Mandatory)
- **Format:** Base64 encoded string with data URI prefix
- **Supported Types:** JPEG, PNG
- **Max Size:** 2MB (before encoding)
- **Requirement:** Clear photo of the animal
- **Example:** `"data:image/jpeg;base64,/9j/4AAQSkZJRg..."`

## Base64 Document Processing

The API automatically:
1. Validates base64 format
2. Decodes the document data
3. Detects MIME type (JPEG/PNG/PDF)
4. Saves file with appropriate extension
5. Stores in organized folder structure: `/storage/app/public/pet_dog_registrations/{application_id}/`

## Error Responses

### 400 - Validation Error
```json
{
    "status": false,
    "message": "Validation failed",
    "errors": {
        "owner_name": ["The owner name field is required."],
        "email": ["The email must be a valid email address."]
    }
}
```

### 401 - Authentication Required
```json
{
    "status": false,
    "message": "Authentication required"
}
```

### 404 - Application Not Found
```json
{
    "status": false,
    "message": "Application not found"
}
```

### 500 - Server Error
```json
{
    "status": false,
    "message": "Failed to submit registration: [error details]"
}
```

## Fee Structure

| Component | Amount |
|-----------|--------|
| Registration Fee | Rs. 50/- |
| Metal Tag | Rs. 200/- |
| **Total Fee** | **Rs. 250/-** |

## Processing Timeline

- **Stipulated Time Limit:** 2 Days
- **Auto-approval:** On successful payment (for now)
- **Notification:** SMS and Email upon approval
- **Certificate:** Available for download from website

## Important Notes

1. **Age Requirement:** Dogs must be 3+ months old
2. **Registration Channels:** Online and Offline (visit TMB at Chandmary)
3. **Document Verification:** All documents will be verified
4. **Payment Gateway:** Integration pending
5. **Certificate Generation:** PDF generation pending
6. **Notifications:** SMS/Email integration pending

## Testing Checklist

- ✅ User Registration
- ✅ User Login  
- ✅ JWT Token Authentication
- ✅ Application Submission (without files)
- ✅ Get All Applications (All roles)
- ✅ Get Application Details
- ✅ Payment Status Update
- ✅ Search Functionality
- ✅ Status Filters
- ✅ Error Handling
- ⚠️ File Upload Testing (requires multipart/form-data)
- ⚠️ Certificate Generation
- ⚠️ SMS/Email Notifications

## Next Development Steps

1. **File Upload Testing:** Test with actual file uploads
2. **Payment Integration:** Integrate with payment gateway
3. **Certificate Generation:** PDF certificate creation
4. **Notification System:** SMS/Email setup
5. **Admin Panel:** Employee/CEO interface
6. **Reporting:** Analytics and reports