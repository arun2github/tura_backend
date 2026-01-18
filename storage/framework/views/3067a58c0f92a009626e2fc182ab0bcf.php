<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admit Card - <?php echo e($roll_number); ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Hind Siliguri", "Times New Roman", serif;
            margin: 0;
            padding: 12px;
            font-size: 12px;
            line-height: 1.4;
            color: #1a1a1a;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f1f3f4 100%);
            letter-spacing: 0.2px;
        }

        /* ---------------- HEADER ---------------- */
        .header {
            background: linear-gradient(135deg, #f0fffe 0%, #ffffff 50%, #e6fffd 100%);
            padding: 12px;
            margin-bottom: 18px;
            display: table;
            width: 100%;
            border-radius: 4px;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
            padding: 4px;
        }

        .header-left {
            width: 25%;
            text-align: left;
        }

        .header-center {
            width: 50%;
            text-align: center;
        }

        .header-right {
            width: 25%;
            text-align: right;
        }

        .government-seal {
            width: 60px;
            height: 60px;
            margin: 0;
        }

        .government-seal img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .logo-placeholder {
            width: 65px;
            height: 65px;
            border: 3px solid #2D8681;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #e6fffd 0%, #ffffff 100%);
            box-shadow: 0 2px 8px rgba(45, 134, 129, 0.2);
        }

        .organization-name {
            font-size: 18px;
            font-weight: 800;
            color: #2D8681;
            margin-bottom: 2px;
            line-height: 1.0;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            text-shadow: 0 2px 4px rgba(45, 134, 129, 0.3);
        }

        .department-name {
            font-size: 12px;
            font-weight: 700;
            color: #2D8681;
            margin: 1px 0;
            line-height: 1.0;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .admit-card-title {
            font-size: 17px;
            font-weight: 800;
            color: #2D8681;
            text-decoration: none;
            border-bottom: 2px solid #2D8681;
            margin: 3px 0 2px 0;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            display: inline-block;
            padding-bottom: 2px;
            text-shadow: 0 2px 4px rgba(45, 134, 129, 0.2);
        }

        .job-title {
            font-size: 12px;
            font-weight: 700;
            color: #2D8681;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .candidate-photo-header {
            width: 80px;
            height: 90px;
            border: 2px solid #000;
            padding: 2px;
            background: #fff;
            text-align: center;
            margin-left: auto;
        }

        .candidate-photo-header img {
            width: 76px;
            height: 86px;
            object-fit: cover;
        }

        .photo-placeholder-header {
            width: 76px;
            height: 86px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #000;
            border: 1px solid #000;
        }

        .signature-box {
            margin-top: 4px;
            padding: 4px;
            border: 1px solid #000;
            height: 25px;
            background: #fff;
            font-size: 9px;
            font-weight: 600;
            text-align: center;
            line-height: 1.1;
        }

        /* ---------------- DETAILS SECTION ---------------- */
        .details-section {
            margin-top: 16px;
        }

        .main-content {
            display: table;
            width: 100%;
            margin-bottom: 18px;
            border: 1px solid #2D8681;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(45, 134, 129, 0.08);
            background: #ffffff;
        }

        .left-column {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding: 15px;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);
            border-right: 2px solid #2D8681;
            min-height: 380px;
        }

        .right-column {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            padding: 15px;
            text-align: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8fffe 100%);
            min-height: 380px;
            border-left: 1px solid #2D8681;
        }

        .photo-container {
            margin-bottom: 12px;
        }

        .db-photo-box {
            border: 2px solid #2D8681;
            padding: 4px;
            background: #f9f9f9;
            text-align: center;
            margin-bottom: 8px;
            height: 100px;
            width: 100px;
            margin-left: auto;
            margin-right: auto;
        }

        .paste-photo-box {
            border: 2px dashed #000;
            padding: 4px;
            background: #fff;
            text-align: center;
            height: 100px;
            width: 100px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 600;
            color: #666;
        }

        .db-photo-box img {
            max-width: 92px;
            max-height: 92px;
            object-fit: cover;
        }

        .enhanced-exam-section {
            background: #ffffff;
            border: 2px solid #2D8681;
            border-radius: 8px;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(45, 134, 129, 0.12);
            overflow: hidden;
        }

        .enhanced-exam-header {
            background: linear-gradient(135deg, #2D8681 0%, #238a85 100%);
            color: white;
            font-weight: 700;
            text-align: center;
            padding: 15px 20px;
            margin: 0;
            font-size: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .exam-row-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8fffe 100%);
            padding: 8px 35px 15px 35px;
            margin: 0 15px;
            border-radius: 0;
            border: none;
            font-size: 13px;
            line-height: 1.7;
            border-top: 1px solid #e6fffd;
        }

        .exam-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
            white-space: nowrap;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .exam-item strong {
            color: #2D8681;
            font-weight: 800;
            text-shadow: 0 1px 2px rgba(45, 134, 129, 0.2);
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            background: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .details-table td {
            padding: 8px 10px;
            border: 1px solid #2D8681;
            font-weight: 500;
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
            background: #f9f9f9;
        }

        .exam-details h3 {
            margin: 0 0 10px;
            color: #2D8681;
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
            margin-top: 25px;
            display: table;
            width: 100%;
            font-size: 12px;
            border-top: 2px solid #2D8681;
            padding-top: 15px;
        }

        .signature-left {
            display: table-cell;
            width: 70%;
            text-align: left;
            padding-left: 20px;
            vertical-align: top;
        }

        .signature-right {
            display: table-cell;
            width: 30%;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-top: 2px solid #2D8681;
            margin-top: 35px;
            padding-top: 8px;
            font-weight: 600;
            color: #2D8681;
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
    <!-- Left Column - Logo -->
    <div class="header-left">
        <div class="government-seal">
            <?php if(file_exists(public_path('images/email/logo.png'))): ?>
                <img src="<?php echo e(public_path('images/email/logo.png')); ?>" class="government-seal">
            <?php else: ?>
                <div class="logo-placeholder">
                    TURA<br>MUNICIPAL
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Center Column - Admit Card Title -->
    <div class="header-center">
        <div class="organization-name">Tura Municipal Board</div>
        <div class="department-name">West Garo Hills, Meghalaya</div>
        <div class="admit-card-title">ADMIT CARD</div>
        <div class="job-title"><?php echo e($job_title); ?></div>
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
            <tr><td class="label-cell">Candidate Name:</td><td class="value-cell"><?php echo e(strtoupper($full_name)); ?></td></tr>
            <?php if($date_of_birth): ?><tr><td class="label-cell">Date of Birth:</td><td class="value-cell"><?php echo e($date_of_birth); ?></td></tr><?php endif; ?>
            <?php if($gender): ?><tr><td class="label-cell">Gender:</td><td class="value-cell"><?php echo e(strtoupper($gender)); ?></td></tr><?php endif; ?>
            <?php if($category): ?><tr><td class="label-cell">Category:</td><td class="value-cell"><?php echo e(strtoupper($category)); ?></td></tr><?php endif; ?>
            <?php if($email): ?><tr><td class="label-cell">Email:</td><td class="value-cell"><?php echo e($email); ?></td></tr><?php endif; ?>
        </table>
    </div>

    <!-- Right Column - Photo Sections -->
    <div class="right-column">
        <div class="photo-container">
            <div class="db-photo-box">
                <?php if($photo_base64): ?>
                    <img src="data:image/jpeg;base64,<?php echo e($photo_base64); ?>">
                <?php else: ?>
                    <div style="height: 92px; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #ccc;">
                        <div style="border: 1px dashed #ccc; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; background: #f5f5f5;">
                            📷
                        </div>
                    </div>
                <?php endif; ?>
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
<?php if(isset($is_consolidated) && $is_consolidated && isset($consolidated_schedule)): ?>
    <!-- CONSOLIDATED EXAM SCHEDULE -->
    <div class="enhanced-exam-section">
        <div class="enhanced-exam-header">EXAMINATION SCHEDULE</div>
        
        <?php if(isset($consolidated_schedule['has_conflicts']) && $consolidated_schedule['has_conflicts']): ?>
            <!-- TIME CONFLICT WARNING -->
            <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 4px; padding: 8px; margin-bottom: 12px;">
                <div style="color: #856404; font-weight: 700; font-size: 12px; margin-bottom: 4px;">⚠️ TIME CONFLICT DETECTED</div>
                <div style="color: #856404; font-size: 10px; line-height: 1.3;">
                    Some exam times overlap. Please contact the examination authority immediately.
                </div>
            </div>
        <?php endif; ?>
        
        <?php $__currentLoopData = $consolidated_schedule['papers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $paper): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="margin-bottom: <?php echo e($index < count($consolidated_schedule['papers']) - 1 ? '10px' : '0'); ?>; border: 2px solid #2D8681; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 8px rgba(45, 134, 129, 0.12); background: #ffffff;">
                <div style="background: linear-gradient(135deg, #2D8681 0%, #238a85 100%); color: white; font-weight: 700; text-align: center; padding: 8px 15px; font-size: 15px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); letter-spacing: 1.5px; text-transform: uppercase;">
                    <?php echo e(strtoupper($paper['paper_type'])); ?> 
                    <?php if(isset($paper['job_title']) && !$paper['is_common_paper']): ?>
                        - <?php echo e(strtoupper($paper['job_title'])); ?>

                    <?php endif; ?>
                </div>
                <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fffe 100%); padding: 8px 35px 15px 35px; margin: 0 15px; border: none; font-size: 13px; line-height: 1.7; border-top: 1px solid #e6fffd;">
                    <?php if(isset($paper['job_title']) && !$paper['is_common_paper']): ?>
                        <div style="color: #2D8681; font-weight: 700; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Post: <?php echo e($paper['job_title']); ?></div>
                    <?php endif; ?>
                    <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Subject:</strong> <?php echo e(strtoupper($paper['subject'])); ?></div>
                    <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Roll No:</strong> <span style="color: #2D8681; font-weight: 700;"><?php echo e($paper['roll_number']); ?></span></div>
                    <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Date:</strong> <?php echo e(date('d-m-Y', strtotime($paper['exam_date']))); ?></div>
                    <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Reporting:</strong> <?php echo e(date('h:i A', strtotime($paper['reporting_time']))); ?></div>
                    <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Exam Time:</strong> <?php echo e($paper['exam_time']); ?></div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php else: ?>
    <!-- INDIVIDUAL ADMIT CARD EXAM SCHEDULE -->
    <?php if($has_slot_1 || $has_slot_2): ?>
    <div class="enhanced-exam-section">
        <div class="enhanced-exam-header">EXAMINATION SCHEDULE</div>
        
        <?php if($has_slot_1): ?>
        <div style="margin-bottom: 6px;">
            <div style="background: linear-gradient(135deg, #2D8681 0%, #238a85 100%); color: white; font-weight: 700; text-align: center; padding: 6px; font-size: 13px; border-radius: 4px; margin-bottom: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); letter-spacing: 1.2px;">PAPER 1</div>
            <div class="exam-row-content">
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Subject:</strong> <?php echo e(strtoupper($subject_slot_1)); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Date:</strong> <?php echo e($exam_date_slot_1); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Reporting:</strong> <?php echo e($reporting_time_slot_1); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Exam Time:</strong> <?php echo e($exam_time_slot_1); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($has_slot_2): ?>
        <div>
            <div style="background: linear-gradient(135deg, #2D8681 0%, #238a85 100%); color: white; font-weight: 700; text-align: center; padding: 6px; font-size: 13px; border-radius: 4px; margin-bottom: 2px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); letter-spacing: 1.2px;">PAPER 2</div>
            <div class="exam-row-content">
                <?php if(isset($job_title)): ?>
                    <div style="color: #2D8681; font-weight: 700; font-size: 14px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Post: <?php echo e($job_title); ?></div>
                <?php endif; ?>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Subject:</strong> <?php echo e(strtoupper($subject_slot_2)); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Date:</strong> <?php echo e($exam_date_slot_2); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Reporting:</strong> <?php echo e($reporting_time_slot_2); ?></div>
                <div style="display: inline-block; margin-right: 30px; margin-bottom: 10px; white-space: nowrap; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;"><strong>Exam Time:</strong> <?php echo e($exam_time_slot_2); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="exam-details">
    <h3>EXAMINATION VENUE</h3>
    <table class="exam-table">
        <tr><td class="exam-label">Exam Center:</td><td><?php echo e($venue_name); ?></td></tr>
        <tr><td class="exam-label">Address:</td><td><?php echo e($venue_address); ?></td></tr>
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
    <div>Downloaded on: <?php echo e(date('d-m-Y H:i:s')); ?></div>
</div>

<!-- ================= PAGE 2 (INSTRUCTIONS) ================= -->
<div class="page-break"></div>

<div style="text-align:center;margin-bottom:15px;">
    <h2 style="margin:0;font-size:20px;font-weight:700;color:#2D8681;">TURA MUNICIPAL BOARD</h2>
    <p style="margin:5px 0;font-size:14px;color:#000;">West Garo Hills, Meghalaya</p>
    <h3 style="margin:8px 0;font-size:18px;color:#000;">INSTRUCTIONS TO CANDIDATES</h3>
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
<?php /**PATH /Users/Prem/tura_backend/tura_backend/resources/views/pdf/admit_card.blade.php ENDPATH**/ ?>