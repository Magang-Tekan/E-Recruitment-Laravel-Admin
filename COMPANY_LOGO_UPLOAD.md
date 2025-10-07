# Company Logo Upload Feature - Implementation Documentation

## Overview
Fitur upload logo untuk company telah diimplementasikan dengan menggunakan best practices Laravel. Implementasi ini mencakup:

1. **Model & Migration**: Field logo sudah ada di tabel companies
2. **Request Validation**: Dedicated request classes untuk validasi
3. **Service Layer**: Service classes untuk business logic
4. **Controller**: Clean controller menggunakan dependency injection
5. **Frontend**: React components dengan preview dan drag-drop support

## Files Yang Dimodifikasi/Dibuat

### Backend Files
1. **Models**
   - `app/Models/Company.php` - Updated with logo URL accessor
   
2. **Requests**
   - `app/Http/Requests/StoreCompanyRequest.php` - NEW
   - `app/Http/Requests/UpdateCompanyRequest.php` - NEW

3. **Services**
   - `app/Services/FileUploadService.php` - NEW
   - `app/Services/CompanyService.php` - NEW

4. **Controllers**
   - `app/Http/Controllers/CompanyController.php` - Updated to use services

5. **Tests**
   - `tests/Feature/CompanyLogoUploadTest.php` - NEW

### Frontend Files
1. **React Components**
   - `resources/js/pages/admin/companies/create.tsx` - Already has logo upload
   - `resources/js/pages/admin/companies/edit.tsx` - Already has logo upload

## Key Features

### 1. File Validation
- **Allowed formats**: jpeg, png, jpg, gif, webp
- **Maximum size**: 2MB
- **Required**: No (logo is optional)

### 2. Storage Management
- Files stored in `storage/app/public/company-logos/`
- Unique filename generation using UUID
- Automatic cleanup when company is deleted
- Public access via symlink

### 3. Security Features
- CSRF protection
- File type validation
- Size validation
- Secure filename generation

### 4. User Experience
- Image preview before upload
- Drag and drop support
- Progress indication
- Error handling with user-friendly messages

## Usage Examples

### Creating Company with Logo
```php
// Form submission will automatically handle logo upload
$request = new StoreCompanyRequest([
    'name' => 'Company Name',
    'logo' => $uploadedFile, // UploadedFile instance
    'description' => 'Description',
    // ... other fields
]);

$company = app(CompanyService::class)->create($request);
```

### Updating Company Logo
```php
// Only update logo if new file is provided
$request = new UpdateCompanyRequest([
    'logo' => $newUploadedFile, // Optional
    // ... other fields
]);

$company = app(CompanyService::class)->update($company, $request);
```

### Accessing Logo URL
```php
// Using accessor
$logoUrl = $company->logo_url;

// Using service method
$logoUrl = $company->getLogoUrl();

// Direct asset helper
$logoUrl = asset('storage/' . $company->logo);
```

## API Endpoints

### Create Company
```
POST /dashboard/companies
Content-Type: multipart/form-data

Fields:
- name (required|string|max:255)
- logo (nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048)
- description (nullable|string)
- email (nullable|email)
- phone (nullable|string|max:20)
- address (nullable|string)
- website (nullable|url)
- featured (nullable|boolean)
- display_order (nullable|integer|min:0)
- vision (nullable|string)
- mission (nullable|string)
```

### Update Company
```
PUT /dashboard/companies/{company}
Content-Type: multipart/form-data

Same fields as create, all nullable
```

## Directory Structure
```
storage/
├── app/
│   └── public/
│       └── company-logos/
│           └── [uuid].jpg
public/
└── storage/ -> ../storage/app/public (symlink)
```

## Configuration

### Storage Configuration
File: `config/filesystems.php`
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Create Storage Link
```bash
php artisan storage:link
```

## Error Handling

### Frontend Error Display
- Form validation errors displayed inline
- File upload errors shown with toast notifications
- Progress indicators during upload

### Backend Error Logging
- All file operations logged
- Exception handling with detailed error messages
- Rollback on failure

## Testing

### Running Tests
```bash
# Run all company logo tests
php artisan test tests/Feature/CompanyLogoUploadTest.php

# Run specific test
php artisan test --filter="test_logo_url_accessor"
```

### Test Coverage
- ✅ Create company with logo
- ✅ Update company logo
- ✅ Logo validation rules
- ✅ File deletion on company removal
- ✅ Logo URL generation

## Security Considerations

1. **File Validation**: Strict MIME type checking
2. **Size Limits**: 2MB maximum file size
3. **Filename Security**: UUID-based naming prevents conflicts
4. **Storage Location**: Files stored outside web root, accessed via symlink
5. **Access Control**: Only authenticated users can upload

## Performance Optimizations

1. **Unique Filenames**: Prevents cache issues
2. **Efficient Storage**: Automatic cleanup of old files
3. **CDN Ready**: File URLs can be easily switched to CDN
4. **Lazy Loading**: Images loaded on demand

## Troubleshooting

### Common Issues

1. **Storage Link Missing**
   ```bash
   php artisan storage:link
   ```

2. **Permission Issues**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/storage/
   ```

3. **File Not Found**
   - Check if file exists in `storage/app/public/company-logos/`
   - Verify symlink is working
   - Check file permissions

### Debug Information
- All file operations are logged in `storage/logs/laravel.log`
- Enable debug mode in `.env` for detailed error messages

## Future Enhancements

1. **Image Optimization**: Automatic resizing and compression
2. **Multiple Formats**: Support for SVG and WebP
3. **CDN Integration**: Amazon S3 or CloudFlare integration
4. **Image Variants**: Generate thumbnails automatically
5. **Bulk Upload**: Multiple logos at once