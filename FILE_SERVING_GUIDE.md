# File Serving Implementation Guide

## Overview
The Laravel API now includes file serving functionality to serve uploaded documents to Flutter web applications. Files are served via the `/api/files/{filename}` endpoint with proper CORS headers, security measures, and caching.

## Implementation Details

### 1. API Endpoint
- **URL**: `GET /api/files/{filename}`
- **Purpose**: Serve uploaded files from storage with proper headers
- **CORS Support**: Includes proper CORS headers for cross-origin requests
- **Security**: Validates file extensions and prevents directory traversal attacks

### 2. File Storage Locations
The system searches for files in multiple possible locations:
- `storage/app/uploads/`
- `storage/app/public/uploads/`
- `public/uploads/`
- `storage/uploads/`

### 3. Security Features
- **File Extension Validation**: Only allows specific file types (jpg, jpeg, png, pdf, doc, docx)
- **Directory Traversal Prevention**: Blocks paths containing `..`, `/`, or `\`
- **File Existence Check**: Verifies file exists before serving

### 4. Enhanced API Responses
The `getApplicationProgress` API now includes file URLs in the `uploaded_documents` section:

```json
{
  "existing_data": {
    "uploaded_documents": [
      {
        "document_type": "photo",
        "file_name": "CEO_SIGN.jpg",
        "uploaded_at": "2025-11-04T10:30:00Z",
        "file_size": 125460,
        "mime_type": "image/jpeg",
        "file_url": "https://laravelv2.turamunicipalboard.com/api/files/CEO_SIGN.jpg",
        "file_exists": true
      }
    ]
  }
}
```

## Usage in Flutter Web

### 1. Loading Images
```dart
Image.network(
  'https://laravelv2.turamunicipalboard.com/api/files/CEO_SIGN.jpg',
  loadingBuilder: (context, child, loadingProgress) {
    if (loadingProgress == null) return child;
    return CircularProgressIndicator(
      value: loadingProgress.expectedTotalBytes != null
          ? loadingProgress.cumulativeBytesLoaded / loadingProgress.expectedTotalBytes!
          : null,
    );
  },
  errorBuilder: (context, error, stackTrace) {
    return Container(
      width: 100,
      height: 100,
      color: Colors.grey[300],
      child: Icon(Icons.error, color: Colors.red),
    );
  },
)
```

### 2. PDF Display
```dart
// Use a PDF viewer package or open in browser
void openPDF(String fileUrl) {
  html.window.open(fileUrl, '_blank');
}
```

### 3. Handling File URLs from API
```dart
class ApplicationDocument {
  final String documentType;
  final String fileName;
  final String? fileUrl;
  final bool fileExists;
  
  ApplicationDocument.fromJson(Map<String, dynamic> json)
    : documentType = json['document_type'],
      fileName = json['file_name'],
      fileUrl = json['file_url'],
      fileExists = json['file_exists'] ?? false;
}

// In your API call
List<ApplicationDocument> documents = (responseData['existing_data']['uploaded_documents'] as List)
    .map((doc) => ApplicationDocument.fromJson(doc))
    .toList();
```

## Testing

### 1. Direct API Test
Test the file serving endpoint directly:
```bash
curl -X GET "https://laravelv2.turamunicipalboard.com/api/files/CEO_SIGN.jpg"
```

### 2. Browser Test
Open the test page:
```
https://laravelv2.turamunicipalboard.com/test_file_serving.html
```

### 3. Flutter Integration Test
```dart
void testFileLoading() async {
  final response = await http.get(
    Uri.parse('https://laravelv2.turamunicipalboard.com/api/files/CEO_SIGN.jpg'),
  );
  
  if (response.statusCode == 200) {
    print('File loaded successfully');
    print('Content-Type: ${response.headers['content-type']}');
    print('File size: ${response.contentLength} bytes');
  } else {
    print('Failed to load file: ${response.statusCode}');
  }
}
```

## API Response Headers

The file serving endpoint includes these headers:
- `Content-Type`: Proper MIME type (image/jpeg, application/pdf, etc.)
- `Content-Length`: File size in bytes
- `Cache-Control`: public, max-age=86400 (24 hour cache)
- `Access-Control-Allow-Origin`: * (allows cross-origin requests)
- `Content-Disposition`: inline for images, attachment for documents

## Error Handling

### Common Error Responses:

1. **File Not Found (404)**:
```json
{
  "success": false,
  "message": "File not found",
  "filename": "nonexistent.jpg"
}
```

2. **Invalid File Type (403)**:
```json
{
  "success": false,
  "message": "File type not allowed",
  "allowed_types": ["jpg", "jpeg", "png", "pdf", "doc", "docx"]
}
```

3. **Invalid Filename (400)**:
```json
{
  "success": false,
  "message": "Invalid filename"
}
```

## Deployment Notes

1. Ensure the storage directories exist and are writable
2. Verify CORS configuration allows your Flutter web domain
3. Test file serving after deployment
4. Monitor file access logs for security issues

## File URL Generation

The system automatically generates file URLs in the `getApplicationProgress` API response. Files are accessible at:
```
https://laravelv2.turamunicipalboard.com/api/files/{filename}
```

Where `{filename}` is URL-encoded to handle special characters and spaces.

## Troubleshooting

### Issue: Files not loading in Flutter
- Check CORS configuration
- Verify file exists in storage
- Check file extension is allowed
- Test direct URL access

### Issue: 404 File Not Found
- Verify file exists in one of the searched storage paths
- Check filename spelling and encoding
- Ensure proper file permissions

### Issue: CORS Errors
- Update `config/cors.php` to include your Flutter web domain
- Check that API route is not protected by auth middleware
- Verify OPTIONS requests are handled properly