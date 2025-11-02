# Complete Job API Documentation

## 📋 Overview
This document covers all job-related APIs including job selection, application management, and status tracking with the 8-stage application process.

---

## 🗃️ Database Schema

### **tura_job_postings** (Job Listings Table)
```sql
- id (Primary Key)
- job_title_department (varchar 255)
- vacancy_count (integer)
- category (varchar 50) - UR, OBC, SC, ST, EWS
- pay_scale (varchar 100)
- qualification (text)
- fee_general (decimal 8,2)
- fee_sc_st (decimal 8,2)
- fee_obc (decimal 8,2)
- application_start_date (date) - nullable
- application_end_date (date) - nullable
- created_at, updated_at (timestamps)
```

### **tura_job_applied_status** (Application Status Table)
```sql
- id (Primary Key)
- user_id (Foreign Key to users table)
- job_id (Foreign Key to tura_job_postings table)
- status (text: draft, in_progress, submitted, under_review, approved, rejected)
- stage (integer: 0-7)
- inserted_at, updated_at (datetime)
```

---

## 📊 Application Stages & Statuses

### **8-Stage Application Process:**
0. **Job Selection** - User selects which job to apply for
1. **Personal Details** - User fills personal information  
2. **Qualification** - User adds educational qualifications
3. **Employment** - User adds employment history
4. **File Upload** - User uploads required documents
5. **Application Summary** - User reviews all information
6. **Payment** - User makes payment for application
7. **Print Application** - User can print final application

### **Status Types:**
- `draft` - Initial state
- `in_progress` - User is actively filling application
- `submitted` - Application completed and submitted
- `under_review` - Application is being reviewed
- `approved` - Application accepted
- `rejected` - Application rejected

---

## 🔗 API Endpoints

### **Authentication**
All APIs require JWT authentication:
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

---

## 1️⃣ **Job Selection & Status Management API**

### **Endpoint:** `POST /api/saveSelectedJob`

**Description:** Handles both initial job selection and stage/status updates throughout the application process.

#### **Request Examples:**

##### **A. Initial Job Selection**
```json
{
  "job_id": 2
}
```

##### **B. Stage Update**
```json
{
  "job_id": 2,
  "stage": 3
}
```

##### **C. Status Update**
```json
{
  "job_id": 2,
  "status": "submitted"
}
```

##### **D. Both Stage & Status Update**
```json
{
  "job_id": 2,
  "stage": 6,
  "status": "under_review"
}
```

#### **Validation Rules:**
- `job_id`: required, integer, must exist in tura_job_postings table
- `stage`: optional, integer, min: 0, max: 7
- `status`: optional, string, must be one of: draft, in_progress, submitted, under_review, approved, rejected

#### **Business Logic:**
1. **New Application:** If no existing application found, creates new record with stage 0 and status 'in_progress'
2. **Existing Application:** Updates stage/status if provided
3. **Stage Progression:** Only allows forward movement (higher stage numbers)
4. **Date Validation:** Checks application_start_date and application_end_date if set

#### **Response Examples:**

##### **A. New Application Created (201 Created)**
```json
{
  "success": true,
  "message": "Job application created successfully",
  "data": {
    "id": 15,
    "job_id": 2,
    "user_id": 123,
    "status": "in_progress",
    "stage": 0,
    "stage_name": "job_selection",
    "inserted_at": "2025-11-01T10:30:00.000000Z",
    "updated_at": "2025-11-01T10:30:00.000000Z",
    "is_new_application": true,
    "selected_job": {
      "id": 2,
      "job_title_department": "Assistant Engineer - Environmental",
      "vacancy_count": 10,
      "category": "UR",
      "pay_scale": "₹25,000 - ₹40,000",
      "qualification": "B.Tech/BE in Environmental Engineering",
      "fee_general": 500.00,
      "fee_sc_st": 250.00,
      "fee_obc": 375.00,
      "application_start_date": "2025-10-01",
      "application_end_date": "2025-12-31"
    }
  }
}
```

##### **B. Application Updated (200 OK)**
```json
{
  "success": true,
  "message": "Job application status updated successfully",
  "data": {
    "id": 15,
    "job_id": 2,
    "user_id": 123,
    "status": "in_progress",
    "stage": 3,
    "stage_name": "employment",
    "inserted_at": "2025-11-01T10:30:00.000000Z",
    "updated_at": "2025-11-01T11:15:00.000000Z",
    "changes_made": {
      "stage_updated": true,
      "status_updated": false,
      "previous_stage": 2,
      "previous_status": "in_progress"
    }
  }
}
```

#### **Error Responses:**

##### **Missing Token (401 Unauthorized)**
```json
{
  "success": false,
  "message": "Token not provided. Please login first.",
  "error_code": "TOKEN_MISSING"
}
```

##### **Invalid Job ID (422 Validation Error)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "job_id": [
      "The selected job id is invalid."
    ]
  }
}
```

##### **Job Not Available (400 Bad Request)**
```json
{
  "success": false,
  "message": "This job is not available for new applications",
  "debug_info": {
    "job_status": null,
    "application_start_date": "2025-12-01",
    "application_end_date": "2025-10-31",
    "current_date": "2025-11-01",
    "status_check": "N/A",
    "start_date_check": "FAIL",
    "end_date_check": "FAIL"
  }
}
```

##### **Invalid Stage/Status (422 Validation Error)**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "stage": [
      "The stage must be between 0 and 7."
    ],
    "status": [
      "The selected status is invalid."
    ]
  }
}
```

---

## 2️⃣ **Get Available Jobs API**

### **Endpoint:** `GET /api/getAvailableJobs`

**Description:** Retrieves all available jobs with user's application status for each job.

#### **Query Parameters (Optional):**
```
status=active          // Filter by job status
category=UR            // Filter by category (UR, OBC, SC, ST, EWS)
application_open_only=true  // Only jobs available for application
search=engineer        // Search in job title, qualification, pay scale
```

#### **Request Example:**
```
GET /api/getAvailableJobs?category=UR&search=engineer
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Available jobs retrieved successfully",
  "user_id": 123,
  "data": [
    {
      "id": 1,
      "job_title_department": "Software Engineer - IT Department",
      "vacancy_count": 5,
      "category": "UR",
      "category_name": "Unreserved",
      "pay_scale": "₹50,000 - ₹80,000",
      "qualification": "B.Tech/BE in Computer Science",
      "fee_general": 500.00,
      "fee_sc_st": 250.00,
      "fee_obc": 375.00,
      "application_start_date": "2025-10-01",
      "application_end_date": "2025-12-31",
      "is_application_open": true,
      "user_application_status": {
        "has_applied": false,
        "can_apply": true
      },
      "created_at": "2025-10-15T08:00:00.000000Z"
    },
    {
      "id": 2,
      "job_title_department": "Assistant Engineer - Environmental",
      "vacancy_count": 10,
      "category": "UR",
      "category_name": "Unreserved",
      "pay_scale": "₹25,000 - ₹40,000",
      "qualification": "B.Tech/BE in Environmental Engineering",
      "fee_general": 500.00,
      "fee_sc_st": 250.00,
      "fee_obc": 375.00,
      "application_start_date": "2025-10-01",
      "application_end_date": "2025-12-31",
      "is_application_open": true,
      "user_application_status": {
        "has_applied": true,
        "application_id": 15,
        "status": "in_progress",
        "stage": 3,
        "stage_name": "employment",
        "applied_at": "2025-11-01T10:30:00.000000Z"
      },
      "created_at": "2025-10-15T08:00:00.000000Z"
    }
  ],
  "total_count": 2,
  "filters_applied": {
    "status": "active",
    "category": "UR",
    "application_open_only": null,
    "search": "engineer"
  }
}
```

---

## 3️⃣ **Get Selected Jobs API**

### **Endpoint:** `GET /api/getSelectedJobs`

**Description:** Retrieves all jobs that the authenticated user has selected/applied for.

#### **Request Example:**
```
GET /api/getSelectedJobs
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Selected jobs retrieved successfully",
  "user_id": 123,
  "data": [
    {
      "application_id": 15,
      "job_id": 2,
      "application_status": {
        "status": "in_progress",
        "current_stage": 3,
        "current_stage_name": "employment",
        "is_completed": false,
        "created_at": "2025-11-01T10:30:00.000000Z",
        "updated_at": "2025-11-01T11:15:00.000000Z"
      },
      "job_details": {
        "id": 2,
        "job_title_department": "Assistant Engineer - Environmental",
        "vacancy_count": 10,
        "category": "UR",
        "pay_scale": "₹25,000 - ₹40,000",
        "qualification": "B.Tech/BE in Environmental Engineering",
        "fee_general": 500.00,
        "fee_sc_st": 250.00,
        "fee_obc": 375.00,
        "application_start_date": "2025-10-01",
        "application_end_date": "2025-12-31",
        "is_application_open": true
      },
      "completion_percentage": 37.5
    }
  ],
  "total_count": 1,
  "summary": {
    "total_selected": 1,
    "completed_applications": 0,
    "draft_applications": 1
  }
}
```

#### **Empty Response (200 OK):**
```json
{
  "success": true,
  "message": "No jobs selected yet",
  "data": [],
  "total_count": 0
}
```

---

## 4️⃣ **Job Posting Management APIs**

### **A. Create Job Posting**

#### **Endpoint:** `POST /api/createJobPosting`

#### **Request Example:**
```json
{
  "job_title_department": "Software Engineer - IT Department",
  "vacancy_count": 5,
  "category": "UR",
  "pay_scale": "₹50,000 - ₹80,000",
  "qualification": "B.Tech/BE in Computer Science with 2+ years experience",
  "fee_general": 500.00,
  "fee_sc_st": 250.00,
  "fee_obc": 375.00,
  "application_start_date": "2025-11-15",
  "application_end_date": "2025-12-31"
}
```

#### **Response Example (201 Created):**
```json
{
  "success": true,
  "message": "Job posting created successfully",
  "data": {
    "id": 10,
    "job_title_department": "Software Engineer - IT Department",
    "vacancy_count": 5,
    "category": "UR",
    "pay_scale": "₹50,000 - ₹80,000",
    "qualification": "B.Tech/BE in Computer Science with 2+ years experience",
    "fee_general": 500.00,
    "fee_sc_st": 250.00,
    "fee_obc": 375.00,
    "application_start_date": "2025-11-15",
    "application_end_date": "2025-12-31",
    "created_at": "2025-11-01T12:00:00.000000Z",
    "updated_at": "2025-11-01T12:00:00.000000Z"
  }
}
```

### **B. Get All Job Postings**

#### **Endpoint:** `GET /api/getAllJobPostings`

#### **Query Parameters (Optional):**
```
category=UR
search=engineer
limit=10
page=1
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Job postings retrieved successfully",
  "data": [
    {
      "id": 1,
      "job_title_department": "Software Engineer - IT Department",
      "vacancy_count": 5,
      "category": "UR",
      "category_name": "Unreserved",
      "pay_scale": "₹50,000 - ₹80,000",
      "qualification": "B.Tech/BE in Computer Science",
      "fee_general": 500.00,
      "fee_sc_st": 250.00,
      "fee_obc": 375.00,
      "application_start_date": "2025-10-01",
      "application_end_date": "2025-12-31",
      "applications_count": 25,
      "is_application_open": true,
      "created_at": "2025-10-15T08:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_records": 25,
    "per_page": 10
  }
}
```

### **C. Get Job Posting by ID**

#### **Endpoint:** `POST /api/getJobPostingById`

#### **Request Example:**
```json
{
  "job_id": 2
}
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Job posting retrieved successfully",
  "data": {
    "id": 2,
    "job_title_department": "Assistant Engineer - Environmental",
    "vacancy_count": 10,
    "category": "UR",
    "category_name": "Unreserved",
    "pay_scale": "₹25,000 - ₹40,000",
    "qualification": "B.Tech/BE in Environmental Engineering",
    "fee_general": 500.00,
    "fee_sc_st": 250.00,
    "fee_obc": 375.00,
    "application_start_date": "2025-10-01",
    "application_end_date": "2025-12-31",
    "applications_count": 45,
    "is_application_open": true,
    "created_at": "2025-10-15T08:00:00.000000Z",
    "updated_at": "2025-10-15T08:00:00.000000Z"
  }
}
```

### **D. Update Job Posting**

#### **Endpoint:** `POST /api/updateJobPosting`

#### **Request Example:**
```json
{
  "job_id": 2,
  "vacancy_count": 15,
  "application_end_date": "2025-12-31"
}
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Job posting updated successfully",
  "data": {
    "id": 2,
    "job_title_department": "Assistant Engineer - Environmental",
    "vacancy_count": 15,
    "category": "UR",
    "pay_scale": "₹25,000 - ₹40,000",
    "qualification": "B.Tech/BE in Environmental Engineering",
    "fee_general": 500.00,
    "fee_sc_st": 250.00,
    "fee_obc": 375.00,
    "application_start_date": "2025-10-01",
    "application_end_date": "2025-12-31",
    "updated_at": "2025-11-01T12:30:00.000000Z"
  },
  "updated_fields": ["vacancy_count", "application_end_date"]
}
```

### **E. Delete Job Posting**

#### **Endpoint:** `POST /api/deleteJobPosting`

#### **Request Example:**
```json
{
  "job_id": 10
}
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Job posting deleted successfully",
  "deleted_job_id": 10
}
```

#### **Error Response - Has Applications (400 Bad Request):**
```json
{
  "success": false,
  "message": "Cannot delete job posting as it has applications",
  "applications_count": 15
}
```

---

## 5️⃣ **Application Progress APIs**

### **Get Application Progress**

#### **Endpoint:** `POST /api/getApplicationProgress`

#### **Request Example:**
```json
{
  "user_id": 123,
  "job_id": 2
}
```

#### **Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Application progress retrieved successfully",
  "user_id": 123,
  "job_id": 2,
  "application_status": {
    "id": 15,
    "status": "in_progress",
    "current_stage": 3,
    "current_stage_name": "employment",
    "is_completed": false
  },
  "progress": {
    "completion_percentage": 37.5,
    "completed_sections": ["job_selection", "personal_details", "qualification"],
    "next_section": "employment",
    "sections_status": {
      "job_selection": true,
      "personal_details": true,
      "qualification": true,
      "employment": false,
      "file_upload": false,
      "application_summary": false,
      "payment": false,
      "print_application": false
    }
  },
  "redirect_to": {
    "section": "employment",
    "action": "fill_section",
    "message": "Qualification details saved! Now add your employment history"
  }
}
```

---

## 🧪 **Testing Scenarios**

### **Complete Application Flow Testing:**

1. **Job Selection:** `POST /api/saveSelectedJob` with `{"job_id": 2}`
2. **Personal Details:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 1}`
3. **Qualification:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 2}`
4. **Employment:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 3}`
5. **File Upload:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 4}`
6. **Application Summary:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 5}`
7. **Payment:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 6, "status": "under_review"}`
8. **Print Application:** `POST /api/saveSelectedJob` with `{"job_id": 2, "stage": 7, "status": "submitted"}`

### **Postman Test Scripts:**

```javascript
// Store token from login response
pm.test("Store JWT token", function () {
    var jsonData = pm.response.json();
    if (jsonData.token) {
        pm.environment.set("jwt_token", jsonData.token);
    }
});

// Test successful response
pm.test("Status code is success", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});

pm.test("Response has success property", function () {
    pm.expect(pm.response.json()).to.have.property('success', true);
});

// Store application ID for future requests
pm.test("Store application data", function () {
    const jsonData = pm.response.json();
    if (jsonData.success && jsonData.data) {
        if (jsonData.data.id) {
            pm.environment.set("application_id", jsonData.data.id);
        }
        if (jsonData.data.job_id) {
            pm.environment.set("job_id", jsonData.data.job_id);
        }
    }
});
```

---

## 🔐 **Security & Validation**

### **Authentication:**
- All APIs require valid JWT token
- Token validation includes expiry check
- User context is automatically added to requests

### **Authorization:**
- Users can only access their own applications
- Admin users can manage job postings
- Stage progression is enforced (no backward movement)

### **Data Validation:**
- All inputs are validated according to database constraints
- Enum values are strictly enforced
- Date validations ensure logical application periods
- File uploads have type and size restrictions

### **Error Handling:**
- Consistent error response format
- Detailed validation error messages
- Debug information for application availability issues
- Proper HTTP status codes

---

## 📝 **Notes**

1. **Stage Progression:** Users can only move forward in stages (0→1→2→...→7)
2. **Status Updates:** Status can be updated independently of stage
3. **Job Availability:** Based on application start/end dates only (no status column needed)
4. **Application Uniqueness:** One application per user per job
5. **Data Persistence:** All changes are tracked with timestamps

This documentation covers all job-related APIs with complete request/response examples and business logic conditions.