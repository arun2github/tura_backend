# 🐕 Pet Dog Registration - Flutter Web Integration Guide

## 🚀 **API Endpoints Overview**

### **Consumer APIs**
- `POST /api/petDogRegistration` - Submit Pet Dog Registration
- `POST /api/payment/{application_id}` - Initiate Payment

### **Admin APIs (CEO/Employee)**
- `POST /api/pet-dog/applications` - Get All Pet Applications
- `POST /api/pet-dog/application-details` - Get Application Details

---

## 📱 **Flutter Web Implementation**

### **1. API Service Class**

```dart
// lib/services/pet_dog_api_service.dart
import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;

class PetDogApiService {
  static const String baseUrl = 'https://laravelv2.turamunicipalboard.com/api';
  
  static Map<String, String> _getHeaders(String? token) {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // Submit Pet Dog Registration
  static Future<Map<String, dynamic>> submitRegistration({
    required String token,
    required Map<String, dynamic> formData,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/petDogRegistration'),
        headers: _getHeaders(token),
        body: jsonEncode(formData),
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to submit registration: ${response.body}');
      }
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }

  // Get All Pet Applications (Admin)
  static Future<Map<String, dynamic>> getAllApplications({
    required String token,
    int perPage = 20,
    int page = 1,
    String? status,
    String? paymentStatus,
    String? search,
  }) async {
    try {
      final requestBody = {
        'per_page': perPage,
        'page': page,
        if (status != null) 'status': status,
        if (paymentStatus != null) 'payment_status': paymentStatus,
        if (search != null && search.isNotEmpty) 'search': search,
      };

      final response = await http.post(
        Uri.parse('$baseUrl/pet-dog/applications'),
        headers: _getHeaders(token),
        body: jsonEncode(requestBody),
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to fetch applications: ${response.body}');
      }
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }

  // Get Application Details (Admin)
  static Future<Map<String, dynamic>> getApplicationDetails({
    required String token,
    required int applicationId,
  }) async {
    try {
      final requestBody = {
        'application_id': applicationId,
      };

      final response = await http.post(
        Uri.parse('$baseUrl/pet-dog/application-details'),
        headers: _getHeaders(token),
        body: jsonEncode(requestBody),
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception('Failed to fetch application details: ${response.body}');
      }
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }

  // Convert image to base64
  static String imageToBase64(Uint8List imageBytes, String mimeType) {
    return 'data:$mimeType;base64,${base64Encode(imageBytes)}';
  }
}
```

---

### **2. Data Models**

```dart
// lib/models/pet_dog_models.dart

class PetDogRegistrationRequest {
  final int formId;
  final String ownerName;
  final String ownerPhone;
  final String ownerEmail;
  final String ownerAddress;
  final String ownerAadharNumber;
  final String? dogName;
  final String? dogBreed;
  final int dogAge;
  final String dogAgeUnit;
  final String dogColor;
  final String dogGender;
  final double dogWeight;
  final String vaccinationStatus;
  final String? vaccinationDate;
  final String? veterinarianName;
  final String? veterinarianLicense;
  final String petPhoto; // base64
  final String ownerPhotoWithPet; // base64
  final String declaration;
  final List<DocumentUpload>? documentList;

  PetDogRegistrationRequest({
    this.formId = 0,
    required this.ownerName,
    required this.ownerPhone,
    required this.ownerEmail,
    required this.ownerAddress,
    required this.ownerAadharNumber,
    this.dogName,
    this.dogBreed,
    required this.dogAge,
    required this.dogAgeUnit,
    required this.dogColor,
    required this.dogGender,
    required this.dogWeight,
    required this.vaccinationStatus,
    this.vaccinationDate,
    this.veterinarianName,
    this.veterinarianLicense,
    required this.petPhoto,
    required this.ownerPhotoWithPet,
    required this.declaration,
    this.documentList,
  });

  Map<String, dynamic> toJson() {
    return {
      'form_id': formId,
      'owner_name': ownerName,
      'owner_phone': ownerPhone,
      'owner_email': ownerEmail,
      'owner_address': ownerAddress,
      'owner_aadhar_number': ownerAadharNumber,
      if (dogName != null) 'dog_name': dogName,
      if (dogBreed != null) 'dog_breed': dogBreed,
      'dog_age': dogAge,
      'dog_age_unit': dogAgeUnit,
      'dog_color': dogColor,
      'dog_gender': dogGender,
      'dog_weight': dogWeight,
      'vaccination_status': vaccinationStatus,
      if (vaccinationDate != null) 'vaccination_date': vaccinationDate,
      if (veterinarianName != null) 'veterinarian_name': veterinarianName,
      if (veterinarianLicense != null) 'veterinarian_license': veterinarianLicense,
      'pet_photo': petPhoto,
      'owner_photo_with_pet': ownerPhotoWithPet,
      'declaration': declaration,
      if (documentList != null) 'document_list': documentList!.map((d) => d.toJson()).toList(),
    };
  }
}

class DocumentUpload {
  final String type;
  final String name;
  final String data; // base64

  DocumentUpload({
    required this.type,
    required this.name,
    required this.data,
  });

  Map<String, dynamic> toJson() {
    return {
      'type': type,
      'name': name,
      'data': data,
    };
  }
}

class PetApplication {
  final int id;
  final String applicationId;
  final String status;
  final String employeeStatus;
  final String ceoStatus;
  final DateTime submittedDate;
  final UserDetails userDetails;
  final PetDetails petDetails;
  final String paymentStatus;
  final double paymentAmount;

  PetApplication({
    required this.id,
    required this.applicationId,
    required this.status,
    required this.employeeStatus,
    required this.ceoStatus,
    required this.submittedDate,
    required this.userDetails,
    required this.petDetails,
    required this.paymentStatus,
    required this.paymentAmount,
  });

  factory PetApplication.fromJson(Map<String, dynamic> json) {
    return PetApplication(
      id: json['id'],
      applicationId: json['application_id'],
      status: json['status'],
      employeeStatus: json['employee_status'],
      ceoStatus: json['ceo_status'],
      submittedDate: DateTime.parse(json['submitted_date']),
      userDetails: UserDetails.fromJson(json['user_details']),
      petDetails: PetDetails.fromJson(json['pet_details']),
      paymentStatus: json['payment_status'],
      paymentAmount: json['payment_amount'].toDouble(),
    );
  }
}

class UserDetails {
  final String name;
  final String email;
  final String phone;
  final String? wardId;
  final String? locality;

  UserDetails({
    required this.name,
    required this.email,
    required this.phone,
    this.wardId,
    this.locality,
  });

  factory UserDetails.fromJson(Map<String, dynamic> json) {
    return UserDetails(
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      wardId: json['ward_id'],
      locality: json['locality'],
    );
  }
}

class PetDetails {
  final String ownerName;
  final String ownerEmail;
  final String ownerPhone;
  final String dogName;
  final String dogBreed;
  final String petTagNumber;
  final String vaccinationStatus;

  PetDetails({
    required this.ownerName,
    required this.ownerEmail,
    required this.ownerPhone,
    required this.dogName,
    required this.dogBreed,
    required this.petTagNumber,
    required this.vaccinationStatus,
  });

  factory PetDetails.fromJson(Map<String, dynamic> json) {
    return PetDetails(
      ownerName: json['owner_name'],
      ownerEmail: json['owner_email'],
      ownerPhone: json['owner_phone'],
      dogName: json['dog_name'],
      dogBreed: json['dog_breed'],
      petTagNumber: json['pet_tag_number'],
      vaccinationStatus: json['vaccination_status'],
    );
  }
}
```

---

### **3. Consumer Registration Form Widget**

```dart
// lib/screens/pet_registration_form.dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:file_picker/file_picker.dart';
import '../services/pet_dog_api_service.dart';
import '../models/pet_dog_models.dart';

class PetRegistrationForm extends StatefulWidget {
  final String userToken;

  const PetRegistrationForm({Key? key, required this.userToken}) : super(key: key);

  @override
  _PetRegistrationFormState createState() => _PetRegistrationFormState();
}

class _PetRegistrationFormState extends State<PetRegistrationForm> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  
  // Form Controllers
  final _ownerNameController = TextEditingController();
  final _ownerPhoneController = TextEditingController();
  final _ownerEmailController = TextEditingController();
  final _ownerAddressController = TextEditingController();
  final _ownerAadharController = TextEditingController();
  final _dogNameController = TextEditingController();
  final _dogBreedController = TextEditingController();
  final _dogAgeController = TextEditingController();
  final _dogColorController = TextEditingController();
  final _dogWeightController = TextEditingController();
  final _vaccinationDateController = TextEditingController();
  final _veterinarianNameController = TextEditingController();
  final _veterinarianLicenseController = TextEditingController();
  
  // Form Values
  String _dogAgeUnit = 'months';
  String _dogGender = 'male';
  String _vaccinationStatus = 'completed';
  
  // Images
  Uint8List? _petPhotoBytes;
  Uint8List? _ownerPhotoBytes;
  List<DocumentUpload> _documents = [];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pet Dog Registration'),
        backgroundColor: Colors.blue.shade600,
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildOwnerDetailsSection(),
                    const SizedBox(height: 20),
                    _buildDogDetailsSection(),
                    const SizedBox(height: 20),
                    _buildVaccinationSection(),
                    const SizedBox(height: 20),
                    _buildPhotoSection(),
                    const SizedBox(height: 20),
                    _buildDocumentSection(),
                    const SizedBox(height: 20),
                    _buildDeclarationSection(),
                    const SizedBox(height: 30),
                    _buildSubmitButton(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildOwnerDetailsSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Owner Details', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            TextFormField(
              controller: _ownerNameController,
              decoration: const InputDecoration(
                labelText: 'Owner Name *',
                border: OutlineInputBorder(),
              ),
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _ownerPhoneController,
              decoration: const InputDecoration(
                labelText: 'Phone Number *',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _ownerEmailController,
              decoration: const InputDecoration(
                labelText: 'Email *',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _ownerAddressController,
              decoration: const InputDecoration(
                labelText: 'Address *',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _ownerAadharController,
              decoration: const InputDecoration(
                labelText: 'Aadhar Number *',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
              maxLength: 12,
              validator: (value) => value?.length != 12 ? 'Must be 12 digits' : null,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDogDetailsSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Dog Details', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            TextFormField(
              controller: _dogNameController,
              decoration: const InputDecoration(
                labelText: 'Dog Name',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _dogBreedController,
              decoration: const InputDecoration(
                labelText: 'Dog Breed',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  flex: 2,
                  child: TextFormField(
                    controller: _dogAgeController,
                    decoration: const InputDecoration(
                      labelText: 'Age *',
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: TextInputType.number,
                    validator: (value) => value?.isEmpty == true ? 'Required' : null,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _dogAgeUnit,
                    decoration: const InputDecoration(
                      labelText: 'Unit *',
                      border: OutlineInputBorder(),
                    ),
                    items: ['months', 'years'].map((unit) {
                      return DropdownMenuItem(value: unit, child: Text(unit));
                    }).toList(),
                    onChanged: (value) => setState(() => _dogAgeUnit = value!),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _dogColorController,
              decoration: const InputDecoration(
                labelText: 'Color *',
                border: OutlineInputBorder(),
              ),
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _dogGender,
              decoration: const InputDecoration(
                labelText: 'Gender *',
                border: OutlineInputBorder(),
              ),
              items: ['male', 'female'].map((gender) {
                return DropdownMenuItem(value: gender, child: Text(gender.toUpperCase()));
              }).toList(),
              onChanged: (value) => setState(() => _dogGender = value!),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _dogWeightController,
              decoration: const InputDecoration(
                labelText: 'Weight (kg) *',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVaccinationSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Vaccination Details', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              value: _vaccinationStatus,
              decoration: const InputDecoration(
                labelText: 'Vaccination Status *',
                border: OutlineInputBorder(),
              ),
              items: ['completed', 'pending'].map((status) {
                return DropdownMenuItem(value: status, child: Text(status.toUpperCase()));
              }).toList(),
              onChanged: (value) => setState(() => _vaccinationStatus = value!),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _vaccinationDateController,
              decoration: const InputDecoration(
                labelText: 'Vaccination Date',
                border: OutlineInputBorder(),
                suffixIcon: Icon(Icons.calendar_today),
              ),
              readOnly: true,
              onTap: () async {
                final date = await showDatePicker(
                  context: context,
                  initialDate: DateTime.now(),
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2030),
                );
                if (date != null) {
                  _vaccinationDateController.text = '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
                }
              },
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _veterinarianNameController,
              decoration: const InputDecoration(
                labelText: 'Veterinarian Name',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _veterinarianLicenseController,
              decoration: const InputDecoration(
                labelText: 'Veterinarian License',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Photos', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => _pickImage(true),
              icon: const Icon(Icons.pets),
              label: Text(_petPhotoBytes != null ? 'Pet Photo Selected' : 'Select Pet Photo *'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _petPhotoBytes != null ? Colors.green : null,
              ),
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: () => _pickImage(false),
              icon: const Icon(Icons.people),
              label: Text(_ownerPhotoBytes != null ? 'Owner Photo Selected' : 'Select Owner Photo with Pet *'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _ownerPhotoBytes != null ? Colors.green : null,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDocumentSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Additional Documents', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _pickDocument,
              icon: const Icon(Icons.attach_file),
              label: const Text('Add Document'),
            ),
            const SizedBox(height: 12),
            if (_documents.isNotEmpty)
              ...(_documents.asMap().entries.map((entry) {
                final index = entry.key;
                final doc = entry.value;
                return ListTile(
                  leading: const Icon(Icons.description),
                  title: Text(doc.name),
                  subtitle: Text(doc.type),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete),
                    onPressed: () => setState(() => _documents.removeAt(index)),
                  ),
                );
              })),
          ],
        ),
      ),
    );
  }

  Widget _buildDeclarationSection() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Declaration', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            const Text(
              'I hereby declare that all information provided is true and correct to the best of my knowledge.',
              style: TextStyle(fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSubmitButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: _petPhotoBytes != null && _ownerPhotoBytes != null ? _submitForm : null,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.green,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
        ),
        child: const Text(
          'Submit Registration',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Future<void> _pickImage(bool isPetPhoto) async {
    try {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.image,
        allowMultiple: false,
        withData: true,
      );

      if (result != null && result.files.first.bytes != null) {
        setState(() {
          if (isPetPhoto) {
            _petPhotoBytes = result.files.first.bytes!;
          } else {
            _ownerPhotoBytes = result.files.first.bytes!;
          }
        });
      }
    } catch (e) {
      _showErrorDialog('Error picking image: $e');
    }
  }

  Future<void> _pickDocument() async {
    try {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.any,
        allowMultiple: false,
        withData: true,
      );

      if (result != null && result.files.first.bytes != null) {
        final file = result.files.first;
        final docType = await _showDocumentTypeDialog();
        
        if (docType != null) {
          final base64Data = PetDogApiService.imageToBase64(
            file.bytes!,
            'application/${file.extension ?? 'pdf'}',
          );

          setState(() {
            _documents.add(DocumentUpload(
              type: docType,
              name: file.name,
              data: base64Data,
            ));
          });
        }
      }
    } catch (e) {
      _showErrorDialog('Error picking document: $e');
    }
  }

  Future<String?> _showDocumentTypeDialog() async {
    return showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Select Document Type'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('Vaccination Certificate'),
              onTap: () => Navigator.pop(context, 'vaccination_certificate'),
            ),
            ListTile(
              title: const Text('Identity Proof'),
              onTap: () => Navigator.pop(context, 'identity_proof'),
            ),
            ListTile(
              title: const Text('Other'),
              onTap: () => Navigator.pop(context, 'other'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final request = PetDogRegistrationRequest(
        ownerName: _ownerNameController.text,
        ownerPhone: _ownerPhoneController.text,
        ownerEmail: _ownerEmailController.text,
        ownerAddress: _ownerAddressController.text,
        ownerAadharNumber: _ownerAadharController.text,
        dogName: _dogNameController.text.isEmpty ? null : _dogNameController.text,
        dogBreed: _dogBreedController.text.isEmpty ? null : _dogBreedController.text,
        dogAge: int.parse(_dogAgeController.text),
        dogAgeUnit: _dogAgeUnit,
        dogColor: _dogColorController.text,
        dogGender: _dogGender,
        dogWeight: double.parse(_dogWeightController.text),
        vaccinationStatus: _vaccinationStatus,
        vaccinationDate: _vaccinationDateController.text.isEmpty ? null : _vaccinationDateController.text,
        veterinarianName: _veterinarianNameController.text.isEmpty ? null : _veterinarianNameController.text,
        veterinarianLicense: _veterinarianLicenseController.text.isEmpty ? null : _veterinarianLicenseController.text,
        petPhoto: PetDogApiService.imageToBase64(_petPhotoBytes!, 'image/jpeg'),
        ownerPhotoWithPet: PetDogApiService.imageToBase64(_ownerPhotoBytes!, 'image/jpeg'),
        declaration: 'I hereby declare that all information provided is true and correct.',
        documentList: _documents.isEmpty ? null : _documents,
      );

      final response = await PetDogApiService.submitRegistration(
        token: widget.userToken,
        formData: request.toJson(),
      );

      if (response['status'] == 'success') {
        _showSuccessDialog(response);
      } else {
        _showErrorDialog(response['message'] ?? 'Registration failed');
      }
    } catch (e) {
      _showErrorDialog('Error: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showSuccessDialog(Map<String, dynamic> response) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Registration Successful!'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Application ID: ${response['application_id']}'),
            Text('Form ID: ${response['form_id']}'),
            Text('Pet Tag Number: ${response['pet_tag_number']}'),
            const SizedBox(height: 16),
            const Text('Your pet registration has been submitted successfully. You can proceed to payment.'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              Navigator.pop(context); // Go back to previous screen
            },
            child: const Text('Continue to Payment'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}
```

---

### **4. Admin Applications List Widget**

```dart
// lib/screens/admin/pet_applications_list.dart
import 'package:flutter/material.dart';
import '../services/pet_dog_api_service.dart';
import '../models/pet_dog_models.dart';

class PetApplicationsList extends StatefulWidget {
  final String adminToken;

  const PetApplicationsList({Key? key, required this.adminToken}) : super(key: key);

  @override
  _PetApplicationsListState createState() => _PetApplicationsListState();
}

class _PetApplicationsListState extends State<PetApplicationsList> {
  bool _isLoading = false;
  List<PetApplication> _applications = [];
  int _currentPage = 1;
  int _totalPages = 1;
  String? _selectedStatus;
  String? _selectedPaymentStatus;
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadApplications();
  }

  Future<void> _loadApplications({bool reset = false}) async {
    if (reset) {
      setState(() {
        _currentPage = 1;
        _applications.clear();
      });
    }

    setState(() => _isLoading = true);

    try {
      final response = await PetDogApiService.getAllApplications(
        token: widget.adminToken,
        page: _currentPage,
        perPage: 20,
        status: _selectedStatus,
        paymentStatus: _selectedPaymentStatus,
        search: _searchController.text,
      );

      if (response['status'] == 'success') {
        final data = response['data'];
        final pagination = data['pagination'];
        
        setState(() {
          if (reset) {
            _applications = (data['applications'] as List)
                .map((app) => PetApplication.fromJson(app))
                .toList();
          } else {
            _applications.addAll((data['applications'] as List)
                .map((app) => PetApplication.fromJson(app))
                .toList());
          }
          _totalPages = pagination['total_pages'];
        });
      }
    } catch (e) {
      _showErrorDialog('Error loading applications: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pet Dog Applications'),
        backgroundColor: Colors.blue.shade600,
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          _buildFilters(),
          Expanded(
            child: _isLoading && _applications.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : _buildApplicationsList(),
          ),
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return Card(
      margin: const EdgeInsets.all(8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                labelText: 'Search',
                hintText: 'Application ID, Owner Name, Dog Name',
                prefixIcon: const Icon(Icons.search),
                border: const OutlineInputBorder(),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    _loadApplications(reset: true);
                  },
                ),
              ),
              onSubmitted: (_) => _loadApplications(reset: true),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String?>(
                    value: _selectedStatus,
                    decoration: const InputDecoration(
                      labelText: 'Application Status',
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('All Status')),
                      const DropdownMenuItem(value: 'CEO Approved', child: Text('CEO Approved')),
                      const DropdownMenuItem(value: 'Pending', child: Text('Pending')),
                    ],
                    onChanged: (value) {
                      setState(() => _selectedStatus = value);
                      _loadApplications(reset: true);
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: DropdownButtonFormField<String?>(
                    value: _selectedPaymentStatus,
                    decoration: const InputDecoration(
                      labelText: 'Payment Status',
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('All Payments')),
                      const DropdownMenuItem(value: 'paid', child: Text('Paid')),
                      const DropdownMenuItem(value: 'pending', child: Text('Pending')),
                      const DropdownMenuItem(value: 'failed', child: Text('Failed')),
                    ],
                    onChanged: (value) {
                      setState(() => _selectedPaymentStatus = value);
                      _loadApplications(reset: true);
                    },
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildApplicationsList() {
    if (_applications.isEmpty) {
      return const Center(child: Text('No applications found'));
    }

    return ListView.builder(
      itemCount: _applications.length + (_currentPage < _totalPages ? 1 : 0),
      itemBuilder: (context, index) {
        if (index == _applications.length) {
          // Load more button
          return Padding(
            padding: const EdgeInsets.all(16),
            child: ElevatedButton(
              onPressed: _isLoading ? null : () {
                setState(() => _currentPage++);
                _loadApplications();
              },
              child: _isLoading 
                  ? const CircularProgressIndicator()
                  : const Text('Load More'),
            ),
          );
        }

        final app = _applications[index];
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          child: ListTile(
            title: Text('${app.petDetails.dogName} - ${app.applicationId}'),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Owner: ${app.petDetails.ownerName}'),
                Text('Status: ${app.status}'),
                Text('Payment: ${app.paymentStatus.toUpperCase()} - ₹${app.paymentAmount}'),
              ],
            ),
            trailing: const Icon(Icons.arrow_forward_ios),
            onTap: () => _viewApplicationDetails(app.id),
          ),
        );
      },
    );
  }

  Future<void> _viewApplicationDetails(int applicationId) async {
    // Navigate to details screen or show details dialog
    try {
      final response = await PetDogApiService.getApplicationDetails(
        token: widget.adminToken,
        applicationId: applicationId,
      );

      if (response['status'] == 'success') {
        _showApplicationDetailsDialog(response['data']);
      }
    } catch (e) {
      _showErrorDialog('Error loading details: $e');
    }
  }

  void _showApplicationDetailsDialog(Map<String, dynamic> data) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Application: ${data['application_info']['application_id']}'),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Status: ${data['application_info']['status']}'),
              Text('Pet Tag: ${data['dog_details']['pet_tag_number']}'),
              const SizedBox(height: 8),
              Text('Owner: ${data['owner_details']['owner_name']}'),
              Text('Dog: ${data['dog_details']['dog_name']} (${data['dog_details']['dog_breed']})'),
              Text('Payment: ${data['payment_details']['status']} - ₹${data['payment_details']['amount']}'),
              // Add more details as needed
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Close'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}
```

---

### **5. pubspec.yaml Dependencies**

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  file_picker: ^6.1.1
```

---

## 🎯 **Usage Examples**

### **Consumer Registration:**
```dart
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => PetRegistrationForm(userToken: 'your_jwt_token'),
  ),
);
```

### **Admin Dashboard:**
```dart
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => PetApplicationsList(adminToken: 'admin_jwt_token'),
  ),
);
```

---

## 🔒 **Security Notes**

1. **JWT Tokens**: Store securely (secure_storage package)
2. **Image Compression**: Compress images before base64 encoding
3. **File Size Limits**: Implement file size validation
4. **Network Timeouts**: Add proper timeout handling
5. **Error Handling**: Comprehensive error scenarios

This integration guide provides complete Flutter Web implementation for Pet Dog Registration system! 🐕📱