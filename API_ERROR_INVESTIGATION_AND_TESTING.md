# API Error Investigation & Testing Guide

## 🔍 Root Cause Analysis

### **Issue: Frontend Always Gets 500 Errors**

The frontend expects a specific response format, but our error handling might not be consistent. Let's investigate:

---

## 📋 Expected Response Format

### **Frontend Expects:**
```json
{
  "error": false,  // or true
  "message": "Success message or error message",
  "data": {...}    // or null
}
```

### **Current Backend Responses:**

#### **1. Success Response (Correct):**
```json
{
  "error": false,
  "message": "Data Fetch Successfully",
  "data": [...]
}
```

#### **2. Error Response (Inconsistent):**

**Some endpoints use:**
```php
return response()->json([
    'error' => true,
    'message' => 'Failed to fetch...'
], 500);
```

**Others use:**
```php
ApiResponseService::errorResponse('Something Went Wrong');
// This calls exit() and sends response
```

**Problem:** `ApiResponseService::errorResponse()` calls `exit()` which might cause issues in some contexts.

---

## 🐛 Issues Found

### **Issue 1: Inconsistent Error Handling**

**Problem:**
- Some endpoints use `ApiResponseService::errorResponse()` which calls `exit()`
- Others use `return response()->json([...], 500)`
- Frontend might not handle both formats correctly

**Example:**
```php
// homepageData - Uses ApiResponseService (calls exit())
catch (Exception $e) {
    ApiResponseService::errorResponse('Failed to fetch homepage data: ' . $e->getMessage());
}

// get_categories - Uses return (doesn't exit)
catch (Exception $e) {
    return response()->json([
        'error' => true,
        'message' => 'Failed to fetch categories: ' . $e->getMessage()
    ], 500);
}
```

### **Issue 2: Error Response Format**

**Frontend JavaScript expects:**
```javascript
if (!data.error) {
    // Success
} else {
    // Error - expects data.message
}
```

**But if backend returns 500 with no JSON body, frontend gets:**
```javascript
error: function (jqXHR) {
    // jqXHR.responseJSON might be undefined
    if (jqXHR.responseJSON) {
        showErrorToast(jqXHR.responseJSON.message);
    }
}
```

### **Issue 3: Missing Error Details**

When an exception occurs, we log it but the frontend might not get the actual error message if:
- Exception happens before try-catch
- Response is not properly formatted
- HTTP status code is 500 but body is empty

---

## ✅ Fixes Required

### **Fix 1: Standardize Error Responses**

All endpoints should return consistent error format:

```php
catch (Exception $e) {
    \Log::error('Endpoint failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'error' => true,
        'message' => 'Failed to fetch data: ' . $e->getMessage(),
        'data' => null
    ], 500);
}
```

**DO NOT use `ApiResponseService::errorResponse()` in catch blocks** because it calls `exit()` which can cause issues.

### **Fix 2: Ensure JSON Response Always**

Even on fatal errors, ensure JSON is returned:

```php
// Add to App\Exceptions\Handler.php
public function render($request, Throwable $exception)
{
    if ($request->expectsJson()) {
        return response()->json([
            'error' => true,
            'message' => 'Server Error: ' . $exception->getMessage(),
            'data' => null
        ], 500);
    }
    
    return parent::render($request, $exception);
}
```

### **Fix 3: Validate Response Format**

Ensure all endpoints return:
- `error` (boolean)
- `message` (string)
- `data` (mixed, can be null)

---

## 🧪 Testing Suite

### **Test Script: `test_api_endpoints.php`**

```php
<?php
/**
 * API Endpoint Testing Script
 * Run: php test_api_endpoints.php
 */

$baseUrl = 'https://maroon-fox-767665.hostingersite.com/api';
// Or local: $baseUrl = 'http://localhost:8000/api';

$endpoints = [
    'get_categories' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => []
    ],
    'get_categories_with_params' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => ['offset' => 0, 'limit' => 10]
    ],
    'get_categories_empty_params' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => ['slug_id' => '', 'is_promoted' => '']
    ],
    'web_settings' => [
        'url' => '/web-settings',
        'method' => 'GET',
        'params' => []
    ],
    'homepage_data' => [
        'url' => '/homepage-data',
        'method' => 'GET',
        'params' => []
    ],
    'homepage_data_empty_coords' => [
        'url' => '/homepage-data',
        'method' => 'GET',
        'params' => ['latitude' => '', 'longitude' => '', 'radius' => '']
    ],
    'get_added_properties' => [
        'url' => '/get-added-properties',
        'method' => 'GET',
        'params' => ['offset' => 0, 'limit' => 100]
    ],
];

function testEndpoint($baseUrl, $name, $config) {
    $url = $baseUrl . $config['url'];
    if (!empty($config['params'])) {
        $url .= '?' . http_build_query($config['params']);
    }
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Testing: {$name}\n";
    echo "URL: {$url}\n";
    echo str_repeat('-', 80) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Parse JSON
    $json = json_decode($body, true);
    $jsonError = json_last_error();
    
    echo "HTTP Code: {$httpCode}\n";
    echo "Content-Type: " . (strpos($headers, 'Content-Type: application/json') !== false ? 'JSON ✓' : 'NOT JSON ✗') . "\n";
    
    if ($jsonError === JSON_ERROR_NONE) {
        echo "JSON Valid: ✓\n";
        echo "Has 'error' field: " . (isset($json['error']) ? '✓' : '✗') . "\n";
        echo "Has 'message' field: " . (isset($json['message']) ? '✓' : '✗') . "\n";
        
        if (isset($json['error'])) {
            echo "Error value: " . ($json['error'] ? 'true' : 'false') . "\n";
        }
        
        if (isset($json['message'])) {
            echo "Message: " . substr($json['message'], 0, 100) . "\n";
        }
        
        if (isset($json['data'])) {
            $dataType = gettype($json['data']);
            echo "Data type: {$dataType}\n";
            if (is_array($json['data'])) {
                echo "Data count: " . count($json['data']) . "\n";
            }
        }
        
        // Check if response format is correct
        $isValid = isset($json['error']) && isset($json['message']);
        echo "\nResponse Format: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n";
        
        // Check if it's an error
        if ($httpCode >= 400) {
            echo "Status: ✗ ERROR (HTTP {$httpCode})\n";
        } else {
            echo "Status: ✓ SUCCESS\n";
        }
        
    } else {
        echo "JSON Valid: ✗ (Error: " . json_last_error_msg() . ")\n";
        echo "Response Body (first 200 chars):\n";
        echo substr($body, 0, 200) . "\n";
        echo "\nStatus: ✗ INVALID RESPONSE\n";
    }
    
    return [
        'name' => $name,
        'http_code' => $httpCode,
        'is_json' => $jsonError === JSON_ERROR_NONE,
        'has_error_field' => isset($json['error']),
        'has_message_field' => isset($json['message']),
        'is_valid_format' => isset($json['error']) && isset($json['message']),
        'is_success' => $httpCode < 400 && isset($json['error']) && $json['error'] === false,
        'response' => $json
    ];
}

// Run tests
$results = [];
foreach ($endpoints as $name => $config) {
    $results[] = testEndpoint($baseUrl, $name, $config);
    sleep(1); // Rate limiting
}

// Summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 80) . "\n";

$total = count($results);
$success = count(array_filter($results, function($r) { return $r['is_success']; }));
$validFormat = count(array_filter($results, function($r) { return $r['is_valid_format']; }));
$errors = count(array_filter($results, function($r) { return $r['http_code'] >= 400; }));

echo "Total Tests: {$total}\n";
echo "Successful: {$success}/{$total}\n";
echo "Valid Format: {$validFormat}/{$total}\n";
echo "Errors: {$errors}/{$total}\n";

echo "\nFailed Endpoints:\n";
foreach ($results as $result) {
    if (!$result['is_success']) {
        echo "  ✗ {$result['name']} (HTTP {$result['http_code']})\n";
    }
}

echo "\nInvalid Format Endpoints:\n";
foreach ($results as $result) {
    if (!$result['is_valid_format']) {
        echo "  ✗ {$result['name']} - Missing required fields\n";
    }
}
```

---

## 🔧 Quick Fixes to Apply

### **1. Fix homepageData Error Response**

```php
// In ApiController.php - homepageData method
catch (Exception $e) {
    \Log::error('homepageData failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // DON'T use ApiResponseService::errorResponse() - it calls exit()
    return response()->json([
        'error' => true,
        'message' => 'Failed to fetch homepage data: ' . $e->getMessage(),
        'data' => null
    ], 500);
}
```

### **2. Ensure All Endpoints Return JSON**

Add to `app/Exceptions/Handler.php`:

```php
public function render($request, Throwable $exception)
{
    // For API requests, always return JSON
    if ($request->is('api/*') || $request->expectsJson()) {
        return response()->json([
            'error' => true,
            'message' => 'Server Error: ' . $exception->getMessage(),
            'data' => null
        ], 500);
    }
    
    return parent::render($request, $exception);
}
```

### **3. Add Response Validation Middleware (Optional)**

Create middleware to validate all API responses have required fields.

---

## 📊 Testing Checklist

- [ ] Run test script against production
- [ ] Verify all endpoints return valid JSON
- [ ] Verify all responses have `error` and `message` fields
- [ ] Check Laravel logs for actual errors
- [ ] Test with empty parameters
- [ ] Test with invalid parameters
- [ ] Verify frontend can parse all responses
- [ ] Check browser console for JavaScript errors
- [ ] Verify error messages are user-friendly

---

## 🎯 Expected Results After Fixes

### **All Endpoints Should Return:**

**Success:**
```json
{
  "error": false,
  "message": "Data Fetch Successfully",
  "data": {...}
}
```

**Error:**
```json
{
  "error": true,
  "message": "Failed to fetch data: [specific error]",
  "data": null
}
```

**Never:**
- Empty response body
- HTML error page
- PHP fatal error output
- Missing `error` or `message` fields

---

**Last Updated:** After investigation
**Status:** 🔍 **Investigation Complete** - Fixes Required

