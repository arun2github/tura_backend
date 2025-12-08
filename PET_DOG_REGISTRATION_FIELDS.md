🐕 **PET DOG REGISTRATION - COMPLETE FIELD LIST**
=====================================================

## 📝 **Required Fields (18 fields total)**

### **👤 Owner Information (5 fields)**
1. **owner_name** - `string(2-100)` - Full name of pet owner
2. **owner_phone** - `string(10-15)` - Contact phone number  
3. **owner_email** - `email(max:100)` - Email address
4. **owner_address** - `string(10-300)` - Complete residential address
5. **owner_aadhar_number** - `string(12)` - Aadhar card number (exactly 12 digits)

### **🐕 Dog Information (6 fields)**
6. **dog_name** - `string(2-50)` - Pet's name
7. **dog_breed** - `string(2-50)` - Breed of the dog
8. **dog_age** - `integer(1-20)` - Age in years
9. **dog_color** - `string(2-50)` - Primary color/marking
10. **dog_gender** - `enum(male,female)` - Gender
11. **dog_weight** - `numeric(1-100)` - Weight in kg

### **💉 Vaccination Details (4 fields)**
12. **vaccination_status** - `enum(completed,pending)` - Current vaccination status
13. **vaccination_date** - `date` - Last vaccination date
14. **veterinarian_name** - `string(2-100)` - Licensed veterinarian name
15. **veterinarian_license** - `string(5-50)` - Vet license number

### **📄 Documents & Declaration (2 fields)**
16. **document_list** - `array` - Base64 encoded documents (vaccination certificates, photos, etc.)
17. **declaration** - `string(10-500)` - Owner's declaration statement

### **🔧 System Fields (1 field)**
18. **form_id** - `integer` - Form type ID (0 for Pet Dog Registration)

---

## 📋 **Sample JSON Structure**

```json
{
    "form_id": 0,
    "owner_name": "John Doe",
    "owner_phone": "9876543210", 
    "owner_email": "john@example.com",
    "owner_address": "123 Main Street, Tura, Meghalaya - 794001",
    "owner_aadhar_number": "123456789012",
    "dog_name": "Buddy",
    "dog_breed": "Golden Retriever",
    "dog_age": 3,
    "dog_color": "Golden",
    "dog_gender": "male",
    "dog_weight": 30.5,
    "vaccination_status": "completed",
    "vaccination_date": "2024-12-01",
    "veterinarian_name": "Dr. Smith",
    "veterinarian_license": "VET12345",
    "document_list": [
        {
            "name": "vaccination_certificate.pdf",
            "data": "data:application/pdf;base64,JVBERi0xLjQ..."
        },
        {
            "name": "dog_photo.jpg",
            "data": "data:image/jpeg;base64,/9j/4AAQSkZJRgABA..."
        }
    ],
    "declaration": "I hereby declare that all information provided is true and accurate."
}
```

---

## 🗄️ **How Fields Are Stored (Dynamic Approach)**

Each field gets stored as a **parameter-value pair** in the `form_entity` table:

| Parameter | Value | Notes |
|-----------|-------|-------|
| owner_name | "John Doe" | Direct string |
| owner_phone | "9876543210" | Direct string |
| dog_age | "3" | Stored as string |
| dog_weight | "30.5" | Stored as string |
| vaccination_status | "completed" | Enum as string |
| document_list | `{"0":{"name":"cert.pdf","data":"base64..."}}` | JSON encoded array |
| declaration | "I hereby declare..." | Direct string |

---

## ✅ **Validation Rules**

- **Required**: All 18 fields are mandatory
- **String lengths**: Proper min/max character limits
- **Email**: Valid email format required
- **Phone**: 10-15 digit validation
- **Aadhar**: Exactly 12 digits
- **Age**: 1-20 years range
- **Weight**: 1-100 kg range
- **Enums**: Strict validation for gender and vaccination status
- **Documents**: Must be valid base64 encoded files
- **Date**: Valid date format (Y-m-d)

This field structure follows the **exact same pattern** as NAC Birth, Water Tanker, and Cesspool Tanker forms, ensuring consistency with the existing dynamic form system.