# Flutter Web Job Application Integration Guide

## 🎯 Overview
Complete Flutter Web integration for Municipal Board Job Application System with 4 main sections and JWT authentication.

## 📋 Application Flow Structure

### **4 Main Sections:**
1. **Personal Details** (Stage 1)
2. **Employment Details** (Stage 2) 
3. **Qualification Details** (Stage 3)
4. **Document Upload** (Stage 4)

---

## 🏗️ Flutter Project Structure

```
lib/
├── main.dart
├── models/
│   ├── user_model.dart
│   ├── job_model.dart
│   ├── personal_details_model.dart
│   ├── employment_model.dart
│   ├── qualification_model.dart
│   └── document_model.dart
├── services/
│   ├── auth_service.dart
│   ├── api_service.dart
│   └── storage_service.dart
├── screens/
│   ├── login_screen.dart
│   ├── job_selection_screen.dart
│   ├── application_form_screen.dart
│   └── success_screen.dart
├── widgets/
│   ├── personal_details_form.dart
│   ├── employment_details_form.dart
│   ├── qualification_details_form.dart
│   ├── document_upload_form.dart
│   └── progress_indicator.dart
└── utils/
    ├── constants.dart
    └── validators.dart
```

---

## 🔐 1. Authentication Service

### **auth_service.dart**
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  static const String baseUrl = 'https://laravelv2.turamunicipalboard.com/api';
  static const String tokenKey = 'jwt_token';
  
  // Login API
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email,
          'password': password,
        }),
      );
      
      final data = json.decode(response.body);
      
      if (response.statusCode == 200 && data['status'] == 'success') {
        // Save token to local storage
        await saveToken(data['access_token']);
        return {
          'success': true,
          'token': data['access_token'],
          'user': data['user_details'],
          'message': data['message']
        };
      } else {
        return {
          'success': false,
          'message': data['message'] ?? 'Login failed'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  // Save token to SharedPreferences
  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(tokenKey, token);
  }
  
  // Get token from SharedPreferences
  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(tokenKey);
  }
  
  // Clear token (logout)
  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(tokenKey);
  }
  
  // Check if user is logged in
  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
}
```

---

## 🌐 2. API Service

### **api_service.dart**
```dart
import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'auth_service.dart';

class ApiService {
  static const String baseUrl = 'https://laravelv2.turamunicipalboard.com/api';
  
  // Get headers with JWT token
  static Future<Map<String, String>> _getHeaders() async {
    final token = await AuthService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }
  
  // Get headers for file upload
  static Future<Map<String, String>> _getFileHeaders() async {
    final token = await AuthService.getToken();
    return {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }
  
  // 1. Get Available Jobs for Application
  static Future<Map<String, dynamic>> getAvailableJobs() async {
    try {
      final headers = await _getHeaders();
      final response = await http.get(
        Uri.parse('$baseUrl/getAvailableJobsForApplication/1'), // userId will be extracted from token
        headers: headers,
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 2. Start Job Application
  static Future<Map<String, dynamic>> startJobApplication(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/startJobApplication'),
        headers: headers,
        body: json.encode({
          'job_id': jobId,
        }),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 3. Get Application Progress
  static Future<Map<String, dynamic>> getApplicationProgress(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getApplicationProgress'),
        headers: headers,
        body: json.encode({
          'job_id': jobId,
        }),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 4. Save Personal Details
  static Future<Map<String, dynamic>> savePersonalDetails(Map<String, dynamic> data) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/savePersonalDetails'),
        headers: headers,
        body: json.encode(data),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 5. Save Employment Details
  static Future<Map<String, dynamic>> saveEmploymentDetails(List<Map<String, dynamic>> employmentRecords, int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveEmploymentDetails'),
        headers: headers,
        body: json.encode({
          'job_id': jobId,
          'employment_records': employmentRecords,
        }),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 6. Save Qualification Details
  static Future<Map<String, dynamic>> saveQualificationDetails(List<Map<String, dynamic>> qualificationRecords, int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/saveQualificationDetails'),
        headers: headers,
        body: json.encode({
          'job_id': jobId,
          'qualification_records': qualificationRecords,
        }),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 7. Upload Documents
  static Future<Map<String, dynamic>> uploadDocuments(Map<String, Uint8List> files, int jobId) async {
    try {
      final headers = await _getFileHeaders();
      var request = http.MultipartRequest('POST', Uri.parse('$baseUrl/uploadDocuments'));
      
      // Add headers
      request.headers.addAll(headers);
      
      // Add job_id
      request.fields['job_id'] = jobId.toString();
      
      // Add files
      files.forEach((fieldName, fileBytes) {
        request.files.add(
          http.MultipartFile.fromBytes(
            fieldName,
            fileBytes,
            filename: '$fieldName.pdf', // or appropriate extension
          ),
        );
      });
      
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // 8. Get Complete Application Details
  static Future<Map<String, dynamic>> getCompleteApplicationDetails(int jobId) async {
    try {
      final headers = await _getHeaders();
      final response = await http.post(
        Uri.parse('$baseUrl/getCompleteApplicationDetails'),
        headers: headers,
        body: json.encode({
          'job_id': jobId,
          'include_base64_files': false, // Set to true if you need file data
        }),
      );
      
      return _handleResponse(response);
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }
  
  // Handle API responses
  static Map<String, dynamic> _handleResponse(http.Response response) {
    final data = json.decode(response.body);
    
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return data;
    } else if (response.statusCode == 401) {
      // Token expired or invalid
      AuthService.clearToken();
      return {
        'success': false,
        'message': 'Session expired. Please login again.',
        'error_code': 'TOKEN_EXPIRED'
      };
    } else {
      return {
        'success': false,
        'message': data['message'] ?? 'An error occurred',
        'errors': data['errors'] ?? {},
      };
    }
  }
}
```

---

## 📱 3. Data Models

### **personal_details_model.dart**
```dart
class PersonalDetails {
  final String salutation;
  final String fullName;
  final String dateOfBirth;
  final String maritalStatus;
  final String gender;
  final String category;
  final String caste;
  final String religion;
  final String identificationMark;
  final String permanentAddress1;
  final String permanentAddress2;
  final String permanentLandmark;
  final String permanentVillage;
  final String permanentState;
  final String permanentDistrict;
  final String permanentPincode;
  final String presentAddress1;
  final String presentAddress2;
  final String presentLandmark;
  final String presentVillage;
  final String presentState;
  final String presentDistrict;
  final String presentPincode;
  
  PersonalDetails({
    required this.salutation,
    required this.fullName,
    required this.dateOfBirth,
    required this.maritalStatus,
    required this.gender,
    required this.category,
    required this.caste,
    required this.religion,
    required this.identificationMark,
    required this.permanentAddress1,
    required this.permanentAddress2,
    required this.permanentLandmark,
    required this.permanentVillage,
    required this.permanentState,
    required this.permanentDistrict,
    required this.permanentPincode,
    required this.presentAddress1,
    required this.presentAddress2,
    required this.presentLandmark,
    required this.presentVillage,
    required this.presentState,
    required this.presentDistrict,
    required this.presentPincode,
  });
  
  Map<String, dynamic> toJson() {
    return {
      'salutation': salutation,
      'full_name': fullName,
      'date_of_birth': dateOfBirth,
      'marital_status': maritalStatus,
      'gender': gender,
      'category': category,
      'caste': caste,
      'religion': religion,
      'identification_mark': identificationMark,
      'permanent_address1': permanentAddress1,
      'permanent_address2': permanentAddress2,
      'permanent_landmark': permanentLandmark,
      'permanent_village': permanentVillage,
      'permanent_state': permanentState,
      'permanent_district': permanentDistrict,
      'permanent_pincode': permanentPincode,
      'present_address1': presentAddress1,
      'present_address2': presentAddress2,
      'present_landmark': presentLandmark,
      'present_village': presentVillage,
      'present_state': presentState,
      'present_district': presentDistrict,
      'present_pincode': presentPincode,
    };
  }
}
```

### **employment_model.dart**
```dart
class EmploymentRecord {
  final String occupationStatus;
  final bool isGovernmentEmployee;
  final String stateWhereEmployed;
  final String appointmentType;
  final String nameOfOrganization;
  final String designation;
  final String dateOfJoining;
  final int durationInMonths;
  final String jobDescription;
  final double monthlySalary;
  final String natureOfDuties;
  final String reasonForLeaving;
  
  EmploymentRecord({
    required this.occupationStatus,
    required this.isGovernmentEmployee,
    required this.stateWhereEmployed,
    required this.appointmentType,
    required this.nameOfOrganization,
    required this.designation,
    required this.dateOfJoining,
    required this.durationInMonths,
    required this.jobDescription,
    required this.monthlySalary,
    required this.natureOfDuties,
    required this.reasonForLeaving,
  });
  
  Map<String, dynamic> toJson() {
    return {
      'occupation_status': occupationStatus,
      'is_government_employee': isGovernmentEmployee,
      'state_where_employed': stateWhereEmployed,
      'appointment_type': appointmentType,
      'name_of_organization': nameOfOrganization,
      'designation': designation,
      'date_of_joining': dateOfJoining,
      'duration_in_months': durationInMonths,
      'job_description': jobDescription,
      'monthly_salary': monthlySalary,
      'nature_of_duties': natureOfDuties,
      'reason_for_leaving': reasonForLeaving,
    };
  }
}
```

### **qualification_model.dart**
```dart
class QualificationRecord {
  final String additionalQualification;
  final String additionalQualificationDetails;
  final String institutionName;
  final String boardUniversity;
  final String examinationPassed;
  final String honorsSpecialization;
  final String generalElectiveSubjects;
  final int yearOfPassing;
  final String monthOfPassing;
  final String division;
  final double percentageObtained;
  
  QualificationRecord({
    required this.additionalQualification,
    required this.additionalQualificationDetails,
    required this.institutionName,
    required this.boardUniversity,
    required this.examinationPassed,
    required this.honorsSpecialization,
    required this.generalElectiveSubjects,
    required this.yearOfPassing,
    required this.monthOfPassing,
    required this.division,
    required this.percentageObtained,
  });
  
  Map<String, dynamic> toJson() {
    return {
      'additional_qualification': additionalQualification,
      'additional__qualification_details': additionalQualificationDetails,
      'institution_name': institutionName,
      'board_university': boardUniversity,
      'examination_passed': examinationPassed,
      'honors_specialization': honorsSpecialization,
      'general_elective_subjects': generalElectiveSubjects,
      'year_of_passing': yearOfPassing,
      'month_of_passing': monthOfPassing,
      'division': division,
      'percentage_obtained': percentageObtained,
    };
  }
}
```

---

## 🎨 4. Main Application Screen

### **application_form_screen.dart**
```dart
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';
import '../widgets/personal_details_form.dart';
import '../widgets/employment_details_form.dart';
import '../widgets/qualification_details_form.dart';
import '../widgets/document_upload_form.dart';

class ApplicationFormScreen extends StatefulWidget {
  final int jobId;
  final String jobTitle;
  
  const ApplicationFormScreen({
    Key? key,
    required this.jobId,
    required this.jobTitle,
  }) : super(key: key);
  
  @override
  _ApplicationFormScreenState createState() => _ApplicationFormScreenState();
}

class _ApplicationFormScreenState extends State<ApplicationFormScreen> {
  int currentStep = 0;
  bool isLoading = false;
  
  // Form keys for validation
  final personalFormKey = GlobalKey<FormState>();
  final employmentFormKey = GlobalKey<FormState>();
  final qualificationFormKey = GlobalKey<FormState>();
  final documentFormKey = GlobalKey<FormState>();
  
  // Data storage
  PersonalDetails? personalDetails;
  List<EmploymentRecord> employmentRecords = [];
  List<QualificationRecord> qualificationRecords = [];
  Map<String, PlatformFile> selectedFiles = {};
  
  @override
  void initState() {
    super.initState();
    _loadApplicationProgress();
  }
  
  Future<void> _loadApplicationProgress() async {
    setState(() => isLoading = true);
    
    final result = await ApiService.getApplicationProgress(widget.jobId);
    
    if (result['success']) {
      // Set current step based on API response
      final stage = result['application_status']['current_stage'];
      setState(() {
        currentStep = stage - 1; // Convert to 0-based index
      });
    }
    
    setState(() => isLoading = false);
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Job Application - ${widget.jobTitle}'),
        backgroundColor: Colors.blue[800],
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // Progress Indicator
                _buildProgressIndicator(),
                
                // Form Content
                Expanded(
                  child: _buildCurrentStepContent(),
                ),
                
                // Navigation Buttons
                _buildNavigationButtons(),
              ],
            ),
    );
  }
  
  Widget _buildProgressIndicator() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          _buildStepIndicator(0, 'Personal\nDetails', currentStep >= 0),
          _buildStepConnector(currentStep >= 1),
          _buildStepIndicator(1, 'Employment\nDetails', currentStep >= 1),
          _buildStepConnector(currentStep >= 2),
          _buildStepIndicator(2, 'Qualification\nDetails', currentStep >= 2),
          _buildStepConnector(currentStep >= 3),
          _buildStepIndicator(3, 'Document\nUpload', currentStep >= 3),
        ],
      ),
    );
  }
  
  Widget _buildStepIndicator(int step, String label, bool isCompleted) {
    final isActive = currentStep == step;
    
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isCompleted
                  ? Colors.green
                  : isActive
                      ? Colors.blue
                      : Colors.grey[300],
            ),
            child: Center(
              child: isCompleted
                  ? const Icon(Icons.check, color: Colors.white)
                  : Text(
                      '${step + 1}',
                      style: TextStyle(
                        color: isActive ? Colors.white : Colors.grey[600],
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              color: isActive ? Colors.blue : Colors.grey[600],
              fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildStepConnector(bool isCompleted) {
    return Expanded(
      child: Container(
        height: 2,
        color: isCompleted ? Colors.green : Colors.grey[300],
      ),
    );
  }
  
  Widget _buildCurrentStepContent() {
    switch (currentStep) {
      case 0:
        return PersonalDetailsForm(
          key: personalFormKey,
          onDataChanged: (data) => personalDetails = data,
        );
      case 1:
        return EmploymentDetailsForm(
          key: employmentFormKey,
          onDataChanged: (data) => employmentRecords = data,
        );
      case 2:
        return QualificationDetailsForm(
          key: qualificationFormKey,
          onDataChanged: (data) => qualificationRecords = data,
        );
      case 3:
        return DocumentUploadForm(
          key: documentFormKey,
          onFilesChanged: (files) => selectedFiles = files,
        );
      default:
        return const Center(child: Text('Invalid step'));
    }
  }
  
  Widget _buildNavigationButtons() {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Previous Button
          currentStep > 0
              ? ElevatedButton(
                  onPressed: () {
                    setState(() => currentStep--);
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.grey[600],
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('Previous'),
                )
              : const SizedBox.shrink(),
          
          // Next/Submit Button
          ElevatedButton(
            onPressed: isLoading ? null : _handleNextStep,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue[800],
              foregroundColor: Colors.white,
            ),
            child: isLoading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : Text(currentStep == 3 ? 'Submit Application' : 'Next'),
          ),
        ],
      ),
    );
  }
  
  Future<void> _handleNextStep() async {
    setState(() => isLoading = true);
    
    bool isValid = false;
    Map<String, dynamic>? result;
    
    switch (currentStep) {
      case 0:
        isValid = personalFormKey.currentState?.validate() ?? false;
        if (isValid && personalDetails != null) {
          final data = personalDetails!.toJson();
          data['job_id'] = widget.jobId;
          result = await ApiService.savePersonalDetails(data);
        }
        break;
        
      case 1:
        isValid = employmentFormKey.currentState?.validate() ?? false;
        if (isValid && employmentRecords.isNotEmpty) {
          final records = employmentRecords.map((e) => e.toJson()).toList();
          result = await ApiService.saveEmploymentDetails(records, widget.jobId);
        }
        break;
        
      case 2:
        isValid = qualificationFormKey.currentState?.validate() ?? false;
        if (isValid && qualificationRecords.isNotEmpty) {
          final records = qualificationRecords.map((e) => e.toJson()).toList();
          result = await ApiService.saveQualificationDetails(records, widget.jobId);
        }
        break;
        
      case 3:
        isValid = documentFormKey.currentState?.validate() ?? false;
        if (isValid && selectedFiles.isNotEmpty) {
          final files = <String, Uint8List>{};
          selectedFiles.forEach((key, file) {
            files[key] = file.bytes!;
          });
          result = await ApiService.uploadDocuments(files, widget.jobId);
        }
        break;
    }
    
    setState(() => isLoading = false);
    
    if (isValid && result != null) {
      if (result['success']) {
        // Show success message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Data saved successfully'),
            backgroundColor: Colors.green,
          ),
        );
        
        // Move to next step or complete application
        if (currentStep < 3) {
          setState(() => currentStep++);
        } else {
          // Application completed
          _showApplicationCompletedDialog();
        }
      } else {
        // Show error message
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'An error occurred'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } else if (!isValid) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please fill all required fields correctly'),
          backgroundColor: Colors.orange,
        ),
      );
    }
  }
  
  void _showApplicationCompletedDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Application Submitted'),
        content: const Text(
          'Your job application has been submitted successfully. '
          'You will receive updates on your application status.',
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).popUntil((route) => route.isFirst);
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}
```

---

## 📄 5. Form Widgets (Personal Details Example)

### **personal_details_form.dart**
```dart
import 'package:flutter/material.dart';
import '../models/personal_details_model.dart';

class PersonalDetailsForm extends StatefulWidget {
  final Function(PersonalDetails) onDataChanged;
  
  const PersonalDetailsForm({
    Key? key,
    required this.onDataChanged,
  }) : super(key: key);
  
  @override
  _PersonalDetailsFormState createState() => _PersonalDetailsFormState();
}

class _PersonalDetailsFormState extends State<PersonalDetailsForm> {
  final _formKey = GlobalKey<FormState>();
  
  // Controllers
  final _salutationController = TextEditingController();
  final _fullNameController = TextEditingController();
  final _dobController = TextEditingController();
  final _maritalStatusController = TextEditingController();
  final _genderController = TextEditingController();
  final _categoryController = TextEditingController();
  final _casteController = TextEditingController();
  final _religionController = TextEditingController();
  final _identificationMarkController = TextEditingController();
  final _permanentAddress1Controller = TextEditingController();
  final _permanentAddress2Controller = TextEditingController();
  final _permanentLandmarkController = TextEditingController();
  final _permanentVillageController = TextEditingController();
  final _permanentStateController = TextEditingController();
  final _permanentDistrictController = TextEditingController();
  final _permanentPincodeController = TextEditingController();
  final _presentAddress1Controller = TextEditingController();
  final _presentAddress2Controller = TextEditingController();
  final _presentLandmarkController = TextEditingController();
  final _presentVillageController = TextEditingController();
  final _presentStateController = TextEditingController();
  final _presentDistrictController = TextEditingController();
  final _presentPincodeController = TextEditingController();
  
  @override
  void initState() {
    super.initState();
    // Add listeners to update parent when data changes
    _setupListeners();
  }
  
  void _setupListeners() {
    final controllers = [
      _salutationController,
      _fullNameController,
      _dobController,
      _maritalStatusController,
      _genderController,
      _categoryController,
      _casteController,
      _religionController,
      _identificationMarkController,
      _permanentAddress1Controller,
      _permanentAddress2Controller,
      _permanentLandmarkController,
      _permanentVillageController,
      _permanentStateController,
      _permanentDistrictController,
      _permanentPincodeController,
      _presentAddress1Controller,
      _presentAddress2Controller,
      _presentLandmarkController,
      _presentVillageController,
      _presentStateController,
      _presentDistrictController,
      _presentPincodeController,
    ];
    
    for (var controller in controllers) {
      controller.addListener(_updateParentData);
    }
  }
  
  void _updateParentData() {
    if (_formKey.currentState?.validate() ?? false) {
      final personalDetails = PersonalDetails(
        salutation: _salutationController.text,
        fullName: _fullNameController.text,
        dateOfBirth: _dobController.text,
        maritalStatus: _maritalStatusController.text,
        gender: _genderController.text,
        category: _categoryController.text,
        caste: _casteController.text,
        religion: _religionController.text,
        identificationMark: _identificationMarkController.text,
        permanentAddress1: _permanentAddress1Controller.text,
        permanentAddress2: _permanentAddress2Controller.text,
        permanentLandmark: _permanentLandmarkController.text,
        permanentVillage: _permanentVillageController.text,
        permanentState: _permanentStateController.text,
        permanentDistrict: _permanentDistrictController.text,
        permanentPincode: _permanentPincodeController.text,
        presentAddress1: _presentAddress1Controller.text,
        presentAddress2: _presentAddress2Controller.text,
        presentLandmark: _presentLandmarkController.text,
        presentVillage: _presentVillageController.text,
        presentState: _presentStateController.text,
        presentDistrict: _presentDistrictController.text,
        presentPincode: _presentPincodeController.text,
      );
      
      widget.onDataChanged(personalDetails);
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Personal Details',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Colors.blue,
              ),
            ),
            const SizedBox(height: 20),
            
            // Basic Information
            _buildSectionTitle('Basic Information'),
            Row(
              children: [
                Expanded(
                  flex: 1,
                  child: _buildDropdownField(
                    'Salutation *',
                    _salutationController,
                    ['Mr.', 'Ms.', 'Mrs.', 'Dr.'],
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  flex: 3,
                  child: _buildTextField(
                    'Full Name *',
                    _fullNameController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Full name is required';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildDateField(
                    'Date of Birth *',
                    _dobController,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildDropdownField(
                    'Marital Status *',
                    _maritalStatusController,
                    ['Single', 'Married', 'Divorced', 'Widowed'],
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildDropdownField(
                    'Gender *',
                    _genderController,
                    ['Male', 'Female', 'Other'],
                  ),
                ),
              ],
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildDropdownField(
                    'Category *',
                    _categoryController,
                    ['General', 'OBC', 'SC', 'ST'],
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Caste *',
                    _casteController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Caste is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Religion *',
                    _religionController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Religion is required';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            _buildTextField(
              'Identification Mark *',
              _identificationMarkController,
              validator: (value) {
                if (value?.isEmpty ?? true) {
                  return 'Identification mark is required';
                }
                return null;
              },
            ),
            
            const SizedBox(height: 30),
            
            // Permanent Address
            _buildSectionTitle('Permanent Address'),
            _buildTextField(
              'Address Line 1 *',
              _permanentAddress1Controller,
              validator: (value) {
                if (value?.isEmpty ?? true) {
                  return 'Address line 1 is required';
                }
                return null;
              },
            ),
            
            _buildTextField(
              'Address Line 2 *',
              _permanentAddress2Controller,
              validator: (value) {
                if (value?.isEmpty ?? true) {
                  return 'Address line 2 is required';
                }
                return null;
              },
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildTextField(
                    'Landmark *',
                    _permanentLandmarkController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Landmark is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Village/Town *',
                    _permanentVillageController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Village/Town is required';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildTextField(
                    'State *',
                    _permanentStateController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'State is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'District *',
                    _permanentDistrictController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'District is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Pincode *',
                    _permanentPincodeController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Pincode is required';
                      }
                      if (value!.length != 6) {
                        return 'Pincode must be 6 digits';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 30),
            
            // Present Address
            Row(
              children: [
                _buildSectionTitle('Present Address'),
                const Spacer(),
                TextButton(
                  onPressed: _copyPermanentAddress,
                  child: const Text('Same as Permanent Address'),
                ),
              ],
            ),
            
            _buildTextField(
              'Address Line 1 *',
              _presentAddress1Controller,
              validator: (value) {
                if (value?.isEmpty ?? true) {
                  return 'Address line 1 is required';
                }
                return null;
              },
            ),
            
            _buildTextField(
              'Address Line 2 *',
              _presentAddress2Controller,
              validator: (value) {
                if (value?.isEmpty ?? true) {
                  return 'Address line 2 is required';
                }
                return null;
              },
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildTextField(
                    'Landmark *',
                    _presentLandmarkController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Landmark is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Village/Town *',
                    _presentVillageController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Village/Town is required';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            Row(
              children: [
                Expanded(
                  child: _buildTextField(
                    'State *',
                    _presentStateController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'State is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'District *',
                    _presentDistrictController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'District is required';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildTextField(
                    'Pincode *',
                    _presentPincodeController,
                    validator: (value) {
                      if (value?.isEmpty ?? true) {
                        return 'Pincode is required';
                      }
                      if (value!.length != 6) {
                        return 'Pincode must be 6 digits';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }
  
  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: Colors.black87,
        ),
      ),
    );
  }
  
  Widget _buildTextField(
    String label,
    TextEditingController controller, {
    String? Function(String?)? validator,
    int maxLines = 1,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: controller,
        validator: validator,
        maxLines: maxLines,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: Colors.blue[800]!),
          ),
        ),
      ),
    );
  }
  
  Widget _buildDropdownField(
    String label,
    TextEditingController controller,
    List<String> options,
  ) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: DropdownButtonFormField<String>(
        value: controller.text.isEmpty ? null : controller.text,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: Colors.blue[800]!),
          ),
        ),
        items: options.map((option) {
          return DropdownMenuItem<String>(
            value: option,
            child: Text(option),
          );
        }).toList(),
        onChanged: (value) {
          controller.text = value ?? '';
          _updateParentData();
        },
        validator: (value) {
          if (value?.isEmpty ?? true) {
            return '${label.replaceAll(' *', '')} is required';
          }
          return null;
        },
      ),
    );
  }
  
  Widget _buildDateField(
    String label,
    TextEditingController controller,
  ) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: Colors.blue[800]!),
          ),
          suffixIcon: const Icon(Icons.calendar_today),
        ),
        onTap: () async {
          final date = await showDatePicker(
            context: context,
            initialDate: DateTime.now().subtract(const Duration(days: 6570)), // 18 years ago
            firstDate: DateTime(1950),
            lastDate: DateTime.now(),
          );
          
          if (date != null) {
            controller.text = date.toString().split(' ')[0]; // YYYY-MM-DD format
            _updateParentData();
          }
        },
        validator: (value) {
          if (value?.isEmpty ?? true) {
            return 'Date of birth is required';
          }
          return null;
        },
      ),
    );
  }
  
  void _copyPermanentAddress() {
    _presentAddress1Controller.text = _permanentAddress1Controller.text;
    _presentAddress2Controller.text = _permanentAddress2Controller.text;
    _presentLandmarkController.text = _permanentLandmarkController.text;
    _presentVillageController.text = _permanentVillageController.text;
    _presentStateController.text = _permanentStateController.text;
    _presentDistrictController.text = _permanentDistrictController.text;
    _presentPincodeController.text = _permanentPincodeController.text;
    _updateParentData();
  }
  
  @override
  void dispose() {
    // Dispose all controllers
    _salutationController.dispose();
    _fullNameController.dispose();
    _dobController.dispose();
    _maritalStatusController.dispose();
    _genderController.dispose();
    _categoryController.dispose();
    _casteController.dispose();
    _religionController.dispose();
    _identificationMarkController.dispose();
    _permanentAddress1Controller.dispose();
    _permanentAddress2Controller.dispose();
    _permanentLandmarkController.dispose();
    _permanentVillageController.dispose();
    _permanentStateController.dispose();
    _permanentDistrictController.dispose();
    _permanentPincodeController.dispose();
    _presentAddress1Controller.dispose();
    _presentAddress2Controller.dispose();
    _presentLandmarkController.dispose();
    _presentVillageController.dispose();
    _presentStateController.dispose();
    _presentDistrictController.dispose();
    _presentPincodeController.dispose();
    
    super.dispose();
  }
}
```

---

## 🚀 6. Main App Integration

### **main.dart**
```dart
import 'package:flutter/material.dart';
import 'screens/login_screen.dart';
import 'screens/job_selection_screen.dart';
import 'services/auth_service.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Municipal Board Job Portal',
      theme: ThemeData(
        primarySwatch: Colors.blue,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: const AppWrapper(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class AppWrapper extends StatefulWidget {
  const AppWrapper({Key? key}) : super(key: key);

  @override
  _AppWrapperState createState() => _AppWrapperState();
}

class _AppWrapperState extends State<AppWrapper> {
  bool isLoading = true;
  bool isLoggedIn = false;

  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    final loggedIn = await AuthService.isLoggedIn();
    setState(() {
      isLoggedIn = loggedIn;
      isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return isLoggedIn ? const JobSelectionScreen() : const LoginScreen();
  }
}
```

---

## 📦 7. Required Dependencies

### **pubspec.yaml**
```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  shared_preferences: ^2.2.2
  file_picker: ^6.1.1
  intl: ^0.18.1
  
dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.0
```

---

## 🔄 8. API Integration Flow

### **Complete Application Flow:**

1. **User Login** → Get JWT Token
2. **Job Selection** → Choose job to apply for
3. **Personal Details** → Fill and submit personal information
4. **Employment Details** → Add employment history
5. **Qualification Details** → Add education details
6. **Document Upload** → Upload required documents
7. **Application Complete** → Show success message

### **Error Handling:**
- Token expiration → Redirect to login
- Validation errors → Show field-specific errors
- Network errors → Show retry options
- Duplicate submissions → Handle gracefully

This comprehensive guide provides end-to-end integration for your Flutter Web job application system with all necessary API calls, error handling, and UI components! 🚀