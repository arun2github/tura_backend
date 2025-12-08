# Flutter Web Integration Guide for Admit Card APIs

## API Endpoints for Flutter Web

### 1. Verify Admit Card
```dart
// Flutter Web HTTP request example
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> verifyAdmitCard(String applicationId, String email) async {
  final url = Uri.parse('http://127.0.0.1:8000/api/admit-card/verify');
  
  final response = await http.post(
    url,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'application_id': applicationId,
      'email': email,
    }),
  );

  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('Failed to verify admit card: ${response.body}');
  }
}
```

### 2. Download Admit Card PDF
```dart
// Flutter Web PDF download
import 'dart:html' as html;

Future<void> downloadAdmitCard(String admitNo) async {
  final url = 'http://127.0.0.1:8000/api/admit-card/download/$admitNo';
  
  // For Flutter Web - open in new tab
  html.window.open(url, '_blank');
}
```

### 3. Complete Flutter Integration Example
```dart
class AdmitCardService {
  static const String baseUrl = 'http://127.0.0.1:8000/api';
  
  static Future<AdmitCardResponse> verifyAdmitCard({
    required String applicationId,
    required String email,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/admit-card/verify'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'application_id': applicationId,
          'email': email,
        }),
      );

      final data = jsonDecode(response.body);
      
      if (response.statusCode == 200 && data['status'] == true) {
        return AdmitCardResponse.fromJson(data);
      } else {
        throw Exception(data['message'] ?? 'Verification failed');
      }
    } catch (e) {
      throw Exception('Network error: $e');
    }
  }
  
  static void downloadPDF(String admitNo) {
    final url = '$baseUrl/admit-card/download/$admitNo';
    html.window.open(url, '_blank');
  }
}

class AdmitCardResponse {
  final bool status;
  final String message;
  final String applicationId;
  final String rollNumber;
  final String fullName;
  final String downloadUrl;

  AdmitCardResponse({
    required this.status,
    required this.message,
    required this.applicationId,
    required this.rollNumber,
    required this.fullName,
    required this.downloadUrl,
  });

  factory AdmitCardResponse.fromJson(Map<String, dynamic> json) {
    return AdmitCardResponse(
      status: json['status'] ?? false,
      message: json['message'] ?? '',
      applicationId: json['application_id'] ?? '',
      rollNumber: json['roll_number'] ?? '',
      fullName: json['full_name'] ?? '',
      downloadUrl: json['download_url'] ?? '',
    );
  }
}
```

## Important Notes for Flutter Web:

1. **CORS**: Already configured to allow localhost and various ports
2. **PDF Download**: Uses `html.window.open()` for web compatibility
3. **Error Handling**: Proper JSON error responses
4. **Headers**: Always include `Accept: application/json`

## Production URLs:
When deploying, replace `http://127.0.0.1:8000` with your production URL.