# Tura Municipal Board Logo Installation

## Step 1: Add Your Logo
Copy your Tura Municipal Board logo file to:
```
public/images/email/logo.png
```

## PowerShell Command to Copy Logo
If your logo file is in Downloads folder:
```powershell
Copy-Item -Path "$env:USERPROFILE\Downloads\tura-logo.png" -Destination ".\public\images\email\logo.png" -Force
```

Or replace the path with your logo location:
```powershell
Copy-Item -Path "C:\path\to\your\logo.png" -Destination ".\public\images\email\logo.png" -Force
```

## Step 2: Test Email with Logo
After adding the logo, test the email to see how it looks:
```powershell
php test_email_direct.php
```

Or use the test interface at:
http://127.0.0.1:8000/email_test_debug.html

## Logo Specifications
- Recommended size: 200x200 pixels or similar square format
- Format: PNG (supports transparency)
- File size: Keep under 100KB for faster email loading
- The email template will resize it to max-width: 120px automatically

## How the Logo Appears
The logo will appear:
- At the top of the email header
- Above the "Tura Municipal Board" title
- With rounded corners and shadow for professional look
- Responsive sizing for mobile email clients

## Troubleshooting
If logo doesn't appear:
1. Check file exists at: public/images/email/logo.png
2. Verify APP_URL in .env file is correct
3. Clear config cache: `php artisan config:clear`
4. Test email delivery

The logo URL in emails will be:
https://laravelv2.turamunicipalboard.com/images/email/logo.png