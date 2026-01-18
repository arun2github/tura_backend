# 📋 Admit Card API - Request/Response Documentation

## **Base URL:** 
```
Production: https://your-domain.com/api
Local: http://127.0.0.1:8000/api
```

---

## **1. Get Consolidated Exam Schedule**

### **Endpoint:** `POST /admit-card/exam-schedule`

### **Request:**
```json
{
  "email": "candidate@email.com"
}
```

### **Success Response (200):**
```json
{
  "status": true,
  "message": "Exam schedule retrieved successfully",
  "candidate_info": {
    "full_name": "John Doe",
    "email": "candidate@email.com", 
    "date_of_birth": "15-06-1995",
    "gender": "Male",
    "category": "General",
    "total_jobs_applied": 3,
    "total_exams": 4
  },
  "exam_schedule": {
    "total_papers": 4,
    "general_papers": 1, 
    "core_papers": 3,
    "has_conflicts": false,
    "papers": [
      {
        "paper_type": "General Awareness",
        "paper_number": 1,
        "subject": "General Knowledge & Reasoning",
        "exam_date": "2026-02-15",
        "exam_time": "10:00 AM - 12:00 PM", 
        "reporting_time": "09:30 AM",
        "venue_name": "ABC Examination Center",
        "venue_address": "123 Main Street, City",
        "applicable_for": "All Job Applications",
        "roll_number": "TR2026001, TR2026002, TR2026003",
        "application_id": "COMMON"
      },
      {
        "paper_type": "Core Paper", 
        "paper_number": 2,
        "subject": "Computer Science",
        "exam_date": "2026-02-16",
        "exam_time": "02:00 PM - 04:00 PM",
        "reporting_time": "01:30 PM", 
        "venue_name": "ABC Examination Center",
        "venue_address": "123 Main Street, City",
        "job_title": "Software Developer",
        "roll_number": "TR2026001",
        "application_id": "APP001"
      }
    ]
  },
  "warnings": [],
  "individual_admit_cards": [
    {
      "job_title": "Software Developer",
      "application_id": "APP001",
      "admit_no": "ADM001",
      "roll_number": "TR2026001",
      "individual_download_url": "https://domain.com/api/admit-card/download/ADM001"
    }
  ],
  "consolidated_download_url": "https://domain.com/api/admit-card/download-consolidated/candidate%40email.com"
}
```

### **Error Response (404):**
```json
{
  "status": false,
  "message": "No admit cards found for this email"
}
```

### **Error Response (422):**
```json
{
  "status": false,
  "message": "Invalid email format",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## **2. Download Consolidated PDF**

### **Endpoint:** `GET /admit-card/download-consolidated/{email}`

### **Example:** 
```
GET /admit-card/download-consolidated/candidate@email.com
```

### **Success Response:**
- **Content-Type:** `application/pdf`
- **Filename Format:** `candidate_at_email_com_admitcard_consolidated_2026-01-04_22-30-15.pdf`
- **Response:** Direct PDF file download

### **Error Response (404):**
```json
{
  "status": false,
  "message": "No admit cards found for this email"
}
```

### **Error Response (409) - Time Conflicts:**
```json
{
  "status": false,
  "message": "Cannot generate consolidated admit card due to exam time conflicts",
  "conflicts": [
    {
      "conflict_type": "time_overlap",
      "exam_date": "2026-02-15", 
      "paper1": {
        "type": "Core Paper",
        "subject": "Mathematics",
        "time": "10:00 AM - 12:00 PM",
        "roll_number": "TR2026001",
        "job": "Data Analyst"
      },
      "paper2": {
        "type": "Core Paper",
        "subject": "Statistics", 
        "time": "11:00 AM - 01:00 PM",
        "roll_number": "TR2026002",
        "job": "Research Analyst"
      }
    }
  ],
  "suggestion": "Please download individual admit cards or contact the examination authority to resolve conflicts."
}
```

---

## **🎯 Flutter Web Integration Summary:**

### **Step 1:** Get Exam Schedule
- **Method:** POST request to `/admit-card/exam-schedule`
- **Input:** Email as plain string
- **Output:** Complete exam schedule with candidate info

### **Step 2:** Download PDF
- **Method:** GET request to `/admit-card/download-consolidated/{email}`
- **Input:** Email as URL parameter (no encoding required)
- **Output:** Direct PDF file download

### **Key Features:**
- ✅ Email handling: Plain string format (no base64 encoding)
- ✅ PDF filename: Email-based with timestamp
- ✅ Time conflict detection: Automatic handling
- ✅ Multiple job support: Consolidated view
- ✅ Error handling: Comprehensive response codes

### **PDF Filename Format:**
```
{email_with_at_and_dots_replaced}_admitcard_consolidated_{timestamp}.pdf

Example: 
candidate_at_email_com_admitcard_consolidated_2026-01-04_22-30-15.pdf
```