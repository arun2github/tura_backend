# Job Selection API Documentation

## 📋 Overview
This guide covers the APIs for job selection functionality where users can view available jobs and select which ones they want to apply for.

## 🗃️ Database Tables

### **tura_job_postings** (Main Job Table)
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
- status (enum: active, inactive, draft)
- application_start_date (date)
- application_end_date (date)
- additional_info (text)
- created_at, updated_at (timestamps)
```

### **tura_job_applied_status** (User Job Selection Table)
```sql
- id (Primary Key)
- user_id (Foreign Key to users table)
- job_id (Foreign Key to tura_job_postings table)
- status (enum: draft, in_progress, submitted, under_review, approved, rejected)
- stage (integer: 0-form_selection, 1-job_selection, 2-personal_details, 3-qualification, 4-employment, 5-file_upload, 6-application_summary, 7-payment, 8-print_application)
- inserted_at, updated_at (timestamps)
```

## 📊 Application Stages

The job application process follows 8 stages:

1. **Job Selection (Stage 1)** - User selects which job to apply for
2. **Personal Details (Stage 2)** - User fills personal information
3. **Qualification (Stage 3)** - User adds educational qualifications
4. **Employment (Stage 4)** - User adds employment history
5. **File Upload (Stage 5)** - User uploads required documents
6. **Application Summary (Stage 6)** - User reviews all information
7. **Payment (Stage 7)** - User makes payment for application
8. **Print Application (Stage 8)** - User can print final application

---

## 🔗 API Endpoints

### **1. Get Available Jobs**
**Endpoint:** `GET /api/getAvailableJobs`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Query Parameters (Optional):**
```
status=active          // Filter by job status (active, inactive, draft)
category=UR           // Filter by category (UR, OBC, SC, ST, EWS)
application_open_only=true  // Show only jobs with open applications
search=engineer       // Search in job title, qualification, or pay scale
```

**Request Example:**
```
GET /api/getAvailableJobs?status=active&application_open_only=true
```

**Success Response (200):**
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
            "category_name": "Unreserved",
            "pay_scale": "15",
            "qualification": "Bachelor's Degree in Mechanical Engineering or Diploma...",
            "fee_general": 460.00,
            "fee_sc_st": 230.00,
            "fee_obc": 460.00,
            "status": "active",
            "application_start_date": "2025-11-01",
            "application_end_date": "2025-12-31",
            "additional_info": null,
            "is_application_open": true,
            "user_application_status": {
                "applied": false,
                "can_apply": true
            },
            "created_at": "2025-11-01T04:33:28.000000Z"
        },
        {
            "id": 2,
            "job_title_department": "Assistant Engineer - Environmental",
            "vacancy_count": 2,
            "category": "UR",
            "category_name": "Unreserved",
            "pay_scale": "15",
            "qualification": "Master's Degree in Environmental Sciences/Environmental Engineering...",
            "fee_general": 460.00,
            "fee_sc_st": 230.00,
            "fee_obc": 460.00,
            "status": "active",
            "application_start_date": "2025-11-01",
            "application_end_date": "2025-12-31",
            "additional_info": null,
            "is_application_open": true,
            "user_application_status": {
                "applied": true,
                "application_id": 5,
                "status": "draft",
                "stage": 2,
                "stage_name": "employment_details",
                "is_completed": false,
                "applied_at": "2025-11-01T10:30:00.000000Z"
            },
            "created_at": "2025-11-01T04:33:28.000000Z"
        }
    ],
    "total_count": 2,
    "filters_applied": {
        "status": "active",
        "category": null,
        "application_open_only": true,
        "search": null
    }
}
```

---

### **2. Save Selected Job**
**Endpoint:** `POST /api/saveSelectedJob`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
    "job_id": 1
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Job selected successfully",
    "data": {
        "application_id": 10,
        "user_id": 1,
        "job_id": 1,
        "selected_job": {
            "id": 1,
            "job_title_department": "Assistant Engineer - Mechanical",
            "vacancy_count": 1,
            "category": "UR",
            "pay_scale": "15",
            "qualification": "Bachelor's Degree in Mechanical Engineering or Diploma...",
            "fee_general": 460.00,
            "fee_sc_st": 230.00,
            "fee_obc": 460.00,
            "application_start_date": "2025-11-01",
            "application_end_date": "2025-12-31"
        },
        "application_status": {
            "status": "draft",
            "current_stage": 1,
            "current_stage_name": "personal_details",
            "is_completed": false
        },
        "next_step": {
            "stage": 1,
            "stage_name": "personal_details",
            "message": "Job selected! Now please fill your personal details to continue your application.",
            "action": "redirect_to_personal_details"
        }
    }
}
```

**Error Response (409) - Already Selected:**
```json
{
    "success": false,
    "message": "You have already selected this job",
    "existing_application": {
        "id": 5,
        "status": "draft",
        "stage": 2,
        "stage_name": "employment_details",
        "created_at": "2025-11-01T10:30:00.000000Z"
    },
    "action": "continue_application"
}
```

**Error Response (400) - Job Not Available:**
```json
{
    "success": false,
    "message": "This job is not available for application",
    "job_status": "inactive",
    "application_start_date": "2025-11-01",
    "application_end_date": "2025-10-31"
}
```

---

### **3. Get Selected Jobs**
**Endpoint:** `GET /api/getSelectedJobs`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Request:** No body required

**Success Response (200):**
```json
{
    "success": true,
    "message": "Selected jobs retrieved successfully",
    "user_id": 1,
    "data": [
        {
            "application_id": 10,
            "job_id": 1,
            "application_status": {
                "status": "draft",
                "current_stage": 2,
                "current_stage_name": "employment_details",
                "is_completed": false,
                "created_at": "2025-11-01T10:30:00.000000Z",
                "updated_at": "2025-11-01T11:15:00.000000Z"
            },
            "job_details": {
                "id": 1,
                "job_title_department": "Assistant Engineer - Mechanical",
                "vacancy_count": 1,
                "category": "UR",
                "pay_scale": "15",
                "qualification": "Bachelor's Degree in Mechanical Engineering...",
                "fee_general": 460.00,
                "fee_sc_st": 230.00,
                "fee_obc": 460.00,
                "status": "active",
                "application_start_date": "2025-11-01",
                "application_end_date": "2025-12-31",
                "is_application_open": true
            },
            "completion_percentage": 50.0
        },
        {
            "application_id": 11,
            "job_id": 3,
            "application_status": {
                "status": "completed",
                "current_stage": 5,
                "current_stage_name": "completed",
                "is_completed": true,
                "created_at": "2025-11-01T09:00:00.000000Z",
                "updated_at": "2025-11-01T12:00:00.000000Z"
            },
            "job_details": {
                "id": 3,
                "job_title_department": "Assistant Engineer - Hydrologist",
                "vacancy_count": 1,
                "category": "UR",
                "pay_scale": "15",
                "qualification": "Master's Degree in Environmental Engineering...",
                "fee_general": 460.00,
                "fee_sc_st": 230.00,
                "fee_obc": 460.00,
                "status": "active",
                "application_start_date": "2025-11-01",
                "application_end_date": "2025-12-31",
                "is_application_open": true
            },
            "completion_percentage": 100.0
        }
    ],
    "total_count": 2,
    "summary": {
        "total_selected": 2,
        "completed_applications": 1,
        "draft_applications": 1
    }
}
```

**Empty Response (200):**
```json
{
    "success": true,
    "message": "No jobs selected yet",
    "data": [],
    "total_count": 0
}
```

---

## 📱 Flutter Integration Examples

### **1. Job Selection Service**
```dart
// lib/services/job_selection_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class JobSelectionService {
  static const String baseUrl = 'http://your-domain.com/api';
  
  static Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token') ?? '';
    
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  // Get available jobs
  static Future<Map<String, dynamic>> getAvailableJobs({
    String? status,
    String? category,
    bool? applicationOpenOnly,
    String? search,
  }) async {
    try {
      final headers = await _getHeaders();
      
      // Build query parameters
      final queryParams = <String, String>{};
      if (status != null) queryParams['status'] = status;
      if (category != null) queryParams['category'] = category;
      if (applicationOpenOnly != null) queryParams['application_open_only'] = applicationOpenOnly.toString();
      if (search != null && search.isNotEmpty) queryParams['search'] = search;
      
      final uri = Uri.parse('$baseUrl/getAvailableJobs').replace(queryParameters: queryParams);
      final response = await http.get(uri, headers: headers);
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Save selected job
  static Future<Map<String, dynamic>> saveSelectedJob(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveSelectedJob'),
        headers: headers,
        body: json.encode({'job_id': jobId}),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Get selected jobs
  static Future<Map<String, dynamic>> getSelectedJobs() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('$baseUrl/getSelectedJobs'),
        headers: headers,
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  static Map<String, dynamic> _handleResponse(http.Response response) {
    try {
      final data = json.decode(response.body);
      return data;
    } catch (e) {
      return {'success': false, 'message': 'Failed to parse response'};
    }
  }
}
```

### **2. Job Selection Screen**
```dart
// lib/screens/job_selection_screen.dart
import 'package:flutter/material.dart';
import '../services/job_selection_service.dart';

class JobSelectionScreen extends StatefulWidget {
  @override
  _JobSelectionScreenState createState() => _JobSelectionScreenState();
}

class _JobSelectionScreenState extends State<JobSelectionScreen> {
  List<dynamic> availableJobs = [];
  bool isLoading = true;
  String? selectedCategory;
  String searchQuery = '';
  
  @override
  void initState() {
    super.initState();
    _loadAvailableJobs();
  }

  Future<void> _loadAvailableJobs() async {
    setState(() => isLoading = true);

    final result = await JobSelectionService.getAvailableJobs(
      status: 'active',
      category: selectedCategory,
      applicationOpenOnly: true,
      search: searchQuery.isNotEmpty ? searchQuery : null,
    );

    if (result['success']) {
      setState(() {
        availableJobs = result['data'];
        isLoading = false;
      });
    } else {
      setState(() => isLoading = false);
      _showErrorSnackBar(result['message'] ?? 'Error loading jobs');
    }
  }

  Future<void> _selectJob(int jobId, String jobTitle) async {
    // Show confirmation dialog
    final confirmed = await _showConfirmationDialog(jobTitle);
    if (!confirmed) return;

    final result = await JobSelectionService.saveSelectedJob(jobId);

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Job selected successfully!'),
          backgroundColor: Colors.green,
        ),
      );
      
      // Navigate to application form
      Navigator.pushNamed(
        context, 
        '/application-form',
        arguments: {
          'jobId': jobId,
          'applicationId': result['data']['application_id'],
        },
      );
    } else {
      _showErrorSnackBar(result['message'] ?? 'Error selecting job');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Available Jobs'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          // Search and Filter Section
          _buildSearchAndFilter(),
          
          // Jobs List
          Expanded(
            child: isLoading
                ? Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadAvailableJobs,
                    child: availableJobs.isEmpty
                        ? _buildEmptyState()
                        : ListView.builder(
                            itemCount: availableJobs.length,
                            itemBuilder: (context, index) {
                              final job = availableJobs[index];
                              return _buildJobCard(job);
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchAndFilter() {
    return Container(
      padding: EdgeInsets.all(16),
      child: Column(
        children: [
          // Search Bar
          TextField(
            decoration: InputDecoration(
              hintText: 'Search jobs...',
              prefixIcon: Icon(Icons.search),
              border: OutlineInputBorder(),
            ),
            onChanged: (value) {
              setState(() => searchQuery = value);
              _loadAvailableJobs();
            },
          ),
          
          SizedBox(height: 12),
          
          // Category Filter
          DropdownButtonFormField<String>(
            value: selectedCategory,
            decoration: InputDecoration(
              labelText: 'Filter by Category',
              border: OutlineInputBorder(),
            ),
            items: [
              DropdownMenuItem(value: null, child: Text('All Categories')),
              DropdownMenuItem(value: 'UR', child: Text('Unreserved (UR)')),
              DropdownMenuItem(value: 'OBC', child: Text('OBC')),
              DropdownMenuItem(value: 'SC', child: Text('SC')),
              DropdownMenuItem(value: 'ST', child: Text('ST')),
              DropdownMenuItem(value: 'EWS', child: Text('EWS')),
            ],
            onChanged: (value) {
              setState(() => selectedCategory = value);
              _loadAvailableJobs();
            },
          ),
        ],
      ),
    );
  }

  Widget _buildJobCard(dynamic job) {
    final hasApplied = job['user_application_status']['applied'];
    final canApply = job['user_application_status']['can_apply'] ?? false;
    
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Job Title and Status
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    job['job_title_department'],
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                _buildStatusChip(hasApplied, canApply),
              ],
            ),
            
            SizedBox(height: 8),
            
            // Job Details
            Text('Vacancies: ${job['vacancy_count']}'),
            Text('Category: ${job['category_name']}'),
            Text('Pay Scale: ${job['pay_scale']}'),
            
            SizedBox(height: 8),
            
            // Qualification (truncated)
            Text(
              'Qualification: ${job['qualification']}',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
            
            SizedBox(height: 12),
            
            // Fees
            Row(
              children: [
                Text('Fees - '),
                Text('General: ₹${job['fee_general']} | '),
                Text('SC/ST: ₹${job['fee_sc_st']} | '),
                Text('OBC: ₹${job['fee_obc']}'),
              ],
            ),
            
            SizedBox(height: 12),
            
            // Action Button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: hasApplied 
                    ? () => _navigateToApplication(job)
                    : canApply 
                        ? () => _selectJob(job['id'], job['job_title_department'])
                        : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: hasApplied ? Colors.orange : Colors.blue[800],
                  foregroundColor: Colors.white,
                ),
                child: Text(
                  hasApplied 
                      ? 'Continue Application'
                      : canApply 
                          ? 'Select This Job'
                          : 'Application Closed',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusChip(bool hasApplied, bool canApply) {
    if (hasApplied) {
      return Chip(
        label: Text('Applied', style: TextStyle(color: Colors.white)),
        backgroundColor: Colors.orange,
      );
    } else if (canApply) {
      return Chip(
        label: Text('Open', style: TextStyle(color: Colors.white)),
        backgroundColor: Colors.green,
      );
    } else {
      return Chip(
        label: Text('Closed', style: TextStyle(color: Colors.white)),
        backgroundColor: Colors.red,
      );
    }
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.work_off, size: 64, color: Colors.grey),
          SizedBox(height: 16),
          Text(
            'No jobs available',
            style: TextStyle(fontSize: 18, color: Colors.grey),
          ),
          SizedBox(height: 8),
          Text(
            'Check back later for new opportunities',
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Future<bool> _showConfirmationDialog(String jobTitle) async {
    return await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Select Job'),
        content: Text('Do you want to select "$jobTitle"? You can only apply for one position at a time.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text('Select'),
          ),
        ],
      ),
    ) ?? false;
  }

  void _navigateToApplication(dynamic job) {
    Navigator.pushNamed(
      context,
      '/application-form',
      arguments: {
        'jobId': job['id'],
        'applicationId': job['user_application_status']['application_id'],
      },
    );
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }
}
```

---

## 🔄 Complete Flow

### **1. User Journey:**
```
1. User logs in
2. Calls getAvailableJobs() → Shows all available jobs with application status
3. User selects a job
4. Calls saveSelectedJob() → Creates application record in draft status
5. System redirects to personal details form (Stage 1)
6. User continues with application stages...
```

### **2. API Integration Flow:**
```dart
// Step 1: Load available jobs
final jobsResult = await JobSelectionService.getAvailableJobs(
  status: 'active',
  applicationOpenOnly: true,
);

// Step 2: User selects a job
final selectionResult = await JobSelectionService.saveSelectedJob(jobId);

// Step 3: Navigate to application form
if (selectionResult['success']) {
  Navigator.pushNamed(context, '/application-form', arguments: {
    'jobId': jobId,
    'applicationId': selectionResult['data']['application_id'],
  });
}
```

This complete API system allows users to browse available jobs from the `tura_job_postings` table and select which ones they want to apply for. The selection is saved in the `tura_job_applied_status` table which then tracks their application progress through all the stages! 🚀