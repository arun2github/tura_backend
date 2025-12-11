
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pet Dog Registration Certificate - <?php echo e($registration_number); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
                .certificate-flex-row {
                    display: flex;
                    gap: 32px;
                    align-items: center;
                    margin-bottom: 2px;
                }
                .certificate-flex-row .label,
                .certificate-flex-row .value {
                    margin-right: 8px;
                }
        body {
            font-family: 'Hind Siliguri', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #2D8681;
        }
        .main-border {
            border: 6px solid #2D8681;
            padding: 12px 18px;
            margin: 10px auto;
            max-width: 750px;
            background: #fff;
            box-shadow: 0 4px 16px rgba(45,134,129,0.08);
            border-radius: 14px;
        }
        .header-flex {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            border-bottom: 2px solid #2D8681;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 18px;
        }
        .header-center {
            flex: 1;
            text-align: center;
        }
        .header-title {
            font-size: 22px;
            font-weight: 700;
            color: #2D8681;
            font-family: 'Hind Siliguri', Arial, sans-serif;
            letter-spacing: 1px;
        }
        .header-address {
            font-size: 13px;
            color: #222;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .header-dept {
            font-size: 18px;
                    color: #222;
            font-weight: 600;
            margin-top: 5px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
                    color: #222;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            margin: 24px 0 12px 0;
            color: #2D8681;
                    color: #222;
            letter-spacing: 1px;
        }
        .certificate-table {
            width: 100%;
            border-collapse: collapse;
                    color: #2D8681;
        }
        .certificate-table td {
            padding: 7px 12px;
            font-size: 15px;
            color: #222;
        }
        .label {
            font-weight: 600;
            color: #2D8681;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .value {
            color: #222;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
                    color: #2D8681;
            margin-top: 8px;
            margin-bottom: 8px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
                    color: #2D8681;
            padding: 6px 12px;
            font-size: 15px;
            color: #2D8681;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .photo-col {
                    color: #2D8681;
            text-align: center;
        }
        .photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
                    color: #222;
            border-radius: 12px;
            margin-bottom: 5px;
            background: #f5f7fa;
        }
        .disclaimer {
            font-size: 13px;
            color: #2D8681;
                    color: #222;
            border-top: 1px solid #2D8681;
            padding-top: 12px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .footer-note {
            font-size: 13px;
            color: #2D8681;
            text-align: center;
            margin-top: 12px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        .non-transfer {
            background: #e6f7f5;
            color: #2D8681;
            font-weight: bold;
            text-align: center;
            padding: 7px;
            border: 1px solid #2D8681;
            margin: 12px 0;
            border-radius: 8px;
            font-family: 'Hind Siliguri', Arial, sans-serif;
        }
        /* Unique accent for headings */
        .accent {
            color: #fff;
            background: #2D8681;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="main-border">
        <div class="header-flex">
            <?php
                $defaultLogoPath = public_path('images/email/logo.png');
            ?>
            <?php if(file_exists($defaultLogoPath)): ?>
                <img src="<?php echo e(asset('images/email/logo.png')); ?>" class="header-logo" alt="Tura Municipal Board Logo">
            <?php else: ?>
                <div class="header-logo" style="display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;text-align:center;background:#2056a0;color:#fff;">TURA<br>MUNICIPAL</div>
            <?php endif; ?>
            <div class="header-center">
                <div class="header-title">TURA MUNICIPAL BOARD</div>
                <div class="header-address">West Garo Hills, Meghalaya<br>Established: 12-09-1979</div>
                <div class="certificate-title">CERTIFICATE OF REGISTRATION OF PET DOG</div>
            </div>
        </div>
        <table class="certificate-table">
           
            <tr>
                <td colspan="4" style="padding:0;border:none;">
                    <div class="certificate-flex-row">
                        <span class="label">PET REGISTRATION NO</span>
                        <span class="value"><?php echo e($registration_number ?? 'NA'); ?></span>
                        <span class="label">DATE OF REGISTRATION</span>
                        <span class="value"><?php echo e(isset($registration_date) && $registration_date ? date('d/m/Y', strtotime($registration_date)) : 'NA'); ?></span>
                    </div>
                </td>
            </tr>
           
        </table>
        <div style="font-size:13px;color:#333;margin-bottom:5px;">
            This registration certificate is granted in pursuance to the municipal regulations for pet registration and public health safety. It is valid only for the particulars specified herein subject to conditions stated below.
        </div>
        <div class="section-title">PARTICULARS OF APPLICANT</div>
        <table class="details-table">
            <tr>
                <td class="label">Owner Name</td>
                <td class="value"><?php echo e(isset($owner_name) && $owner_name ? strtoupper($owner_name) : 'NA'); ?></td>
                <td class="label">Contact Number</td>
                <td class="value"><?php echo e($owner_phone ?? 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Email Address</td>
                <td class="value"><?php echo e($owner_email ?? 'NA'); ?></td>
                <td class="label">Document ID</td>
                <td class="value"><?php echo e($owner_aadhar_number ?? 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Residential Address</td>
                <td class="value" colspan="3"><?php echo e($owner_address ?? 'NA'); ?></td>
            </tr>
        </table>
        <div class="section-title">PARTICULARS OF PET</div>
        <table class="details-table">
            <tr>
                <td class="label">Name of Dog</td>
                <td class="value"><?php echo e(isset($dog_name) && $dog_name ? strtoupper($dog_name) : 'NA'); ?></td>
                <td class="label">Gender of Dog</td>
                <td class="value"><?php echo e(isset($dog_gender) && $dog_gender ? ucfirst($dog_gender) : 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Breed of Dog</td>
                <td class="value"><?php echo e($dog_breed ?? 'NA'); ?></td>
                <td class="label">Age at Registration</td>
                <td class="value"><?php echo e(isset($dog_age) && $dog_age ? $dog_age . ' year(s)' : 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Color</td>
                <td class="value"><?php echo e($dog_color ?? 'NA'); ?></td>
                <td class="label">Weight</td>
                <td class="value"><?php echo e(isset($dog_weight) && $dog_weight ? $dog_weight . ' kg' : 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Veterinarian</td>
                <td class="value"><?php echo e($veterinarian_name ?? 'NA'); ?></td>
                <td class="label">Vet License No.</td>
                <td class="value"><?php echo e($veterinarian_license ?? 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">Date of ARV Vaccination</td>
                <td class="value"><?php echo e(isset($vaccination_date) && $vaccination_date ? date('d/m/Y', strtotime($vaccination_date)) : 'NA'); ?></td>
                <td class="label">Vaccination Status</td>
                <td class="value"><?php echo e(isset($vaccination_status) && $vaccination_status ? ucfirst($vaccination_status) : 'NA'); ?></td>
            </tr>
        </table>
        <table style="width:100%;margin-top:10px;">
            <tr>
                <td class="photo-col">
                    <div class="accent">DOG'S PHOTO</div>
                    <?php if(isset($dog_photo_path) && file_exists($dog_photo_path)): ?>
                        <img src="<?php echo e($dog_photo_path); ?>" class="photo" alt="Dog Photo">
                    <?php else: ?>
                        <div class="photo" style="display:flex;align-items:center;justify-content:center;">No Photo</div>
                    <?php endif; ?>
                </td>
                <td class="photo-col">
                    <div class="accent">OWNER'S PHOTO</div>
                    <?php if(isset($owner_photo_path) && file_exists($owner_photo_path)): ?>
                        <img src="<?php echo e($owner_photo_path); ?>" class="photo" alt="Owner Photo">
                    <?php else: ?>
                        <div class="photo" style="display:flex;align-items:center;justify-content:center;">No Photo</div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <div class="non-transfer">NON TRANSFERABLE / FEE NOT REFUNDABLE</div>
        <table class="details-table">
            <tr>
                <td class="label">Fees Paid in Rupees</td>
                <td class="value"><?php echo e(isset($fee_paid) && $fee_paid ? $fee_paid : 'NA'); ?></td>
                <td class="label">Payment Receipt Number</td>
                <td class="value"><?php echo e($payment_receipt_number ?? 'NA'); ?></td>
            </tr>
            <tr>
                <td class="label">ARV Certificate Issue Date</td>
                <td class="value"><?php echo e(isset($arv_issue_date) && $arv_issue_date ? date('d/m/Y', strtotime($arv_issue_date)) : 'NA'); ?></td>
                <td class="label">Registration Certificate Valid Upto</td>
                <td class="value"><?php echo e(isset($valid_upto) && $valid_upto ? date('d/m/Y', strtotime($valid_upto)) : 'NA'); ?></td>
            </tr>
        </table>
        <div class="footer-note">This is System Generated document and does not need any signature.</div>
        <div class="disclaimer">
            <strong>DISCLAIMER:</strong> This registration is purely on the basis of self-certification. If any of the statement of undertaking-self declaration are found otherwise the subsequently registration is liable to be cancelled-revoked for all intents and purposes forthwith and such person shall be liable for legal and penal action for obtaining registration fraudulently by making false averments.
        </div>
        <div class="footer-note">Certificate ID: <?php echo e($registration_number); ?> | Generated on: <?php echo e(date('d-m-Y H:i:s')); ?></div>
    </div><?php /**PATH C:\Users\imaru\Desktop\dev\laravel_turamunicipal\resources\views/pdf/pet_dog_certificate.blade.php ENDPATH**/ ?>