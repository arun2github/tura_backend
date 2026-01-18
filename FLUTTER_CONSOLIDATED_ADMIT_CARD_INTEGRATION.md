# Flutter Web Integration - Consolidated Admit Card Only

## 🎯 **Consolidated Admit Card API Integration**

### **Base Configuration:**
```dart
class AdmitCardConfig {
  static const String BASE_URL = "https://your-production-domain.com/api";
  static const String CONSOLIDATED_ENDPOINT = "/admit-card/exam-schedule";
  static const String CONSOLIDATED_DOWNLOAD = "/admit-card/download-consolidated";
}
```

### **API Service for Consolidated Admit Cards:**

```dart
import 'dart:convert';
import 'dart:html' as html;
import 'package:http/http.dart' as http;

class ConsolidatedAdmitCardService {
  static const String baseUrl = "https://your-domain.com/api";
  
  /// Get consolidated exam schedule and download info
  static Future<ConsolidatedAdmitCardResponse> getConsolidatedAdmitCard(String email) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/admit-card/exam-schedule'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({'email': email}),
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return ConsolidatedAdmitCardResponse.fromJson(data);
      } else {
        final error = jsonDecode(response.body);
        throw AdmitCardException(error['message'] ?? 'Failed to get admit card');
      }
    } catch (e) {
      throw AdmitCardException('Network error: $e');
    }
  }
  
  /// Download consolidated admit card PDF
  static Future<void> downloadConsolidatedAdmitCard(String email) async {
    try {
      final encodedEmail = base64Encode(utf8.encode(email));
      final downloadUrl = '$baseUrl/admit-card/download-consolidated/$encodedEmail';
      
      // For Flutter Web - trigger download
      html.AnchorElement(href: downloadUrl)
        ..setAttribute('download', '')
        ..setAttribute('target', '_blank')
        ..click();
        
    } catch (e) {
      throw AdmitCardException('Download failed: $e');
    }
  }
  
  /// Get consolidated download URL
  static String getConsolidatedDownloadUrl(String email) {
    final encodedEmail = base64Encode(utf8.encode(email));
    return '$baseUrl/admit-card/download-consolidated/$encodedEmail';
  }
}
```

### **Data Models:**

```dart
class ConsolidatedAdmitCardResponse {
  final bool status;
  final String message;
  final CandidateInfo candidateInfo;
  final ExamSchedule examSchedule;
  final List<Warning> warnings;
  final String? consolidatedDownloadUrl;
  
  ConsolidatedAdmitCardResponse({
    required this.status,
    required this.message,
    required this.candidateInfo,
    required this.examSchedule,
    required this.warnings,
    this.consolidatedDownloadUrl,
  });
  
  factory ConsolidatedAdmitCardResponse.fromJson(Map<String, dynamic> json) {
    return ConsolidatedAdmitCardResponse(
      status: json['status'],
      message: json['message'],
      candidateInfo: CandidateInfo.fromJson(json['candidate_info']),
      examSchedule: ExamSchedule.fromJson(json['exam_schedule']),
      warnings: (json['warnings'] as List? ?? [])
          .map((w) => Warning.fromJson(w))
          .toList(),
      consolidatedDownloadUrl: json['consolidated_download_url'],
    );
  }
}

class CandidateInfo {
  final String fullName;
  final String email;
  final String dateOfBirth;
  final String gender;
  final String category;
  final int totalJobsApplied;
  final int totalExams;
  
  CandidateInfo({
    required this.fullName,
    required this.email,
    required this.dateOfBirth,
    required this.gender,
    required this.category,
    required this.totalJobsApplied,
    required this.totalExams,
  });
  
  factory CandidateInfo.fromJson(Map<String, dynamic> json) {
    return CandidateInfo(
      fullName: json['full_name'],
      email: json['email'],
      dateOfBirth: json['date_of_birth'],
      gender: json['gender'],
      category: json['category'],
      totalJobsApplied: json['total_jobs_applied'],
      totalExams: json['total_exams'],
    );
  }
}

class ExamSchedule {
  final int totalPapers;
  final int generalPapers;
  final int corePapers;
  final List<ExamPaper> papers;
  final bool hasConflicts;
  
  ExamSchedule({
    required this.totalPapers,
    required this.generalPapers,
    required this.corePapers,
    required this.papers,
    required this.hasConflicts,
  });
  
  factory ExamSchedule.fromJson(Map<String, dynamic> json) {
    return ExamSchedule(
      totalPapers: json['total_papers'],
      generalPapers: json['general_papers'],
      corePapers: json['core_papers'],
      papers: (json['papers'] as List)
          .map((p) => ExamPaper.fromJson(p))
          .toList(),
      hasConflicts: json['has_conflicts'],
    );
  }
}

class ExamPaper {
  final String paperType;
  final int paperNumber;
  final String subject;
  final String examDate;
  final String examTime;
  final String reportingTime;
  final String venueName;
  final String venueAddress;
  final String? jobTitle;
  final String rollNumber;
  final String applicationId;
  
  ExamPaper({
    required this.paperType,
    required this.paperNumber,
    required this.subject,
    required this.examDate,
    required this.examTime,
    required this.reportingTime,
    required this.venueName,
    required this.venueAddress,
    this.jobTitle,
    required this.rollNumber,
    required this.applicationId,
  });
  
  factory ExamPaper.fromJson(Map<String, dynamic> json) {
    return ExamPaper(
      paperType: json['paper_type'],
      paperNumber: json['paper_number'],
      subject: json['subject'],
      examDate: json['exam_date'],
      examTime: json['exam_time'],
      reportingTime: json['reporting_time'],
      venueName: json['venue_name'],
      venueAddress: json['venue_address'],
      jobTitle: json['job_title'],
      rollNumber: json['roll_number'],
      applicationId: json['application_id'],
    );
  }
}

class Warning {
  final bool timeConflicts;
  final String message;
  final int conflictsCount;
  
  Warning({
    required this.timeConflicts,
    required this.message,
    required this.conflictsCount,
  });
  
  factory Warning.fromJson(Map<String, dynamic> json) {
    return Warning(
      timeConflicts: json['time_conflicts'],
      message: json['message'],
      conflictsCount: json['conflicts_count'],
    );
  }
}

class AdmitCardException implements Exception {
  final String message;
  AdmitCardException(this.message);
  
  @override
  String toString() => 'AdmitCardException: $message';
}
```

### **Flutter Widget Example:**

```dart
class ConsolidatedAdmitCardScreen extends StatefulWidget {
  @override
  _ConsolidatedAdmitCardScreenState createState() => _ConsolidatedAdmitCardScreenState();
}

class _ConsolidatedAdmitCardScreenState extends State<ConsolidatedAdmitCardScreen> {
  final TextEditingController emailController = TextEditingController();
  bool isLoading = false;
  ConsolidatedAdmitCardResponse? admitCardData;
  String? errorMessage;
  
  Future<void> getAdmitCard() async {
    if (emailController.text.isEmpty) {
      setState(() => errorMessage = 'Please enter your email');
      return;
    }
    
    setState(() {
      isLoading = true;
      errorMessage = null;
      admitCardData = null;
    });
    
    try {
      final result = await ConsolidatedAdmitCardService.getConsolidatedAdmitCard(
        emailController.text.trim()
      );
      
      setState(() {
        admitCardData = result;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = e.toString();
        isLoading = false;
      });
    }
  }
  
  Future<void> downloadAdmitCard() async {
    try {
      await ConsolidatedAdmitCardService.downloadConsolidatedAdmitCard(
        emailController.text.trim()
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Download failed: $e')),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Consolidated Admit Card')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: emailController,
              decoration: InputDecoration(
                labelText: 'Email Address',
                hintText: 'Enter your registered email',
              ),
            ),
            SizedBox(height: 16),
            
            ElevatedButton(
              onPressed: isLoading ? null : getAdmitCard,
              child: isLoading 
                ? CircularProgressIndicator() 
                : Text('Get Admit Card'),
            ),
            
            if (errorMessage != null)
              Container(
                margin: EdgeInsets.only(top: 16),
                padding: EdgeInsets.all(12),
                color: Colors.red[100],
                child: Text(errorMessage!, style: TextStyle(color: Colors.red)),
              ),
              
            if (admitCardData != null) ...[
              SizedBox(height: 16),
              Text('Candidate: ${admitCardData!.candidateInfo.fullName}'),
              Text('Total Exams: ${admitCardData!.examSchedule.totalPapers}'),
              
              if (!admitCardData!.examSchedule.hasConflicts)
                ElevatedButton(
                  onPressed: downloadAdmitCard,
                  child: Text('Download Consolidated Admit Card'),
                )
              else
                Text('Time conflicts detected - contact authority'),
                
              // Show exam schedule
              Expanded(
                child: ListView.builder(
                  itemCount: admitCardData!.examSchedule.papers.length,
                  itemBuilder: (context, index) {
                    final paper = admitCardData!.examSchedule.papers[index];
                    return Card(
                      child: ListTile(
                        title: Text('${paper.paperType}: ${paper.subject}'),
                        subtitle: Text('${paper.examDate} - ${paper.examTime}'),
                        trailing: Text('Roll: ${paper.rollNumber}'),
                      ),
                    );
                  },
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
```

## 📋 **Key Features:**

1. **Single API Call** - Get all exam details in one request
2. **Smart Filename** - PDFs named as `email_admitcard_consolidated_timestamp.pdf`
3. **Conflict Detection** - Handles time conflicts automatically
4. **Clean Integration** - Only consolidated functionality, no individual cards
5. **Error Handling** - Comprehensive exception handling
6. **Web Download** - Direct PDF download in browser

## 🚀 **Production Deployment:**

Just deploy these files:
- `app/Http/Controllers/Api/AdmitCardController.php`
- `app/Models/TuraAdmitCard.php`
- `resources/views/pdf/admit_card.blade.php`
- `routes/api.php` (admit-card section)

**Your admit card system is production-ready with the new email-based filename format!** 📄✨