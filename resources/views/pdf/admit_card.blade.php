<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Card - {{ $roll_number }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Arial", "Helvetica", sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 11px;
            line-height: 1.35;
            color: #2c3e50;
            background: #ffffff;
        }

        /* ---------------- HEADER ---------------- */
        .header {
            background: linear-gradient(135deg, #f8fdfd 0%, #ffffff 100%);
            padding: 12px;
            margin-bottom: 12px;
            display: table;
            width: 100%;
            border-bottom: 3px solid #008080;
            border-radius: 3px 3px 0 0;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
            padding: 2px;
        }

        .header-left {
            width: 20%;
            text-align: left;
        }

        .header-center {
            width: 60%;
            text-align: center;
        }

        .header-right {
            width: 20%;
            text-align: right;
        }

        .government-seal {
            width: 50px;
            height: 50px;
            margin: 0;
        }

        .government-seal img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .logo-placeholder {
            width: 55px;
            height: 55px;
            border: 2px solid #008080;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #e6f7f7 0%, #ffffff 100%);
            color: #008080;
        }

        .organization-name {
            font-size: 17px;
            font-weight: 800;
            color: #008080;
            margin-bottom: 2px;
            line-height: 1.0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .department-name {
            font-size: 11px;
            font-weight: 600;
            color: #2c3e50;
            margin: 1px 0;
            line-height: 1.0;
        }

        .admit-card-title {
            font-size: 15px;
            font-weight: 800;
            color: #008080;
            border-bottom: 2px solid #008080;
            margin: 3px 0;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: inline-block;
            padding-bottom: 2px;
        }

        .job-title {
            font-size: 11px;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.1;
        }

        .candidate-photo-header {
            width: 65px;
            height: 75px;
            border: 1px solid #000;
            padding: 1px;
            background: #fff;
            text-align: center;
            margin-left: auto;
        }

        .candidate-photo-header img {
            width: 63px;
            height: 73px;
            object-fit: cover;
        }

        .photo-placeholder-header {
            width: 63px;
            height: 73px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #000;
            border: 1px solid #ccc;
        }

        .signature-box {
            margin-top: 2px;
            padding: 2px;
            border: 1px solid #000;
            height: 20px;
            background: #fff;
            font-size: 8px;
            font-weight: 500;
            text-align: center;
            line-height: 1.1;
        }

        /* ---------------- DETAILS SECTION ---------------- */
        .details-section {
            margin-top: 8px;
        }

        .main-content {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border: 2px solid #008080;
            background: #ffffff;
            border-radius: 4px;
            overflow: hidden;
        }

        .left-column {
            display: table-cell;
            width: 70%;
            vertical-align: top;
            padding: 10px;
            background: linear-gradient(135deg, #fdfdfd 0%, #f8fdfd 100%);
            border-right: 2px solid #008080;
        }

        .right-column {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            padding: 10px;
            text-align: center;
            background: #ffffff;
        }

        .photo-container {
            margin-bottom: 8px;
        }

        .db-photo-box {
            border: 2px solid #008080;
            padding: 3px;
            background: #ffffff;
            text-align: center;
            margin-bottom: 6px;
            height: 85px;
            width: 85px;
            margin-left: auto;
            margin-right: auto;
            border-radius: 2px;
        }

        .paste-photo-box {
            border: 2px dashed #008080;
            padding: 3px;
            background: #f8fdfd;
            text-align: center;
            height: 85px;
            width: 85px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 600;
            color: #008080;
            border-radius: 2px;
        }

        .db-photo-box img {
            max-width: 76px;
            max-height: 76px;
            object-fit: cover;
        }

        .enhanced-exam-section {
            background: #ffffff;
            border: 1px solid #008080;
            padding: 0;
            margin-bottom: 8px;
            border-radius: 2px;
        }

        .enhanced-exam-header {
            background: #008080;
            color: white;
            font-weight: 700;
            text-align: center;
            padding: 5px 12px;
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
        }

        .exam-row-content {
            background: #ffffff;
            padding: 6px 15px 6px 15px;
            margin: 0;
            font-size: 10px;
            line-height: 1.3;
        }

        .exam-item {
            display: inline-block;
            margin-right: 18px;
            margin-bottom: 4px;
            white-space: nowrap;
            font-weight: 500;
            font-size: 10px;
        }

        .exam-item strong {
            color: #008080;
            font-weight: 600;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            background: #ffffff;
            border-radius: 3px;
            overflow: hidden;
        }

        .details-table td {
            padding: 6px 8px;
            border: 1px solid #008080;
            font-weight: 500;
        }

        .label-cell {
            width: 140px;
            background: linear-gradient(135deg, #e6f7f7 0%, #f0fafa 100%);
            font-weight: 700;
            color: #008080;
        }

        .value-cell {
            background: #ffffff;
            font-weight: 500;
            color: #2c3e50;
        }

        /* ---------------- EXAM DETAILS ---------------- */
        .exam-details {
            margin-top: 10px;
            border: 2px solid #008080;
            padding: 8px;
            background: linear-gradient(135deg, #fdfdfd 0%, #f8fdfd 100%);
            border-radius: 4px;
        }

        .exam-details h3 {
            margin: 0 0 6px;
            color: #008080;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 3px;
            overflow: hidden;
        }

        .exam-table td {
            padding: 6px;
            border: 1px solid #008080;
            font-size: 11px;
        }

        .exam-label {
            width: 130px;
            font-weight: 700;
            background: linear-gradient(135deg, #e6f7f7 0%, #f0fafa 100%);
            color: #008080;
        }

        /* ---------------- SIGNATURES ---------------- */
        .signature-section {
            margin-top: 18px;
            display: table;
            width: 100%;
            font-size: 11px;
            border-top: 2px solid #008080;
            padding-top: 10px;
        }

        .signature-left {
            display: table-cell;
            width: 70%;
            text-align: left;
            padding-left: 18px;
            vertical-align: top;
        }

        .signature-right {
            display: table-cell;
            width: 30%;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-top: 2px solid #008080;
            margin-top: 30px;
            padding-top: 6px;
            font-weight: 700;
            color: #008080;
        }

        /* ---------------- FOOTER ---------------- */
        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 10px;
            border-top: 2px solid #008080;
            padding-top: 6px;
            color: #2c3e50;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 45px;
            color: rgba(0, 128, 128, 0.04);
            font-weight: 800;
            z-index: -1;
        }

        /* ---------------- PAGE BREAK ---------------- */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

<div class="watermark">ADMIT CARD</div>

<!-- ================= PAGE 1 ================= -->
<div class="header">
    <!-- Left Column - Logo -->
    <div class="header-left">
        <div class="government-seal">
            @if(file_exists(public_path('images/email/logo.png')))
                <img src="{{ public_path('images/email/logo.png') }}" class="government-seal">
            @else
                <div class="logo-placeholder">
                    TURA<br>MUNICIPAL
                </div>
            @endif
        </div>
    </div>

    <!-- Center Column - Admit Card Title -->
    <div class="header-center">
        <div class="organization-name">Tura Municipal Board</div>
        <div class="department-name">West Garo Hills, Meghalaya</div>
        <div class="admit-card-title">ADMIT CARD</div>
        <div class="job-title">{{ $job_title }}</div>
    </div>

    <!-- Right Column - Empty -->
    <div class="header-right">
        <!-- Header photo and signature removed -->
    </div>
</div>

<div class="main-content">
    <!-- Left Column - General Information -->
    <div class="left-column">
        <table class="details-table">
            <tr><td class="label-cell">Candidate Name:</td><td class="value-cell">{{ strtoupper($full_name) }}</td></tr>
            @if($date_of_birth)<tr><td class="label-cell">Date of Birth:</td><td class="value-cell">{{ $date_of_birth }}</td></tr>@endif
            @if($gender)<tr><td class="label-cell">Gender:</td><td class="value-cell">{{ strtoupper($gender) }}</td></tr>@endif
            @if($category)<tr><td class="label-cell">Category:</td><td class="value-cell">{{ strtoupper($category) }}</td></tr>@endif
            @if($email)<tr><td class="label-cell">Email:</td><td class="value-cell">{{ $email }}</td></tr>@endif
        </table>
    </div>

    <!-- Right Column - Photo Sections -->
    <div class="right-column">
        <div class="photo-container">
            <div class="db-photo-box">
                @if($photo_base64)
                    <img src="data:image/jpeg;base64,{{ $photo_base64 }}">
                @else
                    <div style="height: 92px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #ccc;">
                        <div style="border: 1px dashed #ccc; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; background: #f5f5f5;">
                            📷
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="photo-container">
            <div class="paste-photo-box">
                AFFIX YOUR<br>RECENT PASSPORT<br>SIZE PHOTOGRAPH
            </div>
        </div>
    </div>
</div>

<!-- EXAM SCHEDULE SECTION -->
@if(isset($is_consolidated) && $is_consolidated && isset($consolidated_schedule))
    <!-- CONSOLIDATED EXAM SCHEDULE -->
    <div class="enhanced-exam-section">
        <!-- <div class="enhanced-exam-header  " >EXAMINATION SCHEDULE</div> -->
        
        @if(isset($consolidated_schedule['has_conflicts']) && $consolidated_schedule['has_conflicts'])
            <!-- TIME CONFLICT WARNING -->
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 4px; padding: 8px; margin-bottom: 12px;">
                <div style="color: #856404; font-weight: 700; font-size: 12px; margin-bottom: 4px;">⚠️ TIME CONFLICT DETECTED</div>
                <div style="color: #856404; font-size: 10px; line-height: 1.3;">
                    Some exam times overlap. Please contact the examination authority immediately.
                </div>
            </div>
        @endif
        
        @foreach($consolidated_schedule['papers'] as $index => $paper)
            <div style="margin-bottom: {{ $index < count($consolidated_schedule['papers']) - 1 ? '3px' : '0' }}; border: 1px solid #008080; background: #ffffff; border-radius: 2px;">
                <div style="background: #008080; color: white; font-weight: 700; text-align: center; padding: 4px 8px; font-size: 10px; text-transform: uppercase;">
                    {{ strtoupper($paper['paper_type']) }} 
                    @if(isset($paper['job_title']) && !$paper['is_common_paper'])
                        - {{ strtoupper($paper['job_title']) }}
                    @endif
                </div>
                <div style="background: #ffffff; padding: 5px 15px 5px 15px; font-size: 10px; line-height: 1.3;">
                    @if(isset($paper['job_title']) && !$paper['is_common_paper'])
                        <div style="color: #008080; font-weight: 700; font-size: 10px; margin-bottom: 3px; text-transform: uppercase;">{{ $paper['job_title'] }}</div>
                    @endif
                    <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Subject:</strong> {{ strtoupper($paper['subject']) }}</div>
                    <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Roll:</strong> <span style="color: #2c3e50; font-weight: 600;">{{ $paper['roll_number'] }}</span></div>
                    <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Date:</strong> {{ date('d-m-Y', strtotime($paper['exam_date'])) }}</div>
                    <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Time:</strong> {{ $paper['exam_time'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <!-- INDIVIDUAL ADMIT CARD EXAM SCHEDULE -->
    @if($has_slot_1 || $has_slot_2)
    <div class="enhanced-exam-section">
        <div class="enhanced-exam-header">EXAMINATION SCHEDULE</div>
        
        @if($has_slot_1)
        <div style="margin-bottom: 2px;">
            <div style="background: #008080; color: white; font-weight: 700; text-align: center; padding: 4px; font-size: 10px; text-transform: uppercase;">PAPER 1</div>
            <div class="exam-row-content">
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Subject:</strong> {{ strtoupper($subject_slot_1) }}</div>
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Date:</strong> {{ $exam_date_slot_1 }}</div>
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Time:</strong> {{ $exam_time_slot_1 }}</div>
            </div>
        </div>
        @endif
        
        @if($has_slot_2)
        <div>
            <div style="background: #008080; color: white; font-weight: 700; text-align: center; padding: 4px; font-size: 10px; text-transform: uppercase;">PAPER 2</div>
            <div class="exam-row-content">
                @if(isset($job_title))
                    <div style="color: #008080; font-weight: 700; font-size: 10px; margin-bottom: 3px; text-transform: uppercase;">{{ $job_title }}</div>
                @endif
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Subject:</strong> {{ strtoupper($subject_slot_2) }}</div>
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Date:</strong> {{ $exam_date_slot_2 }}</div>
                <div style="display: inline-block; margin-right: 18px; margin-bottom: 4px; white-space: nowrap; font-weight: 500; font-size: 10px;"><strong style="color: #008080; font-weight: 600;">Time:</strong> {{ $exam_time_slot_2 }}</div>
            </div>
        </div>
        @endif
    </div>
    @endif
@endif

<div class="exam-details">
    <h3>EXAMINATION VENUE</h3>
    <table class="exam-table">
        <tr><td class="exam-label">Exam Center:</td><td>{{ $venue_name }}</td></tr>
        <tr><td class="exam-label">Address:</td><td>{{ $venue_address }}</td></tr>
    </table>
</div>

<div class="signature-section">
    <div class="signature-left">
        <!-- Candidate signature is now in header -->
    </div>
    <div class="signature-right">
        <div class="signature-line">Authorized Signatory<br>Tura Municipal Board</div>
    </div>
</div>

<div class="footer">
    <strong>Note:</strong> This is a computer-generated admit card; no signature is required.  
    <div>Downloaded on: {{ date('d-m-Y H:i:s') }}</div>
</div>

<!-- ================= PAGE 2 (INSTRUCTIONS) ================= -->
<div class="page-break"></div>

<div style="text-align:center;margin-bottom:12px;">
    <h2 style="margin:0;font-size:18px;font-weight:800;color:#008080;text-transform:uppercase;letter-spacing:0.5px;">TURA MUNICIPAL BOARD</h2>
    <p style="margin:4px 0;font-size:12px;color:#2c3e50;font-weight:600;">West Garo Hills, Meghalaya</p>
    <h3 style="margin:6px 0;font-size:15px;color:#008080;border-bottom:2px solid #008080;display:inline-block;padding-bottom:2px;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;">INSTRUCTIONS TO CANDIDATES</h3>
</div>

<div style="font-size:11px;line-height:1.4;color:#2c3e50;">
   <ol style="padding-left: 15px; margin: 0; font-size: 10px; line-height: 1.3;">
    <li>Check the Intimation Letter carefully and bring discrepancies, if any, to the notice of the Tura Municipal Board immediately.</li>

    <li>Candidates should report to the Examination Hall at least <strong>1 hour before</strong> the start of the examination and observe silence in the Examination Hall. Any conversation or disturbance in the Examination Hall shall be deemed as misbehaviour. If a candidate is found using unfair means, his/her candidature shall be cancelled.</li>

    <li>The candidate should ensure that the <strong>Question Paper is as per his/her opted subject</strong> indicated in the Admit Card. In case the subject of the Question Paper is other than his/her opted subject, the same may be brought to the notice of the Invigilator concerned.</li>

    <li>Candidates should <strong>not bring any article into the Examination Hall</strong>. He/She should leave books/notes, used test booklets or any other material with the Invigilator in the room.</li>

    <li><strong>Seats indicating Roll Numbers</strong> will be allotted to the candidates and they must occupy their allotted seat only.</li>

    <li>Possession/Use of <strong>CALCULATORS / MOBILE PHONES AND OTHER ELECTRONIC / COMMUNICATION DEVICES</strong> is banned in the examination premises. Candidates are advised in their own interest not to bring any of the banned items to the Examination Centre as arrangement for their safekeeping cannot be assured.</li>

    <li>No candidate shall be permitted to leave the Examination Room/Hall before the end of the examination.</li>

    <li>Upon completion of the examination, please wait for instructions from the Invigilator and do not get up from your seat until advised. The candidates will be permitted to move out one at a time only.</li>

    <li>Your candidature to the examination is <strong>provisional</strong>. If at any stage of recruitment you are found not eligible or barred by 'court of law' or barred by order of any Commission, your candidature shall be summarily rejected.</li>

    <li>If religion/customs require you to wear specific attire, please visit the Centre early for thorough checking and mandatory frisking.</li>

    <li>No candidate will be allowed to enter the Examination Centre without the Admit Card, undertaking, Valid ID Proof, and proper frisking. Frisking through Handheld Metal Detector (HHMD) will be carried out without physical touch.</li>

    <li>Candidates will be permitted to carry only the following items into the examination venue:
        <ol type="a" style="margin: 5px 0 5px 0; padding-left: 18px; font-size: 9px;">
            <li>Personal transparent water bottle,</li>
            <li>Admit Card downloaded from TMB website (clear printout on A4 size paper),</li>
            <li>Two passport-size photographs for attendance sheet,</li>
            <li>Original valid ID proof.</li>
        </ol>
    </li>

    <li>Candidates must carry <strong>one original and valid Photo Identification Proof</strong> issued by the Government –  
        PAN Card / Driving License / Voter ID / Passport / Aadhaar Card (with photograph) / e-Aadhaar.  
        Photocopies, scanned copies, or digital photos of IDs WILL NOT be considered valid.
    </li>

    <li>No vehicle will be allowed to park inside the examination centre. Candidates are advised to make their own parking arrangements.</li>
</ol>

</div>

<div style="margin-top:10px;text-align:center;font-size:9px;color:#666;">
    © Tura Municipal Board — Official Examination Instructions
</div>

</body>
</html>
