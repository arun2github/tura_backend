 # Complete API Documentation with Request/Response Examples

## 📋 Table of Contents
1. [Authentication APIs](#authentication-apis)
2. [Job Application APIs](#job-application-apis)
3. [Payment APIs](#payment-apis)
4. [Flutter UI Integration Examples](#flutter-ui-integration-examples)
5. [Error Handling](#error-handling)
6. [Complete Flow Examples](#complete-flow-examples)

---

## 🔐 Authentication APIs

### **1. Login API**
**Endpoint:** `POST /api/login`

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "phone_no": "9876543210",
            "role": "user"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

**Error Response (401):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### **2. Register API**
**Endpoint:** `POST /api/register`

**Request:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "phone_no": "9876543210",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "phone_no": "9876543210",
            "role": "user"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

---

## 💼 Job Application APIs

### **1. Get Jobs for Application (Stage 0)**
**Endpoint:** `GET /api/getJobsForApplication`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:** No body required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Jobs retrieved successfully",
    "data": [
        {
            "id": 1,
            "post_name": "Junior Engineer",
            "department": "Public Works Department",
            "total_posts": 10,
            "last_date_to_apply": "2025-12-31",
            "application_fee_general": 500,
            "application_fee_obc": 300,
            "application_fee_sc_st": 100,
            "application_status": null,
            "current_stage": null,
            "payment_status": null,
            "status": "not_applied"
        },
        {
            "id": 2,
            "post_name": "Clerk",
            "department": "Administration",
            "total_posts": 5,
            "last_date_to_apply": "2025-11-30",
            "application_fee_general": 300,
            "application_fee_obc": 200,
            "application_fee_sc_st": 50,
            "application_status": "draft",
            "current_stage": 2,
            "payment_status": "pending",
            "status": "in_progress"
        }
    ]
}
```

### **2. Get Application Progress with Resume**
**Endpoint:** `POST /api/getApplicationProgressWithResume`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1
}
```

**Success Response (200) - New Application:**
```json
{
    "success": true,
    "message": "Application progress retrieved successfully",
    "user_id": 1,
    "job_id": 1,
    "application_status": {
        "status": "draft",
        "current_stage": 1,
        "current_stage_name": "personal_details",
        "payment_status": "pending",
        "is_completed": false
    },
    "stage_completion": {
        "personal_details": false,
        "employment_details": false,
        "qualification_details": false,
        "document_upload": false
    },
    "existing_data": {
        "personal_details": null,
        "employment_details": [],
        "qualification_details": [],
        "document_upload": []
    },
    "redirect_to": {
        "stage": 1,
        "stage_name": "personal_details",
        "message": "Please fill your personal details to continue"
    }
}
```

**Success Response (200) - Resume Application:**
```json
{
    "success": true,
    "message": "Application progress retrieved successfully",
    "user_id": 1,
    "job_id": 1,
    "application_status": {
        "status": "draft",
        "current_stage": 3,
        "current_stage_name": "qualification_details",
        "payment_status": "pending",
        "is_completed": false
    },
    "stage_completion": {
        "personal_details": true,
        "employment_details": true,
        "qualification_details": false,
        "document_upload": false
    },
    "existing_data": {
        "personal_details": {
            "id": 1,
            "full_name": "John Doe",
            "date_of_birth": "1990-01-01",
            "gender": "male",
            "category": "general",
            "marital_status": "single"
        },
        "employment_details": [
            {
                "id": 1,
                "name_of_organization": "ABC Company",
                "designation": "Software Developer",
                "duration_in_months": 24,
                "monthly_salary": 50000
            }
        ],
        "qualification_details": [],
        "document_upload": []
    },
    "redirect_to": {
        "stage": 3,
        "stage_name": "qualification_details",
        "message": "Please add your qualification details"
    }
}
```

### **3. Save Personal Details (Stage 1)**
**Endpoint:** `POST /api/saveJobPersonalDetails`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1,
    "full_name": "John Doe",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "category": "general",
    "marital_status": "single",
    "father_name": "Robert Doe",
    "mother_name": "Jane Doe",
    "address": "123 Main Street",
    "city": "Mumbai",
    "state": "Maharashtra",
    "pincode": "400001",
    "phone_no": "9876543210",
    "email": "john@example.com",
    "emergency_contact": "9876543211"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Personal details saved successfully",
    "user_id": 1,
    "job_id": 1,
    "next_stage": 2,
    "next_stage_name": "employment_details",
    "data": {
        "id": 1,
        "full_name": "John Doe",
        "date_of_birth": "1990-01-01",
        "gender": "male",
        "category": "general",
        "marital_status": "single",
        "created_at": "2025-10-31T10:30:00.000000Z"
    }
}
```

### **4. Save Employment Details (Stage 2)**
**Endpoint:** `POST /api/saveJobEmploymentDetails`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1,
    "employment_details": [
        {
            "name_of_organization": "ABC Company",
            "designation": "Software Developer",
            "duration_in_months": 24,
            "monthly_salary": 50000,
            "from_date": "2022-01-01",
            "to_date": "2023-12-31",
            "reason_for_leaving": "Career Growth"
        },
        {
            "name_of_organization": "XYZ Corp",
            "designation": "Senior Developer",
            "duration_in_months": 12,
            "monthly_salary": 75000,
            "from_date": "2024-01-01",
            "to_date": "2024-12-31",
            "reason_for_leaving": "Current Job"
        }
    ]
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Employment details saved successfully",
    "user_id": 1,
    "job_id": 1,
    "next_stage": 3,
    "next_stage_name": "qualification_details",
    "data": [
        {
            "id": 1,
            "name_of_organization": "ABC Company",
            "designation": "Software Developer",
            "duration_in_months": 24,
            "monthly_salary": 50000,
            "created_at": "2025-10-31T10:35:00.000000Z"
        },
        {
            "id": 2,
            "name_of_organization": "XYZ Corp",
            "designation": "Senior Developer",
            "duration_in_months": 12,
            "monthly_salary": 75000,
            "created_at": "2025-10-31T10:35:00.000000Z"
        }
    ]
}
```

### **5. Save Qualification Details (Stage 3)**
**Endpoint:** `POST /api/saveJobQualificationDetails`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1,
    "qualifications": [
        {
            "additional_qualification": "Bachelor of Engineering",
            "institution_name": "Mumbai University",
            "year_of_passing": 2012,
            "percentage_obtained": 85.5,
            "grade_obtained": "First Class"
        },
        {
            "additional_qualification": "Master of Computer Applications",
            "institution_name": "Delhi University",
            "year_of_passing": 2014,
            "percentage_obtained": 78.0,
            "grade_obtained": "First Class"
        }
    ]
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Qualification details saved successfully",
    "user_id": 1,
    "job_id": 1,
    "next_stage": 4,
    "next_stage_name": "document_upload",
    "data": [
        {
            "id": 1,
            "additional_qualification": "Bachelor of Engineering",
            "institution_name": "Mumbai University",
            "year_of_passing": 2012,
            "percentage_obtained": 85.5,
            "created_at": "2025-10-31T10:40:00.000000Z"
        },
        {
            "id": 2,
            "additional_qualification": "Master of Computer Applications",
            "institution_name": "Delhi University",
            "year_of_passing": 2014,
            "percentage_obtained": 78.0,
            "created_at": "2025-10-31T10:40:00.000000Z"
        }
    ]
}
```

### **6. Upload Documents (Stage 4)**
**Endpoint:** `POST /api/uploadJobDocuments`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: multipart/form-data
```

**Request (Form Data):**
```
job_id: 1
document_type: resume
is_mandatory: true
file: [Resume.pdf]
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Document uploaded successfully",
    "user_id": 1,
    "job_id": 1,
    "data": {
        "id": 1,
        "document_type": "resume",
        "is_mandatory": true,
        "file_path": "/storage/documents/job_1/user_1/resume_1730367600.pdf",
        "file_size": "245760",
        "created_at": "2025-10-31T10:45:00.000000Z"
    }
}
```

### **7. Get Application Summary (Stage 5)**
**Endpoint:** `POST /api/getApplicationSummary`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Application summary retrieved successfully",
    "data": {
        "job_details": {
            "id": 1,
            "post_name": "Junior Engineer",
            "department": "Public Works Department",
            "total_posts": 10,
            "last_date_to_apply": "2025-12-31"
        },
        "personal_details": {
            "id": 1,
            "full_name": "John Doe",
            "date_of_birth": "1990-01-01",
            "gender": "male",
            "category": "general",
            "marital_status": "single",
            "father_name": "Robert Doe",
            "mother_name": "Jane Doe",
            "address": "123 Main Street",
            "city": "Mumbai",
            "state": "Maharashtra",
            "pincode": "400001",
            "phone_no": "9876543210",
            "email": "john@example.com"
        },
        "employment_details": [
            {
                "id": 1,
                "name_of_organization": "ABC Company",
                "designation": "Software Developer",
                "duration_in_months": 24,
                "monthly_salary": 50000,
                "from_date": "2022-01-01",
                "to_date": "2023-12-31"
            }
        ],
        "qualification_details": [
            {
                "id": 1,
                "additional_qualification": "Bachelor of Engineering",
                "institution_name": "Mumbai University",
                "year_of_passing": 2012,
                "percentage_obtained": 85.5,
                "grade_obtained": "First Class"
            }
        ],
        "document_details": [
            {
                "document_type": "resume",
                "is_mandatory": true,
                "uploaded_at": "2025-10-31T10:45:00.000000Z"
            },
            {
                "document_type": "photo",
                "is_mandatory": true,
                "uploaded_at": "2025-10-31T10:46:00.000000Z"
            }
        ],
        "payment_details": {
            "category": "general",
            "application_fee": 500,
            "currency": "INR"
        },
        "application_id": "APP2025001001001234"
    }
}
```

**Error Response (400) - Incomplete Application:**
```json
{
    "success": false,
    "message": "Application is incomplete. Please complete all sections.",
    "missing_sections": {
        "personal_details": false,
        "employment_details": false,
        "qualification_details": true,
        "document_upload": true
    }
}
```

---

## 💳 Payment APIs

### **8. Initiate Payment**
**Endpoint:** `POST /api/initiateApplicationPayment`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json
```

**Request:**
```json
{
    "job_id": 1
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Payment initiated successfully",
    "data": {
        "transaction_id": "TXN1730367800123",
        "amount": 500,
        "currency": "INR",
        "payment_gateway_data": {
            "key": "rzp_test_xxxxxxxxxx",
            "amount": 50000,
            "currency": "INR",
            "order_id": "TXN1730367800123",
            "name": "Municipal Board Job Application",
            "description": "Application fee for Junior Engineer",
            "prefill": {
                "name": "John Doe",
                "email": "john@example.com",
                "contact": "9876543210"
            },
            "notes": {
                "user_id": "1",
                "job_id": "1",
                "application_type": "job_application"
            },
            "callback_url": "http://localhost:8000/api/payment/callback"
        },
        "redirect_url": "http://localhost:8000/payment/process/TXN1730367800123"
    }
}
```

### **9. Payment Callback**
**Endpoint:** `POST /api/payment/callback`

**Request (from Payment Gateway):**
```json
{
    "order_id": "TXN1730367800123",
    "payment_id": "pay_xxxxxxxxxx",
    "signature": "xxxxxxxxxx",
    "status": "success"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Payment completed successfully",
    "data": {
        "transaction_id": "TXN1730367800123",
        "status": "completed",
        "redirect_url": "http://localhost:3000/application/success/TXN1730367800123"
    }
}
```

**Failed Payment Response (400):**
```json
{
    "success": false,
    "message": "Payment failed",
    "data": {
        "transaction_id": "TXN1730367800123",
        "status": "failed"
    }
}
```

---

## 📱 Flutter UI Integration Examples

### **1. API Service Implementation**
```dart
// lib/services/api_service.dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'http://your-domain.com/api';
  
  // Get headers with authentication
  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token') ?? '';
    
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  // Handle API response
  static Map<String, dynamic> _handleResponse(http.Response response) {
    try {
      final Map<String, dynamic> data = json.decode(response.body);
      
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return data;
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Request failed',
          'status_code': response.statusCode,
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Failed to parse response: $e',
      };
    }
  }

  // Get jobs for application
  static Future<Map<String, dynamic>> getJobsForApplication() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('$baseUrl/getJobsForApplication'),
        headers: headers,
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Get application progress with resume
  static Future<Map<String, dynamic>> getApplicationProgressWithResume(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getApplicationProgressWithResume'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Save personal details
  static Future<Map<String, dynamic>> savePersonalDetails(Map<String, dynamic> data) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveJobPersonalDetails'),
        headers: headers,
        body: json.encode(data),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Save employment details
  static Future<Map<String, dynamic>> saveEmploymentDetails(Map<String, dynamic> data) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveJobEmploymentDetails'),
        headers: headers,
        body: json.encode(data),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Save qualification details
  static Future<Map<String, dynamic>> saveQualificationDetails(Map<String, dynamic> data) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveJobQualificationDetails'),
        headers: headers,
        body: json.encode(data),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Upload document
  static Future<Map<String, dynamic>> uploadDocument(
    int jobId,
    String documentType,
    bool isMandatory,
    File file,
  ) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';
      
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/uploadJobDocuments'),
      );
      
      request.headers['Authorization'] = 'Bearer $token';
      request.fields['job_id'] = jobId.toString();
      request.fields['document_type'] = documentType;
      request.fields['is_mandatory'] = isMandatory.toString();
      
      request.files.add(
        await http.MultipartFile.fromPath('file', file.path),
      );
      
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Upload error: $e'};
    }
  }

  // Get application summary
  static Future<Map<String, dynamic>> getApplicationSummary(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getApplicationSummary'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Initiate payment
  static Future<Map<String, dynamic>> initiateApplicationPayment(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/initiateApplicationPayment'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
}
```

### **2. Usage in UI Widgets**

#### **Personal Details Form Example:**
```dart
// lib/screens/personal_details_screen.dart
class PersonalDetailsScreen extends StatefulWidget {
  final int jobId;
  final Map<String, dynamic>? existingData;
  
  const PersonalDetailsScreen({
    Key? key,
    required this.jobId,
    this.existingData,
  }) : super(key: key);
  
  @override
  _PersonalDetailsScreenState createState() => _PersonalDetailsScreenState();
}

class _PersonalDetailsScreenState extends State<PersonalDetailsScreen> {
  final _formKey = GlobalKey<FormState>();
  bool isLoading = false;
  
  // Form controllers
  final _fullNameController = TextEditingController();
  final _dobController = TextEditingController();
  String? _selectedGender;
  String? _selectedCategory;
  String? _selectedMaritalStatus;
  
  @override
  void initState() {
    super.initState();
    _prefillData();
  }
  
  void _prefillData() {
    if (widget.existingData != null) {
      _fullNameController.text = widget.existingData!['full_name'] ?? '';
      _dobController.text = widget.existingData!['date_of_birth'] ?? '';
      _selectedGender = widget.existingData!['gender'];
      _selectedCategory = widget.existingData!['category'];
      _selectedMaritalStatus = widget.existingData!['marital_status'];
    }
  }
  
  Future<void> _savePersonalDetails() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => isLoading = true);
    
    final data = {
      'job_id': widget.jobId,
      'full_name': _fullNameController.text,
      'date_of_birth': _dobController.text,
      'gender': _selectedGender,
      'category': _selectedCategory,
      'marital_status': _selectedMaritalStatus,
      // Add other fields...
    };
    
    final result = await ApiService.savePersonalDetails(data);
    
    setState(() => isLoading = false);
    
    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.green,
        ),
      );
      
      // Navigate to next stage
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (context) => EmploymentDetailsScreen(
            jobId: widget.jobId,
          ),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Error saving details'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Personal Details'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              // Full Name
              TextFormField(
                controller: _fullNameController,
                decoration: const InputDecoration(
                  labelText: 'Full Name *',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please enter your full name';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Date of Birth
              TextFormField(
                controller: _dobController,
                decoration: const InputDecoration(
                  labelText: 'Date of Birth *',
                  border: OutlineInputBorder(),
                  suffixIcon: Icon(Icons.calendar_today),
                ),
                readOnly: true,
                onTap: () async {
                  final date = await showDatePicker(
                    context: context,
                    initialDate: DateTime(1990),
                    firstDate: DateTime(1950),
                    lastDate: DateTime.now(),
                  );
                  if (date != null) {
                    _dobController.text = date.toString().split(' ')[0];
                  }
                },
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Please select your date of birth';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Gender Dropdown
              DropdownButtonFormField<String>(
                value: _selectedGender,
                decoration: const InputDecoration(
                  labelText: 'Gender *',
                  border: OutlineInputBorder(),
                ),
                items: ['male', 'female', 'other'].map((gender) {
                  return DropdownMenuItem(
                    value: gender,
                    child: Text(gender.toUpperCase()),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() => _selectedGender = value);
                },
                validator: (value) {
                  if (value == null) {
                    return 'Please select your gender';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 16),
              
              // Category Dropdown
              DropdownButtonFormField<String>(
                value: _selectedCategory,
                decoration: const InputDecoration(
                  labelText: 'Category *',
                  border: OutlineInputBorder(),
                ),
                items: ['general', 'obc', 'sc', 'st'].map((category) {
                  return DropdownMenuItem(
                    value: category,
                    child: Text(category.toUpperCase()),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() => _selectedCategory = value);
                },
                validator: (value) {
                  if (value == null) {
                    return 'Please select your category';
                  }
                  return null;
                },
              ),
              
              const SizedBox(height: 32),
              
              // Save Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: isLoading ? null : _savePersonalDetails,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue[800],
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text(
                          'Save & Continue',
                          style: TextStyle(fontSize: 16),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  @override
  void dispose() {
    _fullNameController.dispose();
    _dobController.dispose();
    super.dispose();
  }
}
```

#### **Job Selection with API Integration:**
```dart
// lib/screens/job_selection_screen.dart
class JobSelectionScreen extends StatefulWidget {
  @override
  _JobSelectionScreenState createState() => _JobSelectionScreenState();
}

class _JobSelectionScreenState extends State<JobSelectionScreen> {
  List<dynamic> jobs = [];
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadJobs();
  }

  Future<void> _loadJobs() async {
    setState(() => isLoading = true);

    final result = await ApiService.getJobsForApplication();

    if (result['success']) {
      setState(() {
        jobs = result['data'];
        isLoading = false;
      });
    } else {
      setState(() => isLoading = false);
      _showErrorSnackBar(result['message'] ?? 'Error loading jobs');
    }
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _startApplication(int jobId, String jobTitle) async {
    // Check application progress first
    final progressResult = await ApiService.getApplicationProgressWithResume(jobId);
    
    if (progressResult['success']) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => ApplicationFormScreen(
            jobId: jobId,
            jobTitle: jobTitle,
            progressData: progressResult,
          ),
        ),
      );
    } else {
      _showErrorSnackBar('Error loading application progress');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Select Job to Apply'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadJobs,
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: jobs.length,
                itemBuilder: (context, index) {
                  final job = jobs[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  job['post_name'] ?? 'N/A',
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              _buildStatusChip(job['status']),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text('Department: ${job['department'] ?? 'N/A'}'),
                          Text('Total Posts: ${job['total_posts'] ?? 'N/A'}'),
                          Text('Last Date: ${job['last_date_to_apply'] ?? 'N/A'}'),
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Fee: ₹${job['application_fee_general']}',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              ElevatedButton(
                                onPressed: () => _startApplication(
                                  job['id'],
                                  job['post_name'],
                                ),
                                child: Text(
                                  job['status'] == 'not_applied' ? 'Apply' : 'Continue',
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    String text;
    
    switch (status) {
      case 'completed':
        color = Colors.green;
        text = 'Completed';
        break;
      case 'in_progress':
        color = Colors.orange;
        text = 'In Progress';
        break;
      default:
        color = Colors.grey;
        text = 'Not Applied';
    }

    return Chip(
      label: Text(text, style: const TextStyle(color: Colors.white, fontSize: 12)),
      backgroundColor: color,
    );
  }
}
```

---

## ⚠️ Error Handling

### **Common Error Responses:**

#### **Authentication Errors:**
```json
{
    "success": false,
    "message": "Unauthenticated.",
    "status_code": 401
}
```

#### **Validation Errors:**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "job_id": ["The job id field is required."],
        "full_name": ["The full name field is required."]
    },
    "status_code": 400
}
```

#### **Server Errors:**
```json
{
    "success": false,
    "message": "Internal server error",
    "status_code": 500
}
```

### **Flutter Error Handling Example:**
```dart
class ErrorHandler {
  static void handleApiError(BuildContext context, Map<String, dynamic> result) {
    String message = result['message'] ?? 'An error occurred';
    
    if (result['status_code'] == 401) {
      // Redirect to login
      Navigator.pushReplacementNamed(context, '/login');
      return;
    }
    
    if (result['errors'] != null) {
      // Validation errors
      final errors = result['errors'] as Map<String, dynamic>;
      message = errors.values.first.first;
    }
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        action: SnackBarAction(
          label: 'OK',
          textColor: Colors.white,
          onPressed: () {
            ScaffoldMessenger.of(context).hideCurrentSnackBar();
          },
        ),
      ),
    );
  }
}
```

---

## 🔄 Complete Flow Examples

### **1. New Application Flow:**
```dart
// Step 1: Load jobs and select one
final jobsResult = await ApiService.getJobsForApplication();

// Step 2: Check application progress
final progressResult = await ApiService.getApplicationProgressWithResume(jobId);

// Step 3: Fill sections in order
await ApiService.savePersonalDetails(personalData);
await ApiService.saveEmploymentDetails(employmentData);
await ApiService.saveQualificationDetails(qualificationData);
await ApiService.uploadDocument(jobId, 'resume', true, resumeFile);

// Step 4: Get summary and proceed to payment
final summaryResult = await ApiService.getApplicationSummary(jobId);
final paymentResult = await ApiService.initiateApplicationPayment(jobId);
```

### **2. Resume Application Flow:**
```dart
// Check where user left off
final progressResult = await ApiService.getApplicationProgressWithResume(jobId);

if (progressResult['success']) {
  final currentStage = progressResult['application_status']['current_stage'];
  final existingData = progressResult['existing_data'];
  
  // Navigate to appropriate stage with existing data
  switch (currentStage) {
    case 1:
      Navigator.push(context, MaterialPageRoute(
        builder: (context) => PersonalDetailsScreen(
          jobId: jobId,
          existingData: existingData['personal_details'],
        ),
      ));
      break;
    case 2:
      Navigator.push(context, MaterialPageRoute(
        builder: (context) => EmploymentDetailsScreen(
          jobId: jobId,
          existingData: existingData['employment_details'],
        ),
      ));
      break;
    // ... other stages
  }
}
```

This comprehensive documentation provides all the request/response examples and UI integration patterns you need for implementing the complete job application flow! 🚀