# Property Edit Requests - Access Guide

This guide explains how to access and manage property edit requests in the admin panel.

## 📍 Where to Find Edit Requests

### 1. **Via API/JSON Endpoints** (For Admin Panel Integration)

The following endpoints are available for accessing property edit requests:

#### Get All Edit Requests
```
GET /property-edit-requests?status=pending
```
**Parameters:**
- `status` (optional): `pending`, `approved`, `rejected`, or `all` (default: `pending`)

**Response:**
```json
{
  "error": false,
  "message": "Property edit requests retrieved successfully",
  "data": {
    "edit_requests": [
      {
        "id": 1,
        "property_id": 123,
        "requested_by": 45,
        "status": "pending",
        "edited_data": {...},
        "original_data": {...},
        "property": {
          "id": 123,
          "title": "Property Title",
          "slug_id": "property-slug"
        },
        "requested_by": {
          "id": 45,
          "name": "Owner Name",
          "email": "owner@example.com"
        }
      }
    ]
  }
}
```

#### Get Specific Edit Request
```
GET /property-edit-requests/{id}
```

#### Approve/Reject Edit Request
```
POST /property-edit-requests/update-status
```
**Request Body:**
```json
{
  "edit_request_id": 1,
  "status": "approved",  // or "rejected"
  "reject_reason": "Reason for rejection"  // required if status is "rejected"
}
```

### 2. **Via Database Query** (Direct Access)

You can query the database directly:

```sql
-- Get all pending edit requests
SELECT * FROM property_edit_requests WHERE status = 'pending';

-- Get edit requests with property and owner details
SELECT 
    per.id,
    per.property_id,
    per.status,
    per.created_at,
    p.title AS property_title,
    c.name AS owner_name,
    c.email AS owner_email
FROM property_edit_requests per
INNER JOIN propertys p ON per.property_id = p.id
INNER JOIN customers c ON per.requested_by = c.id
WHERE per.status = 'pending'
ORDER BY per.created_at DESC;
```

### 3. **Via Laravel Tinker** (Command Line)

```bash
php artisan tinker
```

Then run:
```php
// Get all pending edit requests
$editRequests = \App\Models\PropertyEditRequest::where('status', 'pending')
    ->with(['property', 'requestedBy'])
    ->get();

// Get specific edit request
$editRequest = \App\Models\PropertyEditRequest::find(1);

// View the edited data
$editRequest->edited_data;

// View the original data
$editRequest->original_data;
```

### 4. **Via Admin Panel** (If UI is Created)

If you create an admin panel page, you can access it at:
```
/admin/property-edit-requests
```

## 🔧 How to Access via Code

### In Controllers
```php
use App\Models\PropertyEditRequest;

// Get all pending requests
$pendingRequests = PropertyEditRequest::where('status', 'pending')
    ->with(['property', 'requestedBy'])
    ->get();

// Get requests for a specific property
$propertyRequests = PropertyEditRequest::where('property_id', $propertyId)
    ->where('status', 'pending')
    ->get();
```

### In Blade Views
```php
@php
    $editRequests = \App\Models\PropertyEditRequest::where('status', 'pending')
        ->with(['property', 'requestedBy'])
        ->get();
@endphp

@foreach($editRequests as $request)
    <div>
        Property: {{ $request->property->title }}
        Owner: {{ $request->requestedBy->name }}
        Requested: {{ $request->created_at->format('Y-m-d H:i') }}
    </div>
@endforeach
```

## 📊 Understanding the Data

### Edit Request Structure
- **id**: Unique identifier
- **property_id**: ID of the property being edited
- **requested_by**: ID of the customer/owner who requested the edit
- **status**: `pending`, `approved`, or `rejected`
- **edited_data**: JSON object containing all the edited property fields
- **original_data**: JSON object containing the original property data before edits
- **reject_reason**: Reason for rejection (if rejected)
- **reviewed_by**: ID of admin who reviewed the request
- **reviewed_at**: Timestamp when request was reviewed

### Comparing Original vs Edited Data

To see what changed:
```php
$editRequest = PropertyEditRequest::find($id);
$original = $editRequest->original_data;
$edited = $editRequest->edited_data;

// Find differences
$changes = [];
foreach ($edited as $key => $value) {
    if (!isset($original[$key]) || $original[$key] != $value) {
        $changes[$key] = [
            'original' => $original[$key] ?? null,
            'edited' => $value
        ];
    }
}
```

## 🚀 Quick Access Methods

### Method 1: Direct URL (if routes are set up)
```
http://your-domain.com/property-edit-requests?status=pending
```

### Method 2: Using Postman/API Client
1. Set method to `GET`
2. URL: `http://your-domain.com/property-edit-requests`
3. Add header: `Authorization: Bearer {your-token}` (if using API auth)
4. Add query parameter: `?status=pending`

### Method 3: Add to Admin Menu
You can add a menu item in your admin panel that links to:
```php
route('property-edit-requests.index', ['status' => 'pending'])
```

## 📝 Example: Creating an Admin View

Create a view file: `resources/views/property/edit-requests.blade.php`

```blade
@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Property Edit Requests</h4>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($editRequests as $request)
                <tr>
                    <td>{{ $request->id }}</td>
                    <td>{{ $request->property->title }}</td>
                    <td>{{ $request->requestedBy->name }}</td>
                    <td>{{ ucfirst($request->status) }}</td>
                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('property-edit-requests.show', $request->id) }}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
```

## ✅ Summary

**Routes Available:**
- `GET /property-edit-requests` - List all edit requests
- `GET /property-edit-requests/{id}` - Get specific edit request
- `POST /property-edit-requests/update-status` - Approve/Reject request

**Database Table:**
- `property_edit_requests` - Stores all edit requests

**Model:**
- `App\Models\PropertyEditRequest` - Eloquent model

**Service:**
- `App\Services\PropertyEditRequestService` - Business logic for managing requests

