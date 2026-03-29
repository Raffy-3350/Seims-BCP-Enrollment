# File Upload Feature - Setup & Implementation Guide

## Overview
This file upload feature allows users to upload documents during registration and view/manage uploaded files in the users table admin panel.

## Components

### 1. Database Schema
**Table: `user_files`**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `user_id` (VARCHAR(50), FOREIGN KEY → users.user_id)
- `file_name` (VARCHAR(255)) - System generated filename
- `original_name` (VARCHAR(255)) - Original filename as uploaded
- `file_size` (INT) - File size in bytes
- `file_type` (VARCHAR(100)) - MIME type
- `uploaded_at` (TIMESTAMP) - Upload timestamp
- `deleted_at` (TIMESTAMP, nullable) - Soft delete timestamp

**Migration File:** `database/migrations/001_create_user_files_table.sql`

### 2. Frontend Components

#### Student Registration Form
**File:** `modules/integration/pages/student_registration.php`

**Features:**
- Drag & drop file upload zone
- Click to browse functionality
- Real-time upload status feedback
- Support for multiple file types (PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, GIF)
- Max file size: 10MB per file

**Usage:**
1. User drags files into the dropbox OR clicks "Select Files" button
2. Files are uploaded immediately to `../api/upload_file.php`
3. Success/failure status is displayed for each file
4. Files are temporarily associated with the registration request

#### Users Management Table
**File:** `modules/user-creation/pages/users_table.html`

**Features:**
- Added "Files" column to users table
- Shows count of uploaded files per user
- Click file count to open files modal
- Modal displays all files with download and delete options
- File statistics (size, upload date)

### 3. API Endpoints

#### `upload_file.php`
**Method:** POST  
**Parameters:**
- `user_id` (form field) - User ID or temporary identifier
- `file` (form file) - The file to upload

**Response:**
```json
{
  "success": true,
  "file_id": 123,
  "file_name": "BCP-2026-001_1234567890_abcdef.pdf",
  "original_name": "document.pdf",
  "message": "File uploaded successfully"
}
```

#### `get_user_files.php`
**Method:** GET  
**Parameters:**
- `user_id` (query string) - User ID to retrieve files for

**Response:**
```json
{
  "success": true,
  "files": [
    {
      "id": 1,
      "file_name": "BCP-2026-001_1234567890_abcdef.pdf",
      "original_name": "document.pdf",
      "file_size": 51200,
      "file_size_human": "50 KB",
      "file_type": "application/pdf",
      "uploaded_at": "2026-03-27 10:30:00"
    }
  ],
  "count": 1
}
```

#### `delete_file.php`
**Method:** POST  
**Parameters:**
- `file_id` (JSON) - File ID to delete

**Response:**
```json
{
  "success": true,
  "message": "File deleted successfully"
}
```

#### `download_file.php`
**Method:** GET  
**Parameters:**
- `id` (query string) - File ID to download

**Returns:** Binary file content with appropriate headers

### 4. File Storage

**Location:** `assets/uploads/`

**File Naming Convention:**
```
{USER_ID}_{TIMESTAMP}_{UNIQUE_ID}.{EXTENSION}
```

Example: `BCP-2026-001_1709961000_5e3a4b2c.pdf`

**Allowed Extensions:**
- Document: pdf, doc, docx, xls, xlsx
- Image: png, jpg, jpeg, gif

**Max File Size:** 10MB per file

### 5. Security Features

1. **File Extension Validation** - Only whitelisted extensions allowed
2. **File Size Limits** - 10MB maximum per file
3. **User-Scoped Storage** - Files tracked with user_id
4. **MIME Type Checking** - File type validation
5. **Secure Download** - Files served with proper Content-Disposition headers
6. **Database Foreign Keys** - Cascading delete when user is deleted

### 6. Installation Steps

#### Step 1: Create Database Table
Run the migration SQL:
```sql
CREATE TABLE IF NOT EXISTS `user_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  KEY `user_id_idx` (`user_id`),
  KEY `uploaded_at_idx` (`uploaded_at`),
  CONSTRAINT `fk_user_files_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

#### Step 2: Create Uploads Directory
The directory `assets/uploads/` should exist with proper write permissions:
```bash
mkdir -p assets/uploads
chmod 755 assets/uploads
```

#### Step 3: Verify File Permissions
Ensure the web server (Apache/nginx) has write permissions:
```bash
chown -R www-data:www-data assets/uploads
chmod 775 assets/uploads
```

#### Step 4: Test Upload
1. Go to student registration form
2. Upload a test file
3. Check `assets/uploads/` for the file
4. Verify database entry in `user_files` table

### 7. Usage Flow

#### For Students (Registration)
1. Fill out registration form
2. Scroll to "Supporting Documents" section
3. Drag files into the dropbox or click "Select Files"
4. Wait for upload confirmation
5. Submit registration
6. Files are associated with their registration

#### For Admins (Users Table)
1. Go to Users Management page
2. Find user in the table
3. Click file count in "Files" column
4. Modal opens showing all files
5. Download or delete files as needed

### 8. Error Handling

**Common Issues:**

| Issue | Solution |
|-------|----------|
| "File type not allowed" | Upload only whitelisted formats |
| "File size exceeds 10MB" | Compress file or split into parts |
| "Failed to move uploaded file" | Check directory permissions |
| "Database insert failed" | Verify database connection |
| "File not found in database" | File may have been deleted |

### 9. Performance Considerations

- Files are stored on disk, not in database (BLOB)
- Database stores metadata only for fast queries
- Large files should be compressed before upload
- Consider implementing file cleanup/archival for old files
- Monitor disk space usage

### 10. Future Enhancements

- Virus scanning integration
- Thumbnail generation for images
- File versioning/history
- Bulk file operations
- File sharing/permissions
- Integration with cloud storage (AWS S3, Azure Blob)
- Searchable file metadata
- File preview capabilities

### 11. Troubleshooting

**Check XAMPP/Server:**
```
php -r "echo phpversion();"
```

**Check Directory Permissions:**
```
ls -la assets/uploads/
```

**Check Database Connection:**
- Verify connection in `modules/user-creation/api/config.php`
- Test with phpMyAdmin

**View Error Logs:**
- Apache: `c:/xampp/apache/logs/error.log`
- PHP: Check browser console for network errors

### 12. Support

For issues or questions:
1. Check error messages in browser console
2. Review database logs
3. Check file system permissions
4. Verify database table creation
5. Test with different file types and sizes
