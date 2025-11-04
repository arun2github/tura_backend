# Flutter Base64 Image Preview Implementation Guide

## Problem Analysis
You're experiencing issues with displaying uploaded base64 images from your server. The main problems are:
1. Base64 data from server needs proper decoding for Image.memory()
2. Data URL format handling (data:image/jpeg;base64,...)
3. Error handling for corrupted or invalid base64 data
4. Preview functionality for uploaded documents

## Solution Overview

### 1. Controller Improvements

Add these methods to your `ApplicationFormController`:

```dart
import 'dart:convert';
import 'dart:typed_data';

// Convert base64 data URL to bytes for Image.memory()
Uint8List? _base64ToBytes(String base64String) {
  try {
    // Handle data URL format: data:image/jpeg;base64,/9j/4AAQ...
    if (base64String.startsWith('data:')) {
      final base64Data = base64String.split(',').last;
      return base64Decode(base64Data);
    }
    // Handle raw base64 string
    return base64Decode(base64String);
  } catch (e) {
    print('❌ Error converting base64 to bytes: $e');
    return null;
  }
}

// Build image widget for uploaded base64 images
Widget buildUploadedImageWidget(String base64Data, {
  double? width,
  double? height,
  BoxFit? fit,
}) {
  try {
    final bytes = _base64ToBytes(base64Data);
    if (bytes == null) {
      return _buildImageErrorWidget('Invalid image data', width, height);
    }

    return Image.memory(
      bytes,
      width: width,
      height: height,
      fit: fit ?? BoxFit.cover,
      errorBuilder: (context, error, stackTrace) {
        print('🔥 Image.memory error: $error');
        return _buildImageErrorWidget('Failed to display image', width, height);
      },
      frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
        if (wasSynchronouslyLoaded) return child;
        
        return AnimatedOpacity(
          opacity: frame == null ? 0 : 1,
          duration: const Duration(milliseconds: 300),
          child: child,
        );
      },
    );
  } catch (e) {
    print('❌ Error building uploaded image widget: $e');
    return _buildImageErrorWidget('Error loading image', width, height);
  }
}

// Error widget for failed image loading
Widget _buildImageErrorWidget(String message, double? width, double? height) {
  return Container(
    width: width,
    height: height,
    decoration: BoxDecoration(
      color: Colors.grey.shade100,
      border: Border.all(color: Colors.grey.shade300),
      borderRadius: BorderRadius.circular(8),
    ),
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(
          Icons.broken_image_outlined,
          size: 48,
          color: Colors.grey.shade500,
        ),
        const SizedBox(height: 8),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Text(
            message,
            style: TextStyle(
              color: Colors.grey.shade600,
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ),
      ],
    ),
  );
}

// Preview uploaded document with proper base64 handling
void previewUploadedDocument(Map<String, dynamic> uploadedDoc) {
  try {
    final fileName = uploadedDoc['file_name'] ?? 'Unknown';
    final fileExtension = uploadedDoc['file_extension']?.toLowerCase() ?? '';
    final base64Data = uploadedDoc['file_base64'] ?? '';
    
    print('👀 Previewing uploaded document: $fileName');
    
    if (base64Data.isEmpty) {
      Get.snackbar(
        'Error',
        'No image data available for preview',
        snackPosition: SnackPosition.TOP,
        backgroundColor: Colors.red,
        colorText: Colors.white,
      );
      return;
    }

    // Show preview dialog based on file type
    Get.dialog(
      Dialog(
        child: Container(
          width: Get.width * 0.9,
          height: Get.height * 0.8,
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              // Header
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Document Preview',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          fileName,
                          style: const TextStyle(
                            fontSize: 14,
                            color: Colors.grey,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Get.back(),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
              const Divider(),
              
              // Content based on file type
              Expanded(
                child: ['jpg', 'jpeg', 'png', 'gif', 'bmp'].contains(fileExtension)
                    ? Center(
                        child: Container(
                          constraints: BoxConstraints(
                            maxWidth: Get.width * 0.8,
                            maxHeight: Get.height * 0.6,
                          ),
                          child: buildUploadedImageWidget(
                            base64Data,
                            fit: BoxFit.contain,
                          ),
                        ),
                      )
                    : _buildNonImagePreview(uploadedDoc, fileExtension),
              ),
              
              // Footer
              const Divider(),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Uploaded: ${uploadedDoc['uploaded_at']?.toString().split('T')[0] ?? 'Unknown date'}',
                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  ),
                  Text(
                    'Type: ${fileExtension.toUpperCase()}',
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  } catch (e) {
    print('❌ Error previewing uploaded document: $e');
    Get.snackbar(
      'Error',
      'Failed to preview document: ${e.toString()}',
      snackPosition: SnackPosition.TOP,
      backgroundColor: Colors.red,
      colorText: Colors.white,
    );
  }
}

// Handle non-image file previews
Widget _buildNonImagePreview(Map<String, dynamic> doc, String extension) {
  IconData iconData;
  Color iconColor;
  String fileType;
  
  if (extension == 'pdf') {
    iconData = Icons.picture_as_pdf;
    iconColor = Colors.red.shade600;
    fileType = 'PDF Document';
  } else {
    iconData = Icons.insert_drive_file;
    iconColor = Colors.grey.shade600;
    fileType = 'Document File';
  }
  
  return Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(iconData, size: 80, color: iconColor),
        const SizedBox(height: 16),
        Text(
          fileType,
          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        Text(
          doc['file_name'] ?? 'Unknown',
          style: const TextStyle(fontSize: 14, color: Colors.grey),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        Text(
          'File type: ${extension.toUpperCase()}',
          style: const TextStyle(fontSize: 12, color: Colors.grey),
        ),
        const SizedBox(height: 8),
        const Text(
          'Preview is available for images only.\nDocument is stored and ready for submission.',
          style: TextStyle(fontSize: 12, color: Colors.grey),
          textAlign: TextAlign.center,
        ),
      ],
    ),
  );
}
```

### 2. Widget Updates

In your `UploadsSection` widget, replace the `Image.network()` calls with:

```dart
// For uploaded photos
ClipRRect(
  borderRadius: BorderRadius.circular(8),
  child: controller.buildUploadedImageWidget(
    controller.uploadedPhotoUrl.value,
    width: double.infinity,
    height: 200,
    fit: BoxFit.cover,
  ),
)

// For uploaded signatures
ClipRRect(
  borderRadius: BorderRadius.circular(8),
  child: controller.buildUploadedImageWidget(
    controller.uploadedSignatureUrl.value,
    width: double.infinity,
    height: 200,
    fit: BoxFit.cover,
  ),
)
```

### 3. Add Preview Buttons

Add preview buttons to your uploaded images:

```dart
// Preview button for uploaded photo
Positioned(
  bottom: 8,
  right: 8,
  child: Container(
    decoration: BoxDecoration(
      color: Colors.black54,
      borderRadius: BorderRadius.circular(20),
    ),
    child: IconButton(
      onPressed: () {
        final photoDoc = controller.uploadedDocuments['profile_photo'];
        if (photoDoc != null) {
          controller.previewUploadedDocument(photoDoc);
        }
      },
      icon: const Icon(Icons.zoom_in, color: Colors.white, size: 20),
      tooltip: 'Preview photo',
    ),
  ),
)
```

### 4. Loading Documents with Base64

Update your document loading method:

```dart
Future<void> loadUploadedDocuments(int userId, int jobId) async {
  try {
    isLoadingUploads.value = true;
    print('📋 Loading uploaded documents for user: $userId, job: $jobId');

    final response = await _apiService.getUploadedDocuments(userId, jobId);
    
    if (response['success'] == true && response['documents'] != null) {
      final documents = response['documents'] as List;
      print('📄 Found ${documents.length} uploaded documents');

      // Clear existing uploaded documents
      uploadedDocuments.clear();
      uploadedPhotoUrl.value = '';
      uploadedSignatureUrl.value = '';

      for (final doc in documents) {
        final documentType = doc['document_type']?.toString() ?? '';
        final base64Data = doc['file_base64']?.toString() ?? '';
        
        if (base64Data.isNotEmpty) {
          uploadedDocuments[documentType] = {
            'id': doc['id'],
            'file_name': doc['file_name'],
            'file_extension': doc['file_extension'],
            'file_size': doc['file_size'],
            'file_base64': base64Data,
            'uploaded_at': doc['uploaded_at'],
            'document_type': documentType,
          };

          // Set specific URLs for photo and signature
          if (documentType == 'profile_photo') {
            uploadedPhotoUrl.value = base64Data;
            print('📷 Set uploaded photo (${base64Data.length} chars)');
          } else if (documentType == 'signature') {
            uploadedSignatureUrl.value = base64Data;
            print('✍️ Set uploaded signature (${base64Data.length} chars)');
          }
        }
      }

      print('✅ Loaded ${uploadedDocuments.length} documents successfully');
      uploadedDocuments.refresh();
    }
  } catch (e) {
    print('❌ Error loading uploaded documents: $e');
    Get.snackbar(
      'Error',
      'Failed to load uploaded documents: ${e.toString()}',
      snackPosition: SnackPosition.TOP,
      backgroundColor: Colors.red,
      colorText: Colors.white,
    );
  } finally {
    isLoadingUploads.value = false;
  }
}
```

## Key Points

1. **Use `Image.memory()`** instead of `Image.network()` for base64 data
2. **Handle data URL format** by splitting on comma and taking the base64 part
3. **Proper error handling** with fallback error widgets
4. **Base64 validation** before attempting to decode
5. **Preview functionality** for both new files and uploaded documents
6. **Animated loading** with fade-in effect for better UX

## Testing

After implementing these changes:
1. Upload a document via your API
2. Reload the form to fetch uploaded documents
3. Verify images display correctly in the preview areas
4. Test the preview dialog functionality
5. Check error handling with invalid base64 data

This implementation should resolve your base64 image display issues and provide a smooth preview experience for your users.