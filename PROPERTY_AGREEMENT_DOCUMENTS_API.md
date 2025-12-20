# Property Agreement Documents API Documentation

## Overview
This document provides the API endpoints for accessing property agreement documents. These endpoints allow the frontend to view and download the 5 agreement documents associated with each property.

---

## Base URL
```
http://localhost:8000/property/{propertyId}/document/{documentType}
```

**Production URL:**
```
https://maroon-fox-767665.hostingersite.com/property/{propertyId}/document/{documentType}
```

---

## API Endpoints

### 1. Identity Proof Document

**Endpoint:**
```
GET /property/{propertyId}/document/identity_proof
```

**Parameters:**
- `propertyId` (required): The ID of the property (integer)
- `documentType`: `identity_proof` (fixed)

**Query Parameters:**
- `download` (optional): Set to `1` to force download instead of preview

**Example URLs:**
```
# Preview document
http://localhost:8000/property/280/document/identity_proof

# Download document
http://localhost:8000/property/280/document/identity_proof?download=1
```

**Response:**
- **Success (200)**: Returns the document file with appropriate Content-Type header
- **Error (404)**: Property not found, document not found, or invalid document type

---

### 2. National ID/Passport Document

**Endpoint:**
```
GET /property/{propertyId}/document/national-id
```

**Parameters:**
- `propertyId` (required): The ID of the property (integer)
- `documentType`: `national-id` (fixed)

**Query Parameters:**
- `download` (optional): Set to `1` to force download instead of preview

**Example URLs:**
```
# Preview document
http://localhost:8000/property/280/document/national-id

# Download document
http://localhost:8000/property/280/document/national-id?download=1
```

**Response:**
- **Success (200)**: Returns the document file with appropriate Content-Type header
- **Error (404)**: Property not found, document not found, or invalid document type

---

### 3. Utilities Bills Document

**Endpoint:**
```
GET /property/{propertyId}/document/utilities-bills
```

**Parameters:**
- `propertyId` (required): The ID of the property (integer)
- `documentType`: `utilities-bills` (fixed)

**Query Parameters:**
- `download` (optional): Set to `1` to force download instead of preview

**Example URLs:**
```
# Preview document
http://localhost:8000/property/280/document/utilities-bills

# Download document
http://localhost:8000/property/280/document/utilities-bills?download=1
```

**Response:**
- **Success (200)**: Returns the document file with appropriate Content-Type header
- **Error (404)**: Property not found, document not found, or invalid document type

---

### 4. Power of Attorney Document

**Endpoint:**
```
GET /property/{propertyId}/document/power-of-attorney
```

**Parameters:**
- `propertyId` (required): The ID of the property (integer)
- `documentType`: `power-of-attorney` (fixed)

**Query Parameters:**
- `download` (optional): Set to `1` to force download instead of preview

**Example URLs:**
```
# Preview document
http://localhost:8000/property/280/document/power-of-attorney

# Download document
http://localhost:8000/property/280/document/power-of-attorney?download=1
```

**Response:**
- **Success (200)**: Returns the document file with appropriate Content-Type header
- **Error (404)**: Property not found, document not found, or invalid document type

---

### 5. Ownership Contract Document

**Endpoint:**
```
GET /property/{propertyId}/document/ownership-contract
```

**Parameters:**
- `propertyId` (required): The ID of the property (integer)
- `documentType`: `ownership-contract` (fixed)

**Query Parameters:**
- `download` (optional): Set to `1` to force download instead of preview

**Example URLs:**
```
# Preview document
http://localhost:8000/property/280/document/ownership-contract

# Download document
http://localhost:8000/property/280/document/ownership-contract?download=1
```

**Response:**
- **Success (200)**: Returns the document file with appropriate Content-Type header
- **Error (404)**: Property not found, document not found, or invalid document type

---

## Document Type Mapping

| Frontend Document Type | API Endpoint Value | Database Field |
|----------------------|-------------------|----------------|
| Identity Proof | `identity_proof` | `identity_proof` |
| National ID/Passport | `national-id` | `national_id_passport` |
| Utilities Bills | `utilities-bills` | `utilities_bills` |
| Power of Attorney | `power-of-attorney` | `power_of_attorney` |
| Ownership Contract | `ownership-contract` | `ownership_contract` |

---

## Supported File Types

The API supports the following file types:
- **Images**: JPG, JPEG, PNG, GIF, WEBP
- **Documents**: PDF, DOC, DOCX, XLS, XLSX, TXT, RTF
- **Archives**: ZIP, RAR

---

## Response Headers

### Preview Mode (default)
```
Content-Type: application/pdf (or appropriate MIME type)
Content-Disposition: inline; filename="document.pdf"
```

### Download Mode (?download=1)
```
Content-Type: application/pdf (or appropriate MIME type)
Content-Disposition: attachment; filename="document.pdf"
```

---

## Error Responses

### 404 Not Found
**Property not found:**
```json
{
  "message": "Property not found"
}
```

**Document not found:**
```json
{
  "message": "Document not found"
}
```

**Invalid document type:**
```json
{
  "message": "Invalid document type"
}
```

---

## Frontend Integration Examples

### JavaScript/React Example

```javascript
// Base URL configuration
const API_BASE_URL = 'http://localhost:8000'; // or production URL

// Document types mapping
const DOCUMENT_TYPES = {
  identityProof: 'identity_proof',
  nationalIdPassport: 'national-id',
  utilitiesBills: 'utilities-bills',
  powerOfAttorney: 'power-of-attorney',
  ownershipContract: 'ownership-contract'
};

// Function to get document URL
function getDocumentUrl(propertyId, documentType, download = false) {
  const baseUrl = `${API_BASE_URL}/property/${propertyId}/document/${documentType}`;
  return download ? `${baseUrl}?download=1` : baseUrl;
}

// Example: Get Identity Proof document URL
const identityProofUrl = getDocumentUrl(280, DOCUMENT_TYPES.identityProof);
// Result: http://localhost:8000/property/280/document/identity_proof

// Example: Get National ID/Passport document URL for download
const nationalIdDownloadUrl = getDocumentUrl(280, DOCUMENT_TYPES.nationalIdPassport, true);
// Result: http://localhost:8000/property/280/document/national-id?download=1

// Example: Open document in new tab
function viewDocument(propertyId, documentType) {
  const url = getDocumentUrl(propertyId, documentType);
  window.open(url, '_blank');
}

// Example: Download document
function downloadDocument(propertyId, documentType) {
  const url = getDocumentUrl(propertyId, documentType, true);
  window.location.href = url;
  // Or use fetch for more control
  fetch(url)
    .then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `document-${propertyId}-${documentType}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    });
}
```

### React Component Example

```jsx
import React from 'react';

const PropertyDocumentViewer = ({ propertyId }) => {
  const API_BASE_URL = 'http://localhost:8000';
  
  const documents = [
    { label: 'Identity Proof', type: 'identity_proof' },
    { label: 'National ID/Passport', type: 'national-id' },
    { label: 'Utilities Bills', type: 'utilities-bills' },
    { label: 'Power of Attorney', type: 'power-of-attorney' },
    { label: 'Ownership Contract', type: 'ownership-contract' }
  ];

  const getDocumentUrl = (documentType, download = false) => {
    const baseUrl = `${API_BASE_URL}/property/${propertyId}/document/${documentType}`;
    return download ? `${baseUrl}?download=1` : baseUrl;
  };

  return (
    <div className="property-documents">
      <h3>Agreement Documents</h3>
      {documents.map((doc) => (
        <div key={doc.type} className="document-item">
          <h4>{doc.label}</h4>
          <div className="document-actions">
            <a 
              href={getDocumentUrl(doc.type)} 
              target="_blank" 
              rel="noopener noreferrer"
              className="btn-view"
            >
              View
            </a>
            <a 
              href={getDocumentUrl(doc.type, true)} 
              download
              className="btn-download"
            >
              Download
            </a>
          </div>
        </div>
      ))}
    </div>
  );
};

export default PropertyDocumentViewer;
```

### Vue.js Example

```vue
<template>
  <div class="property-documents">
    <h3>Agreement Documents</h3>
    <div v-for="doc in documents" :key="doc.type" class="document-item">
      <h4>{{ doc.label }}</h4>
      <div class="document-actions">
        <a 
          :href="getDocumentUrl(doc.type)" 
          target="_blank"
          class="btn-view"
        >
          View
        </a>
        <a 
          :href="getDocumentUrl(doc.type, true)" 
          download
          class="btn-download"
        >
          Download
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PropertyDocumentViewer',
  props: {
    propertyId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      apiBaseUrl: 'http://localhost:8000',
      documents: [
        { label: 'Identity Proof', type: 'identity_proof' },
        { label: 'National ID/Passport', type: 'national-id' },
        { label: 'Utilities Bills', type: 'utilities-bills' },
        { label: 'Power of Attorney', type: 'power-of-attorney' },
        { label: 'Ownership Contract', type: 'ownership-contract' }
      ]
    };
  },
  methods: {
    getDocumentUrl(documentType, download = false) {
      const baseUrl = `${this.apiBaseUrl}/property/${this.propertyId}/document/${documentType}`;
      return download ? `${baseUrl}?download=1` : baseUrl;
    }
  }
};
</script>
```

---

## Testing Endpoints

### Test All 5 Endpoints

Use these test URLs with a valid property ID (replace `280` with an actual property ID):

```bash
# 1. Identity Proof
curl -I http://localhost:8000/property/280/document/identity_proof

# 2. National ID/Passport
curl -I http://localhost:8000/property/280/document/national-id

# 3. Utilities Bills
curl -I http://localhost:8000/property/280/document/utilities-bills

# 4. Power of Attorney
curl -I http://localhost:8000/property/280/document/power-of-attorney

# 5. Ownership Contract
curl -I http://localhost:8000/property/280/document/ownership-contract
```

### Browser Testing

Open these URLs directly in your browser:

```
http://localhost:8000/property/280/document/identity_proof
http://localhost:8000/property/280/document/national-id
http://localhost:8000/property/280/document/utilities-bills
http://localhost:8000/property/280/document/power-of-attorney
http://localhost:8000/property/280/document/ownership-contract
```

---

## Connectivity Test Script

### JavaScript Test Function

```javascript
async function testDocumentEndpoints(propertyId) {
  const baseUrl = 'http://localhost:8000';
  const documents = [
    { name: 'Identity Proof', type: 'identity_proof' },
    { name: 'National ID/Passport', type: 'national-id' },
    { name: 'Utilities Bills', type: 'utilities-bills' },
    { name: 'Power of Attorney', type: 'power-of-attorney' },
    { name: 'Ownership Contract', type: 'ownership-contract' }
  ];

  console.log(`Testing document endpoints for Property ID: ${propertyId}\n`);

  for (const doc of documents) {
    const url = `${baseUrl}/property/${propertyId}/document/${doc.type}`;
    try {
      const response = await fetch(url, { method: 'HEAD' });
      const status = response.status;
      const statusText = response.status === 200 ? '✅ Connected' : 
                        response.status === 404 ? '❌ Not Found' : 
                        `⚠️ Error: ${status}`;
      
      console.log(`${doc.name}: ${statusText} (${status})`);
      console.log(`  URL: ${url}`);
    } catch (error) {
      console.error(`${doc.name}: ❌ Connection Failed`);
      console.error(`  URL: ${url}`);
      console.error(`  Error: ${error.message}`);
    }
    console.log('');
  }
}

// Usage
testDocumentEndpoints(280);
```

### cURL Test Script (Bash)

```bash
#!/bin/bash

PROPERTY_ID=280
BASE_URL="http://localhost:8000"

echo "Testing Property Document Endpoints for Property ID: $PROPERTY_ID"
echo "================================================================"
echo ""

documents=(
  "Identity Proof:identity_proof"
  "National ID/Passport:national-id"
  "Utilities Bills:utilities-bills"
  "Power of Attorney:power-of-attorney"
  "Ownership Contract:ownership-contract"
)

for doc in "${documents[@]}"; do
  IFS=':' read -r name type <<< "$doc"
  url="$BASE_URL/property/$PROPERTY_ID/document/$type"
  
  echo "Testing: $name"
  echo "URL: $url"
  
  status=$(curl -s -o /dev/null -w "%{http_code}" "$url")
  
  if [ "$status" -eq 200 ]; then
    echo "Status: ✅ Connected (200 OK)"
  elif [ "$status" -eq 404 ]; then
    echo "Status: ⚠️  Not Found (404) - Document may not exist for this property"
  else
    echo "Status: ❌ Error ($status)"
  fi
  
  echo ""
done
```

---

## Summary

### All 5 API Endpoints:

1. **Identity Proof**: `GET /property/{id}/document/identity_proof`
2. **National ID/Passport**: `GET /property/{id}/document/national-id`
3. **Utilities Bills**: `GET /property/{id}/document/utilities-bills`
4. **Power of Attorney**: `GET /property/{id}/document/power-of-attorney`
5. **Ownership Contract**: `GET /property/{id}/document/ownership-contract`

### Quick Test URLs (Replace 280 with actual property ID):

```
http://localhost:8000/property/280/document/identity_proof
http://localhost:8000/property/280/document/national-id
http://localhost:8000/property/280/document/utilities-bills
http://localhost:8000/property/280/document/power-of-attorney
http://localhost:8000/property/280/document/ownership-contract
```

---

**Note:** All endpoints require the property to exist and have the document uploaded. If a document doesn't exist for a property, the endpoint will return a 404 error.

