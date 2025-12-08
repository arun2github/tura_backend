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
            font-family: "Hind Siliguri", sans-serif;
            margin: 0;
            padding: 18px;
            font-size: 13px;
            line-height: 1.25;
        }

        /* ---------------- HEADER ---------------- */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .government-seal {
            width: 70px;
            height: 70px;
            margin: 0 auto 8px;
        }

        .organization-name {
            font-size: 22px;
            font-weight: 700;
            color: #1a4a72;
        }

        .department-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c5aa0;
            margin-top: 4px;
        }

        .admit-card-title {
            font-size: 20px;
            font-weight: 700;
            color: #d32f2f;
            text-decoration: underline;
            margin-top: 10px;
        }

        /* ---------------- TOP SECTION ---------------- */
        .content-wrapper {
            display: table;
            width: 100%;
        }

        .photo-section {
            display: table-cell;
            width: 130px;
            vertical-align: top;
            padding-right: 12px;
        }

        .photo-frame {
            border: 1px solid #000;
            padding: 4px;
            width: 120px;
            height: 150px;
            background: #fafafa;
            text-align: center;
        }

        .candidate-photo {
            width: 112px;
            height: 140px;
            object-fit: cover;
        }

        .photo-placeholder {
            width: 112px;
            height: 140px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #555;
            border: 1px solid #aaa;
        }

        /* ---------------- DETAILS TABLE ---------------- */
        .details-section {
            display: table-cell;
            vertical-align: top;
            padding-left: 12px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .details-table td {
            padding: 6px 8px;
            border: 1px solid #444;
        }

        .label-cell {
            width: 160px;
            background: #f1f1f1;
            font-weight: 600;
        }

        .value-cell {
            background: #fff;
            font-weight: 400;
        }

        /* ---------------- EXAM DETAILS ---------------- */
        .exam-details {
            margin-top: 16px;
            border: 1px solid #000;
            padding: 12px;
            background: #f7f7f7;
        }

        .exam-details h3 {
            margin: 0 0 10px;
            color: #d32f2f;
            font-size: 16px;
            font-weight: 700;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
        }

        .exam-table td {
            padding: 6px;
            border: 1px solid #444;
            font-size: 12px;
        }

        .exam-label {
            width: 160px;
            font-weight: 600;
            background: #ececec;
        }

        /* ---------------- SIGNATURES ---------------- */
        .signature-section {
            margin-top: 20px;
            display: table;
            width: 100%;
            font-size: 12px;
        }

        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 28px;
            padding-top: 4px;
            font-weight: 600;
        }

        /* ---------------- FOOTER ---------------- */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }

        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 50px;
            color: rgba(200, 0, 0, 0.08);
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
    <div class="government-seal">
        @if($logo_path && file_exists($logo_path))
            <img src="{{ $logo_path }}" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid #1a4a72;">
        @else
            <div style="width:70px;height:70px;border-radius:50%;border:2px solid #1a4a72;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;text-align:center;">
                TURA<br>MUNICIPAL
            </div>
        @endif
    </div>

    <div class="organization-name">Tura Municipal Board</div>
    <div class="department-name">Recruitment Department</div>
    <div class="admit-card-title">ADMIT CARD</div>
    <div style="font-size:15px;font-weight:600;margin-top:6px;">{{ $job_title }}</div>
</div>

<div class="content-wrapper">
    <div class="photo-section">
        <div class="photo-frame">
            @if($photo_base64)
                <img src="data:image/jpeg;base64,{{ $photo_base64 }}" class="candidate-photo">
            @else
                <div class="photo-placeholder">PASTE<br>PHOTO</div>
            @endif
        </div>
    </div>

    <div class="details-section">
        <table class="details-table">
            <tr><td class="label-cell">Candidate Name:</td><td class="value-cell">{{ strtoupper($full_name) }}</td></tr>
            <tr><td class="label-cell">Roll Number:</td><td class="value-cell">{{ $roll_number }}</td></tr>
            <tr><td class="label-cell">Application ID:</td><td class="value-cell">{{ $application_id }}</td></tr>
            <tr><td class="label-cell">Admit Card No:</td><td class="value-cell">{{ $admit_no }}</td></tr>
            @if($date_of_birth)<tr><td class="label-cell">Date of Birth:</td><td>{{ $date_of_birth }}</td></tr>@endif
            @if($gender)<tr><td class="label-cell">Gender:</td><td>{{ strtoupper($gender) }}</td></tr>@endif
            @if($category)<tr><td class="label-cell">Category:</td><td>{{ strtoupper($category) }}</td></tr>@endif
            <tr><td class="label-cell">Examination Date:</td><td>{{ $exam_date }}</td></tr>
            <tr><td class="label-cell">Reporting Time:</td><td>{{ $reporting_time }}</td></tr>
            <tr><td class="label-cell">Exam Time:</td><td>{{ $exam_time }}</td></tr>
        </table>
    </div>
</div>

<div class="exam-details">
    <h3>EXAMINATION VENUE</h3>
    <table class="exam-table">
        <tr><td class="exam-label">Exam Center:</td><td>{{ $venue_name }}</td></tr>
        <tr><td class="exam-label">Address:</td><td>{{ $venue_address }}</td></tr>
    </table>
</div>

<div class="signature-section">
    <div class="signature-left">
        <div class="signature-line">Candidate's Signature</div>
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

<div style="text-align:center;margin-bottom:15px;">
    <h2 style="margin:0;font-size:20px;font-weight:700;color:#1a4a72;">TURA MUNICIPAL BOARD</h2>
    <p style="margin:5px 0;font-size:14px;color:#555;">West Garo Hills, Meghalaya</p>
    <h3 style="margin:8px 0;font-size:18px;color:#d97706;">INSTRUCTIONS TO CANDIDATES</h3>
</div>

<div style="font-size:12px;line-height:1.45;">
   <ol style="padding-left: 18px; margin: 0; font-size: 12px; line-height: 1.45;">
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
        <ol type="a" style="margin: 8px 0 8px 0; padding-left: 22px; font-size: 11px;">
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

<div style="margin-top:18px;text-align:center;font-size:11px;color:#666;">
    © Tura Municipal Board — Official Examination Instructions
</div>

</body>
</html>
