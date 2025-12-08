# Admit Card API Setup Instructions

## Installation Requirements

To complete the PDF generation functionality, you need to install the dompdf package:

```bash
composer require barryvdh/laravel-dompdf
```

After installation, add this line to your `config/app.php` providers array:
```php
Barryvdh\DomPDF\ServiceProvider::class,
```

And add this to the aliases array:
```php
'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
```

Alternatively, run:
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

## API Endpoints Created

### 1. Verify Admit Card
**POST** `/api/admit-card/verify`

**Request Body:**
```json
{
    "application_id": "APP123456",
    "email": "candidate@example.com"
}
```

**Success Response:**
```json
{
    "status": true,
    "message": "Record Found",
    "application_id": "APP123456",
    "roll_number": "ROLL001",
    "full_name": "John Doe",
    "download_url": "https://example.com/api/admit-card/download/ADMIT001"
}
```

**Error Response:**
```json
{
    "status": false,
    "message": "Invalid Application ID or Email"
}
```

### 2. Download Admit Card PDF
**GET** `/api/admit-card/download/{admit_no}`

- Downloads PDF file named `AdmitCard_{admit_no}.pdf`
- If dompdf is not installed, returns HTML view that can be printed

## Files Created

1. **Model:** `app/Models/TuraAdmitCard.php`
2. **Controller:** `app/Http/Controllers/Api/AdmitCardController.php`
3. **Routes:** Added to `routes/api.php`
4. **Blade Template:** `resources/views/pdf/admit_card.blade.php`

## Testing

You can test the APIs using tools like Postman or curl:

```bash
# Test verification
curl -X POST "http://127.0.0.1:8000/api/admit-card/verify" \
     -H "Content-Type: application/json" \
     -d '{"application_id":"APP123456","email":"test@example.com"}'

# Test download (replace ADMIT001 with actual admit_no)
curl -X GET "http://127.0.0.1:8000/api/admit-card/download/ADMIT001"
```

## Features

- Government-style admit card design
- Base64 photo support
- Comprehensive candidate and exam details
- Official instructions and guidelines
- Responsive design for PDF generation
- Error handling and validation
- Download tracking (updates `pdf_downloaded_at`)