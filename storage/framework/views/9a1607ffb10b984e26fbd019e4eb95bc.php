<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pet Dog Registration Certificate</title>
        <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<?php
    use Illuminate\Support\Facades\Log;
?>

    <style>
        
        body {
            font-family: 'Hind Siliguri', Arial, sans-serif;
            margin: 0;
            padding: 5px;
            background: #fff;
            color: #000;
            font-size: 10px;
            line-height: 1.2;
        }
        
        .certificate-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10px;
            background: #fff;
            box-sizing: border-box;
            border: 2px solid #000;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .header-flex {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .header-left {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
            text-align: left;
        }
        
        .header-center {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
            text-align: center;
        }
        
        .header-right {
            display: table-cell;
            width: 20%;
            vertical-align: middle;
            text-align: right;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        
        .header-text h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .header-text p {
            font-size: 10px;
            margin: 1px 0;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .certificate-title {
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0;
            text-transform: uppercase;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .reg-number-date {
            display: table;
            width: 100%;
            margin: 8px 0;
            font-size: 10px;
            font-weight: bold;
            font-family: 'Hind Siliguri', Arial, sans-serif;
            border-collapse: collapse;
        }
        
        .reg-number-date .reg-cell {
            display: table-cell;
            width: 33.33%;
            padding: 3px;
            text-align: center;
            vertical-align: middle;
        }
        
        .reg-number-date .reg-cell:first-child {
            text-align: left;
        }
        
        .reg-number-date .reg-cell:last-child {
            text-align: right;
        }
        
        .description {
            font-size: 9px;
            text-align: justify;
            margin: 8px 0;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
            font-size: 10px;
            border-collapse: collapse;
        }
        
        .info-item {
            display: table-cell;
            width: 50%;
            padding: 2px 3px;
            vertical-align: top;
        }
        
        .info-item.full-width {
            width: 100%;
            display: table-cell;
        }
        
        .field-label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .field-value {
            display: inline-block;
            min-width: 100px;
            padding-left: 5px;
            margin-left: 5px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .photo-section {
            text-align: center;
            margin: 12px 0;
        }
        
        .photo-container {
            text-align: center;
        }
        
        .photo-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .photo-frame {
            width: 70px;
            height: 70px;
            border: 1px solid #000;
            display: inline-block;
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 8px;
            color: #666;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .non-transferable {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin: 10px 0;
            padding: 6px;
            border: 1px solid #000;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .disclaimer {
            font-size: 8px;
            text-align: justify;
            margin: 10px 0;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .signature-section {
            text-align: right;
            margin: 15px 0;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        
        .signature-image {
            width: 100px;
            height: 50px;
            margin-bottom: 3px;
            object-fit: contain;
        }
        
        .signature-line {
            width: 150px;
            height: 1px;
            border-bottom: 1px solid #000;
            margin: 0 0 3px auto;
        }
        
        .signature-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 1px;
        }
        
        .signature-subtitle {
            font-size: 9px;
            margin-bottom: 5px;
        }
        
        .footer-contact {
            font-size: 8px;
            text-align: center;
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-flex">
                <div class="header-left">
                    <?php
                        $defaultLogoPath = public_path('images/email/logo.png');
                    ?>
                    <?php if(file_exists($defaultLogoPath)): ?>
                        <img src="<?php echo e(asset('images/email/logo.png')); ?>" class="logo" alt="Logo">
                    <?php else: ?>
                        <div class="logo" style="border:1px solid #000; display:flex; align-items:center; justify-content:center; font-size:6px; font-weight:bold; width:50px; height:50px;">LOGO</div>
                    <?php endif; ?>
                </div>
                <div class="header-center">
                    <div class="header-text">
                        <h1>TURA MUNICIPAL BOARD</h1>
                        <p>WEST GARO HILLS, Meghalaya</p>
                        <p>Established: 12-09-1979</p>
                    </div>
                </div>
                <div class="header-right">
                    <!-- Empty right column -->
                </div>
            </div>
            <div class="certificate-title">CERTIFICATE OF REGISTRATION OF PET DOG UNDER SECTION 128<br>OF MEGHALAYA MUNICIPAL ACT, 1973</div>
        </div>

        <!-- Registration Number and Date -->
        <div class="reg-number-date">
            <div class="reg-cell">PET REGISTRATION NO <?php echo e($registration_number ?? ''); ?></div>
            <div class="reg-cell">PET TAG NO <?php echo e($pet_tag_number ?? ''); ?></div>
            <div class="reg-cell">DATE OF REGISTRATION <?php echo e(isset($registration_date) && $registration_date ? date('d/m/Y', strtotime($registration_date)) : date('d/m/Y')); ?></div>
        </div>

        <div class="description">
            This registration certificate is granted in pursuance to the municipal regulations for pet registration and public health safety. It is valid only for the particulars specified herein subject to conditions stated below.
        </div>

        <!-- Particulars of Applicant -->
        <div class="section-title">PARTICULARS OF APPLICANT</div>
        
        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Owner Name</span>
                <span class="field-value"><?php echo e(isset($owner_name) && $owner_name ? strtoupper($owner_name) : ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Contact Number</span>
                <span class="field-value"><?php echo e($owner_phone ?? ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Email Address</span>
                <span class="field-value"><?php echo e($owner_email ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Document ID</span>
                <span class="field-value"><?php echo e($owner_aadhar_number ?? ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item full-width" style="width: 100%;">
                <span class="field-label">Residential Address</span>
                <span class="field-value" style="min-width: 300px;"><?php echo e($owner_address ?? ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Ward No</span>
                <span class="field-value"><?php echo e($ward_no ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">District</span>
                <span class="field-value"><?php echo e($district ?? 'West Garo Hills'); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Pincode</span>
                <span class="field-value"><?php echo e($pincode ?? ''); ?></span>
            </div>
            <div class="info-item"></div>
        </div>

        <!-- Particulars of Pet -->
        <div class="section-title">PARTICULARS OF PET</div>
        
        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Name of Dog</span>
                <span class="field-value"><?php echo e(isset($dog_name) && $dog_name ? strtoupper($dog_name) : ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Gender of Dog</span>
                <span class="field-value"><?php echo e(isset($dog_gender) && $dog_gender ? ucfirst($dog_gender) : ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Breed of Dog</span>
                <span class="field-value"><?php echo e($dog_breed ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Age at Registration</span>
                <span class="field-value"><?php echo e(isset($dog_age) && $dog_age ? $dog_age . ' ' . ($dog_age_unit ?? 'year(s)') : ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Color</span>
                <span class="field-value"><?php echo e($dog_color ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Weight</span>
                <span class="field-value"><?php echo e(isset($dog_weight) && $dog_weight ? $dog_weight . ' kg' : ''); ?></span>
            </div>
        </div>

        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Vaccination Status</span>
                <span class="field-value"><?php echo e(isset($vaccination_status) && $vaccination_status ? ucfirst($vaccination_status) : ''); ?></span>
            </div>
            <div class="info-item"></div>
        </div>

        <!-- Dog Photo -->
        <div class="photo-section">
            <div class="photo-container">
                <div class="photo-title">DOG'S PHOTO</div>
                <div class="photo-frame">
                    <?php if(isset($pet_photo_base64) && $pet_photo_base64): ?>
                        <img src="data:image/jpeg;base64,<?php echo e($pet_photo_base64); ?>" class="photo" alt="Dog Photo">
                    <?php elseif(isset($pet_photo_path) && $pet_photo_path): ?>
                        <?php
                            // Try different path combinations for debugging
                            $storagePath = storage_path('app/public/' . $pet_photo_path);
                            $publicPath = public_path('storage/' . $pet_photo_path);
                            $directPath = public_path($pet_photo_path);
                            
                            Log::info('Pet Photo Debug:', [
                                'original_path' => $pet_photo_path,
                                'storage_path' => $storagePath,
                                'storage_exists' => file_exists($storagePath),
                                'public_path' => $publicPath, 
                                'public_exists' => file_exists($publicPath),
                                'direct_path' => $directPath,
                                'direct_exists' => file_exists($directPath)
                            ]);
                            
                            // Use the path that exists
                            $finalPath = null;
                            if (file_exists($storagePath)) {
                                $finalPath = $storagePath;
                            } elseif (file_exists($publicPath)) {
                                $finalPath = $publicPath;
                            } elseif (file_exists($directPath)) {
                                $finalPath = $directPath;
                            }
                            
                            Log::info('Final path chosen: ' . ($finalPath ?? 'NONE'));
                        ?>
                        
                        <?php if($finalPath): ?>
                            <img src="<?php echo e($finalPath); ?>" class="photo" alt="Dog Photo">
                        <?php else: ?>
                            <div class="no-photo">Photo Not Found<br><small style="font-size:6px;"><?php echo e($pet_photo_path); ?></small></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-photo">No Photo</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Non Transferable -->
        <div class="non-transferable"> TRANSFERABLE / FEE NOT REFUNDABLE</div>

        <!-- Fee Details -->
        <div class="info-row">
            <div class="info-item">
                <span class="field-label">Fees Paid in Rupees</span>
                <span class="field-value"><?php echo e(isset($total_fee) && $total_fee ? $total_fee : '250'); ?></span>
            </div>
            <div class="info-item">
                <span class="field-label">Registration Certificate Valid Upto</span>
                <span class="field-value"><?php echo e(isset($registration_date) && $registration_date ? date('d/m/Y', strtotime($registration_date . ' +1 year')) : date('d/m/Y', strtotime('+1 year'))); ?></span>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="disclaimer">
            <strong>DISCLAIMER:</strong> This registration is purely on the basis of self-certification. If any of the statement of undertaking-self declaration are found otherwise the subsequently registration is liable to be cancelled-revoked for all intents and purposes forthwith and such person shall be liable for legal and penal action for obtaining registration fraudulently by making false averments.
        </div>

        <!-- CEO Signature -->
        <div class="signature-section">
            <?php
                $ceoSignPath = public_path('images/email/CEO_SIGN.png');
            ?>
            <?php if(file_exists($ceoSignPath)): ?>
                <img src="<?php echo e(asset('images/email/CEO_SIGN.png')); ?>" class="signature-image" alt="CEO Signature">
            <?php else: ?>
                <div class="signature-line"></div>
            <?php endif; ?>
            <div class="signature-title">Chief Executive Officer</div>
            <div class="signature-subtitle">Tura Municipal Board</div>
        </div>

        <!-- Footer Contact -->
        <div class="footer-contact">
            Chief Executive Officer, Tura Municipal Board, Chandmary, Tura, West Garo Hills District, Pin 794002.<br>
            Tel: 03651-350052, Email: ceotmb@ymail.com, Website: turamunicipalboard.com
        </div>
    </div>
</body>
</html><?php /**PATH /Users/Prem/tura_backend/tura_backend/resources/views/pdf/pet_dog_certificate.blade.php ENDPATH**/ ?>