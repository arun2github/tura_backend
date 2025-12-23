# 🐕 Admin Pet Dog Registration API Endpoints

## 📋 **1. Get All Pet Applications**

### **Endpoint:** `POST /api/pet-dog/applications`

### **Headers:**
```
Content-Type: application/json
Authorization: Bearer {jwt_token}
```

### **Request Body:**
```json
{
    "per_page": 20,
    "page": 1,
    "status": "CEO Approved",
    "payment_status": "paid",
    "search": "TMB-001"
}
```

### **Request Parameters:**
- `per_page` (optional, integer): Number of records per page (default: 20)
- `page` (optional, integer): Page number (default: 1)  
- `status` (optional, string): Filter by application status
- `payment_status` (optional, string): Filter by payment status ("paid", "pending", "failed")
- `search` (optional, string): Search by application ID, owner name, dog name, or pet tag number

### **Success Response (200):**
```json
{
    "status": "success",
    "message": "Applications retrieved successfully",
    "data": {
        "applications": [
            {
                "id": 1,
                "application_id": "APP-2024-001",
                "status": "CEO Approved", 
                "employee_status": "Approved",
                "ceo_status": "Approved",
                "submitted_date": "2024-12-24T10:30:00Z",
                "user_details": {
                    "name": "John Doe",
                    "email": "john@example.com",
                    "phone": "9876543210",
                    "ward_id": "WARD-001",
                    "locality": "Gandhi Road"
                },
                "pet_details": {
                    "owner_name": "John Doe",
                    "owner_email": "john@example.com", 
                    "owner_phone": "9876543210",
                    "dog_name": "Bruno",
                    "dog_breed": "Labrador",
                    "pet_tag_number": "TMB-001",
                    "vaccination_status": "completed"
                },
                "payment_status": "paid",
                "payment_amount": 250.00
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total_records": 45,
            "total_pages": 3,
            "has_next": true,
            "has_previous": false
        },
        "summary": {
            "total_applications": 45,
            "ceo_approved": 30,
            "pending": 15,
            "paid_applications": 28,
            "pending_payments": 17
        }
    }
}
```

### **Error Response (403):**
```json
{
    "status": "error",
    "message": "Access denied. CEO or Editor role required."
}
```

### **Error Response (401):**
```json
{
    "status": "error",
    "message": "Unauthorized. Invalid or expired token."
}
```

---

## 📄 **2. Get Application Details**

### **Endpoint:** `POST /api/pet-dog/application-details`

### **Headers:**
```
Content-Type: application/json
Authorization: Bearer {jwt_token}
```

### **Request Body:**
```json
{
    "application_id": 1
}
```

### **Request Parameters:**
- `application_id` (required, integer): Database ID of the application

### **Success Response (200):**
```json
{
    "status": "success",
    "message": "Application details retrieved successfully",
    "data": {
        "application_info": {
            "id": 1,
            "application_id": "APP-2024-001",
            "status": "CEO Approved",
            "employee_status": "Approved",
            "ceo_status": "Approved",
            "submitted_date": "2024-12-24T10:30:00Z",
            "last_updated": "2024-12-24T11:45:00Z"
        },
        "user_details": {
            "user_id": 123,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "9876543210",
            "ward_id": "WARD-001",
            "locality": "Gandhi Road",
            "registration_date": "2024-01-15T09:30:00Z"
        },
        "owner_details": {
            "owner_name": "John Doe",
            "owner_email": "john@example.com",
            "owner_phone": "9876543210",
            "owner_address": "123 Gandhi Road, Tura, Meghalaya",
            "owner_aadhar_number": "123456789012"
        },
        "dog_details": {
            "dog_name": "Bruno",
            "dog_breed": "Labrador",
            "dog_age": 2,
            "dog_age_unit": "years",
            "dog_color": "Golden",
            "dog_gender": "male",
            "dog_weight": 25.5,
            "pet_tag_number": "TMB-001",
            "vaccination_status": "completed",
            "vaccination_date": "2024-11-15",
            "veterinarian_name": "Dr. Smith",
            "veterinarian_license": "VET-2024-001"
        },
        "payment_details": {
            "payment_id": "PAY-2024-001",
            "amount": 250.00,
            "status": "paid",
            "payment_date": "2024-12-24T11:00:00Z",
            "transaction_id": "TXN-123456789",
            "payment_method": "SBI Payment Gateway"
        },
        "documents": [
            {
                "type": "pet_photo",
                "name": "bruno_photo.jpg",
                "upload_date": "2024-12-24T10:30:00Z"
            },
            {
                "type": "owner_photo_with_pet", 
                "name": "owner_with_bruno.jpg",
                "upload_date": "2024-12-24T10:30:00Z"
            },
            {
                "type": "vaccination_certificate",
                "name": "vaccination_cert.pdf",
                "upload_date": "2024-12-24T10:32:00Z"
            }
        ],
        "timeline": [
            {
                "action": "Application Submitted",
                "date": "2024-12-24T10:30:00Z",
                "by": "Applicant"
            },
            {
                "action": "Payment Completed",
                "date": "2024-12-24T11:00:00Z", 
                "by": "System"
            },
            {
                "action": "Employee Approved",
                "date": "2024-12-24T11:30:00Z",
                "by": "Employee"
            },
            {
                "action": "CEO Approved",
                "date": "2024-12-24T11:45:00Z",
                "by": "CEO"
            }
        ]
    }
}
```

### **Error Response (404):**
```json
{
    "status": "error",
    "message": "Application not found with ID: 1"
}
```

### **Error Response (403):**
```json
{
    "status": "error",
    "message": "Access denied. CEO or Editor role required."
}
```

### **Error Response (422):**
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "application_id": ["The application_id field is required."]
    }
}
```

---

## 🎯 **Usage Notes**

### **Authentication:**
- Both endpoints require JWT token with CEO or Editor role
- Token must be passed in Authorization header as Bearer token

### **Pagination:**
- `/applications` endpoint supports pagination with `page` and `per_page` parameters
- Returns pagination metadata for building UI pagination controls

### **Filtering:**
- Filter by application status: "CEO Approved", "Pending", "Employee Approved"
- Filter by payment status: "paid", "pending", "failed"
- Search across application ID, owner name, dog name, pet tag number

### **Response Data:**
- All dates in ISO 8601 format with timezone
- Amounts in decimal format (250.00)
- Complete application lifecycle information
- Document metadata (actual file serving requires separate endpoint)