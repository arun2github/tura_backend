<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pet Dog Registration Certificate - {{ $registration_number }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: "Open Sans", sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #2c3e50;
        }

        .certificate-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 8px solid #2c5aa0;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .certificate-border {
            border: 3px solid #d4af37;
            margin: 15px;
            border-radius: 10px;
            position: relative;
        }

        .decorative-corner {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 3px solid #d4af37;
        }
        
        .corner-tl { top: -3px; left: -3px; border-right: none; border-bottom: none; }
        .corner-tr { top: -3px; right: -3px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: -3px; left: -3px; border-right: none; border-top: none; }
        .corner-br { bottom: -3px; right: -3px; border-left: none; border-top: none; }

        /* Header Section */
        .certificate-header {
            text-align: center;
            padding: 30px 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
        }

        .logo-section {
            margin-bottom: 15px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .municipality-name {
            font-family: "Merriweather", serif;
            font-size: 28px;
            font-weight: 900;
            margin: 10px 0 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .municipality-subtitle {
            font-size: 16px;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .established-date {
            font-size: 14px;
            font-weight: 300;
            opacity: 0.8;
        }

        /* Certificate Title */
        .certificate-title {
            text-align: center;
            padding: 25px 40px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .title-main {
            font-family: "Merriweather", serif;
            font-size: 32px;
            font-weight: 700;
            color: #2c5aa0;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .title-subtitle {
            font-size: 18px;
            color: #6c757d;
            margin: 8px 0 0;
            font-style: italic;
        }

        /* Content Section */
        .certificate-content {
            padding: 40px;
            line-height: 1.8;
        }

        .registration-intro {
            text-align: center;
            font-size: 18px;
            margin-bottom: 30px;
            color: #495057;
            font-style: italic;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
            margin: 30px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #2c5aa0;
            min-width: 140px;
            font-size: 14px;
        }

        .detail-value {
            font-weight: 400;
            color: #2c3e50;
            font-size: 14px;
            flex: 1;
        }

        .detail-separator {
            margin: 0 10px;
            color: #d4af37;
            font-weight: bold;
        }

        /* Special Sections */
        .registration-number {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .reg-number-label {
            font-size: 14px;
            font-weight: 300;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .reg-number-value {
            font-family: "Merriweather", serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .metal-tag-section {
            background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }

        .metal-tag-number {
            font-family: "Merriweather", serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        /* Validation Section */
        .validation-section {
            margin-top: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #28a745;
        }

        .validation-text {
            font-size: 16px;
            text-align: center;
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .vaccination-status {
            display: inline-block;
            padding: 8px 20px;
            background: #28a745;
            color: white;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .vaccination-status.pending {
            background: #ffc107;
            color: #212529;
        }

        /* Footer Section */
        .certificate-footer {
            padding: 30px 40px;
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 40px;
        }

        .signature-block {
            text-align: center;
        }

        .signature-line {
            border-bottom: 2px solid #2c5aa0;
            margin-bottom: 10px;
            height: 40px;
        }

        .signature-title {
            font-weight: 600;
            color: #2c5aa0;
            font-size: 14px;
        }

        .signature-name {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Issue Date */
        .issue-date {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: white;
            border: 2px dashed #d4af37;
            border-radius: 8px;
        }

        .issue-date-label {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .issue-date-value {
            font-size: 16px;
            font-weight: 700;
            color: #2c5aa0;
            margin-top: 5px;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 60px;
            color: rgba(44, 90, 160, 0.05);
            font-weight: 900;
            z-index: 1;
            pointer-events: none;
            font-family: "Merriweather", serif;
        }

        /* QR Code Section */
        .qr-section {
            position: absolute;
            bottom: 20px;
            right: 20px;
            text-align: center;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border: 2px solid #d4af37;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6c757d;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .certificate-container {
                border: 8px solid #2c5aa0;
                box-shadow: none;
                max-width: none;
                margin: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .signatures {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .municipality-name {
                font-size: 24px;
            }
            
            .title-main {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
    <div class="certificate-container">
        <!-- Decorative Corners -->
        <div class="certificate-border">
            <div class="decorative-corner corner-tl"></div>
            <div class="decorative-corner corner-tr"></div>
            <div class="decorative-corner corner-bl"></div>
            <div class="decorative-corner corner-br"></div>
            
            <!-- Watermark -->
            <div class="watermark">CERTIFIED</div>
            
            <!-- Header -->
            <div class="certificate-header">
                <div class="logo-section">
                    @if($logo_path && file_exists($logo_path))
                        <img src="{{ $logo_path }}" class="logo" alt="Tura Municipal Board Logo">
                    @else
                        <div style="width:80px;height:80px;border-radius:50%;border:4px solid white;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;text-align:center;background:#2c5aa0;">
                            TURA<br>MUNICIPAL
                        </div>
                    @endif
                </div>
                
                <div class="municipality-name">TURA MUNICIPAL BOARD</div>
                <div class="municipality-subtitle">West Garo Hills, Meghalaya</div>
                <div class="established-date">Established: 12-09-1979</div>
            </div>
            
            <!-- Certificate Title -->
            <div class="certificate-title">
                <h1 class="title-main">Pet Dog Registration Certificate</h1>
                <p class="title-subtitle">Official Certification of Canine Registration</p>
            </div>
            
            <!-- Main Content -->
            <div class="certificate-content">
                <p class="registration-intro">
                    This certifies that the following canine has been officially registered with the 
                    Tura Municipal Board as per the municipal regulations for pet registration and public health safety.
                </p>
                
                <!-- Registration Number -->
                <div class="registration-number">
                    <div class="reg-number-label">REGISTRATION NUMBER</div>
                    <div class="reg-number-value">{{ $registration_number }}</div>
                </div>
                
                <!-- Details Grid -->
                <div class="details-grid">
                    <!-- Owner Details -->
                    <div class="detail-item">
                        <span class="detail-label">Owner Name</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ strtoupper($owner_name) }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Contact Number</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $owner_phone }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Email Address</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $owner_email }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Aadhar Number</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $owner_aadhar_number }}</span>
                    </div>
                    
                    <!-- Pet Details -->
                    <div class="detail-item">
                        <span class="detail-label">Pet Name</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ strtoupper($dog_name) }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Breed</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $dog_breed }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Age</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $dog_age }} year(s)</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Gender</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ ucfirst($dog_gender) }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Color</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $dog_color }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Weight</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $dog_weight }} kg</span>
                    </div>
                    
                    <!-- Veterinary Details -->
                    <div class="detail-item">
                        <span class="detail-label">Veterinarian</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $veterinarian_name }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Vet License No.</span>
                        <span class="detail-separator">:</span>
                        <span class="detail-value">{{ $veterinarian_license }}</span>
                    </div>
                </div>
                
                <!-- Metal Tag Number -->
                @if($metal_tag_number)
                <div class="metal-tag-section">
                    <div style="font-size: 14px; margin-bottom: 8px;">METAL TAG NUMBER</div>
                    <div class="metal-tag-number">{{ $metal_tag_number }}</div>
                </div>
                @endif
                
                <!-- Vaccination Status -->
                <div class="validation-section">
                    <div class="validation-text">Vaccination Status</div>
                    <span class="vaccination-status {{ $vaccination_status === 'pending' ? 'pending' : '' }}">
                        {{ ucfirst($vaccination_status) }}
                    </span>
                    @if($vaccination_date)
                        <div style="margin-top: 10px; font-size: 14px; color: #6c757d;">
                            Last Vaccination: {{ date('d-m-Y', strtotime($vaccination_date)) }}
                        </div>
                    @endif
                </div>
                
                <!-- Issue Date -->
                <div class="issue-date">
                    <div class="issue-date-label">Certificate Issued On</div>
                    <div class="issue-date-value">{{ date('d F, Y') }}</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="certificate-footer">
                <div style="text-align: center; margin-bottom: 20px; font-size: 14px; color: #6c757d; font-style: italic;">
                    This certificate is valid subject to compliance with municipal regulations and annual renewal requirements.
                </div>
                
                <div class="signatures">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-title">Pet Owner</div>
                        <div class="signature-name">{{ $owner_name }}</div>
                    </div>
                    
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-title">Municipal Officer</div>
                        <div class="signature-name">Tura Municipal Board</div>
                    </div>
                </div>
                
                <!-- QR Code Section -->
                <div class="qr-section">
                    <div class="qr-code">
                        Verification<br>Code
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #6c757d;">
                    <strong>Note:</strong> This is a computer-generated certificate and is valid without signature.
                    <br>Certificate ID: {{ $registration_number }} | Generated on: {{ date('d-m-Y H:i:s') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>