# API Testing Guide for Job Selection APIs

## 🧪 Testing Setup

Since your tables already exist, let's test the APIs directly. Here are the exact API calls you can make:

## 📋 Prerequisites
- Laravel server running on `http://localhost:8000`
- Valid JWT token from login API
- At least one record in `tura_job_postings` table
- Valid user ID

---

## 🔐 Step 1: Get JWT Token

**Login API:**
```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "your_email@example.com",
    "password": "your_password"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

---

## 🧪 Step 2: Test Job Selection APIs

### **Test 1: Get Available Jobs**
```bash
GET http://localhost:8000/api/getAvailableJobs
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Available jobs retrieved successfully",
    "user_id": 1,
    "data": [
        {
            "id": 1,
            "job_title_department": "Assistant Engineer - Mechanical",
            "vacancy_count": 1,
            "category": "UR",
            "user_application_status": {
                "applied": false,
                "can_apply": true
            }
        }
    ]
}
```

### **Test 2: Save Selected Job**
```bash
POST http://localhost:8000/api/saveSelectedJob
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
    "job_id": 1
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Job selected successfully",
    "data": {
        "application_id": 2,
        "user_id": 1,
        "job_id": 1,
        "next_step": {
            "stage": 1,
            "stage_name": "personal_details",
            "message": "Job selected! Now please fill your personal details to continue your application."
        }
    }
}
```

### **Test 3: Get Selected Jobs**
```bash
GET http://localhost:8000/api/getSelectedJobs
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Selected jobs retrieved successfully",
    "user_id": 1,
    "data": [
        {
            "application_id": 1,
            "job_id": 1,
            "application_status": {
                "status": "submitted",
                "current_stage": 5,
                "is_completed": true
            },
            "completion_percentage": 100.0
        }
    ]
}
```

---

## 🐞 Debug Steps

### **Step 1: Check Route Registration**
```bash
php artisan route:list | grep -i job
```

### **Step 2: Check if APIs are accessible**
```bash
curl -X GET http://localhost:8000/api/getAvailableJobs \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### **Step 3: Check Laravel Logs**
```bash
tail -f storage/logs/laravel.log
```

---

## 🔧 Quick Test Commands

### **Test with cURL:**

```bash
# 1. Login to get token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# 2. Get available jobs (replace TOKEN)
curl -X GET http://localhost:8000/api/getAvailableJobs \
  -H "Authorization: Bearer TOKEN"

# 3. Select a job (replace TOKEN and job_id)
curl -X POST http://localhost:8000/api/saveSelectedJob \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"job_id":1}'

# 4. Get selected jobs
curl -X GET http://localhost:8000/api/getSelectedJobs \
  -H "Authorization: Bearer TOKEN"
```

---

## 📊 Expected Database Changes

After testing `saveSelectedJob`, check your database:

```sql
-- Should see new record in tura_job_applied_status
SELECT * FROM tura_job_applied_status WHERE user_id = 1 ORDER BY id DESC LIMIT 1;

-- Expected result:
-- id | job_id | user_id | status | stage | inserted_at | updated_at
-- 2  | 1      | 1       | draft  | 1     | 2025-11-01  | 2025-11-01
```

---

## ⚠️ Common Issues & Solutions

### **Issue 1: 401 Unauthorized**
- **Cause:** Invalid or expired JWT token
- **Solution:** Login again to get fresh token

### **Issue 2: 404 Not Found**
- **Cause:** Routes not registered
- **Solution:** Clear route cache: `php artisan route:clear`

### **Issue 3: 500 Internal Server Error**
- **Cause:** Database or code error
- **Solution:** Check `storage/logs/laravel.log` for detailed error

### **Issue 4: Token not found in request**
- **Cause:** JWT middleware not working
- **Solution:** Check if JWT is properly configured

---

## 🚀 Success Indicators

✅ **API Working Correctly If:**
1. `getAvailableJobs` returns jobs from `tura_job_postings`
2. `saveSelectedJob` creates new record in `tura_job_applied_status`
3. `getSelectedJobs` shows user's applied jobs with status
4. No errors in Laravel logs
5. Response times are reasonable (<1 second)

---

## 📱 Postman Collection

You can also create a Postman collection with these requests:

```json
{
    "info": {
        "name": "Job Selection APIs",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Login",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\"email\":\"test@example.com\",\"password\":\"password\"}"
                },
                "url": "http://localhost:8000/api/login"
            }
        },
        {
            "name": "Get Available Jobs",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ],
                "url": "http://localhost:8000/api/getAvailableJobs"
            }
        },
        {
            "name": "Save Selected Job",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    },
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\"job_id\":1}"
                },
                "url": "http://localhost:8000/api/saveSelectedJob"
            }
        },
        {
            "name": "Get Selected Jobs",
            "request": {
                "method": "GET",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ],
                "url": "http://localhost:8000/api/getSelectedJobs"
            }
        }
    ]
}
```

Copy this into Postman and set the `{{token}}` variable after login! 🚀