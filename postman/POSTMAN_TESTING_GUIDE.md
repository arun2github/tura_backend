# Tura Municipal Admit Card API - Postman Testing Guide

## Quick Start

### 1. Import Collection
1. Open Postman
2. Click "Import" button
3. Select `Admit_Card_API_Collection.json` file
4. Collection will be imported with all endpoints and test data

### 2. Environment Variables
The collection uses these variables:
- `base_url`: http://127.0.0.1:8000/api (default Laravel server)
- `admit_no`: ADMIT001 (sample admit number for testing)

### 3. Prerequisites
```bash
# Start Laravel server
php artisan serve

# Start XAMPP MySQL server
# Ensure database connection is working
```

## API Endpoints

### 1. Test API Connection
- **Method**: GET
- **URL**: `{{base_url}}/test-admit-card`
- **Purpose**: Verify API server is running
- **Response**: Simple connectivity test

### 2. Verify Admit Card
- **Method**: POST
- **URL**: `{{base_url}}/admit-card/verify`
- **Headers**:
  - Accept: application/json
  - Content-Type: application/json
- **Body**:
```json
{
  "application_id": "APP123456",
  "email": "test@example.com"
}
```
- **Success Response** (200):
```json
{
  "status": true,
  "message": "Record Found",
  "application_id": "APP123456",
  "roll_number": "ROLL001",
  "full_name": "John Doe Test",
  "download_url": "http://127.0.0.1:8000/api/admit-card/download/ADMIT001"
}
```
- **Error Response** (404):
```json
{
  "status": false,
  "message": "Invalid Application ID or Email"
}
```

### 3. Download Admit Card PDF
- **Method**: GET
- **URL**: `{{base_url}}/admit-card/download/{{admit_no}}`
- **Headers**:
  - Accept: application/pdf
- **Response**: PDF file download (2-page document)
  - **Page 1**: Admit card with candidate details, photo, exam information
  - **Page 2**: Complete instructions to candidates (14 detailed points)
- **Example**: `http://127.0.0.1:8000/api/admit-card/download/ADMIT001`

### 4. Test Database Connection
- **Method**: GET
- **URL**: `{{base_url}}/test-db`
- **Purpose**: Verify database connectivity

## Test Data

### Sample Test Record
```json
{
  "application_id": "APP123456",
  "email": "test@example.com",
  "admit_no": "ADMIT001",
  "roll_number": "ROLL001",
  "full_name": "John Doe Test",
  "gender": "Male",
  "category": "General",
  "exam_center": "Tura Municipal Corporation",
  "exam_date": "2024-12-15",
  "exam_time": "10:00 AM",
  "reporting_time": "09:30 AM"
}
```

## Testing Scenarios

### 1. Valid Data Test
✅ Use the sample data above to test successful verification

### 2. Invalid Data Test
❌ Test with non-existent application_id:
```json
{
  "application_id": "INVALID123",
  "email": "invalid@example.com"
}
```

### 3. Missing Fields Test
❌ Test validation by omitting required fields:
```json
{
  "application_id": "APP123456"
}
```

### 4. Wrong Email Test
❌ Test with correct application_id but wrong email:
```json
{
  "application_id": "APP123456",
  "email": "wrong@example.com"
}
```

## Expected Responses

### Successful Verification
- Status: 200 OK
- JSON with candidate details and download URL
- All required fields populated

### Failed Verification
- Status: 404 Not Found
- JSON with error message
- No sensitive data exposed

### PDF Download
- Content-Type: application/pdf
- **2-Page Government-style admit card**:
  - **Page 1**: Complete admit card with candidate photo, details, exam information, signature sections
  - **Page 2**: Comprehensive instructions (14 detailed points) including:
    - Reporting time requirements (1 hour before exam)
    - ID proof requirements and acceptable documents
    - Prohibited items (mobile phones, calculators, electronic devices)
    - Examination hall conduct rules
    - Vehicle parking restrictions
    - Frisking and security procedures
- Proper formatting with official letterhead and watermark

## Troubleshooting

### Server Connection Issues
```bash
# Check if Laravel server is running
curl http://127.0.0.1:8000

# Restart Laravel server
php artisan serve
```

### Database Issues
```bash
# Test database connection
php artisan migrate:status

# Check if test data exists
php test_admit_card_api.php
```

### CORS Issues (For Flutter Web)
- API includes CORS headers
- Supports localhost origins
- JSON responses properly formatted

## Flutter Web Integration

### Example Flutter HTTP Request
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<Map<String, dynamic>> verifyAdmitCard(
  String applicationId, 
  String email
) async {
  final response = await http.post(
    Uri.parse('http://127.0.0.1:8000/api/admit-card/verify'),
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'application_id': applicationId,
      'email': email,
    }),
  );

  return jsonDecode(response.body);
}
```

## Automation Testing

### Postman Tests
Add these to the "Tests" tab in Postman:

```javascript
// For verify endpoint
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has required fields", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('status');
    pm.expect(jsonData).to.have.property('message');
    pm.expect(jsonData).to.have.property('application_id');
    pm.expect(jsonData).to.have.property('download_url');
});

pm.test("Status is true for valid data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.status).to.be.true;
});
```

## Environment Setup

### Local Development
- Base URL: `http://127.0.0.1:8000/api`
- Database: Local MySQL via XAMPP
- PHP Version: 8.1+
- Laravel Version: 10+

### Production (when deployed)
- Update base_url variable in Postman
- Ensure HTTPS is used
- Update CORS settings if needed

## Security Notes

- API validates both application_id and email
- No sensitive data in error messages
- PDF generation includes watermark
- CORS properly configured for web clients

---

**Note**: Make sure Laravel server is running (`php artisan serve`) before testing any endpoints.