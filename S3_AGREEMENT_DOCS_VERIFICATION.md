# S3 Agreement Documents Upload Verification

## Current Status

### ✅ Code Implementation
Both `store_image()` and `handleFileUpload()` functions **fully support S3 uploads**:

1. **Admin Panel (PropertController)** - Uses `store_image()`:
   - ✅ `identity_proof` → `PROPERTY_IDENTITY_PROOF_PATH`
   - ✅ `national_id_passport` → `PROPERTY_NATIONAL_ID_PATH`
   - ✅ `utilities_bills` → `PROPERTY_UTILITIES_PATH`
   - ✅ `power_of_attorney` → `PROPERTY_POA_PATH`

2. **API Endpoints (ApiController)** - Uses `handleFileUpload()`:
   - ✅ `post_property` method
   - ✅ `update_post_property` method

### ⚠️ Important Note
**Both functions REQUIRE S3 to be configured.** They will throw an exception if `FILESYSTEM_DISK` is not set to `'s3'`:
```php
} else {
    throw new Exception('S3 disk is required for file uploads');
}
```

## S3 Upload Process

### How It Works:
1. **File Upload**: User uploads file via admin panel or API
2. **S3 Client**: Creates AWS S3 client using environment credentials
3. **S3 Key**: Constructs S3 key as `images/{path}/{filename}`
4. **Upload**: Uses `putObject()` to upload file to S3
5. **Verification**: Checks if file exists on S3 after upload
6. **Database**: Saves only the filename (not full path) to database
7. **Logging**: Extensive logging for debugging

### S3 Paths:
- Identity Proof: `images/property_identity_proof/{filename}`
- National ID/Passport: `images/property_national_id/{filename}`
- Utilities Bills: `images/property_utilities_bills/{filename}`
- Power of Attorney: `images/property_power_of_attorney/{filename}`

## Required Environment Configuration

To enable S3 uploads, set these in your `.env` file:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
```

## Verification Steps

### 1. Check S3 Configuration
Run the verification script:
```bash
php check_s3_agreement_docs.php
```

### 2. Check Logs
After uploading a document, check Laravel logs for:
- `store_image: starting` - Initial upload attempt
- `store_image: S3 put result` - Successful S3 upload
- `store_image: S3 upload complete` - Upload verification
- Any errors will be logged with `S3 upload failed`

### 3. Verify in S3
Check your S3 bucket for files in these paths:
- `images/property_identity_proof/`
- `images/property_national_id/`
- `images/property_utilities_bills/`
- `images/property_power_of_attorney/`

### 4. Test Upload
1. Go to property edit page: `http://localhost:8000/property/{id}/edit`
2. Upload a file to any agreement document field
3. Check logs for S3 upload confirmation
4. Verify file appears in S3 bucket

## Current Database Status

Recent properties with agreement documents:
- Property ID 191: National ID/Passport (1756716747.2234.docx)
- Property ID 79: Identity Proof + National ID/Passport
- Property ID 193: National ID/Passport
- Property ID 124: National ID/Passport
- Property ID 127: National ID/Passport

## Troubleshooting

### Issue: "S3 disk is required for file uploads"
**Solution**: Set `FILESYSTEM_DISK=s3` in `.env` and configure AWS credentials

### Issue: Files not appearing in S3
**Check**:
1. AWS credentials are correct
2. Bucket name is correct
3. IAM permissions allow PutObject
4. Check Laravel logs for errors

### Issue: Files saved but can't view
**Check**:
1. `PropertyDocumentController` can access S3 files
2. S3 bucket has public read permissions (or signed URLs)
3. File paths in database match S3 keys

## Code Locations

- **Helper Functions**: `app/Helpers/custom_helper.php`
  - `store_image()` - Line 826
  - `handleFileUpload()` - Line 663
  
- **Controllers**:
  - `app/Http/Controllers/PropertController.php` - Line 714-732
  - `app/Http/Controllers/ApiController.php` - Multiple locations
  
- **Document Viewer**: `app/Http/Controllers/PropertyDocumentController.php`
  - Handles viewing/downloading documents from S3

