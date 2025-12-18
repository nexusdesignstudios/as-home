# Frontend Filter Form APIs Documentation

This document provides all the API endpoints and data structures needed to implement the property filter form on the website.

---

## Base URL
```
https://maroon-fox-767665.hostingersite.com/api
```

---

## 1. Get Categories API

### Endpoint
```
GET /api/get-categories
```

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `offset` | integer | No | Pagination offset (default: 0) |
| `limit` | integer | No | Number of results (default: 10) |
| `search` | string | No | Search category by name |
| `id` | integer | No | Get specific category by ID |
| `slug_id` | string | No | Get category by slug |
| `property_classification` | integer | No | Filter by property classification (1-5) |
| `has_property` | boolean | No | Only return categories with properties |
| `latitude` | float | No | Filter by location (with longitude) |
| `longitude` | float | No | Filter by location (with latitude) |

### Response Structure
```json
{
  "error": false,
  "message": "Data Fetch Successfully",
  "total": 10,
  "data": [
    {
      "id": 1,
      "category": "Apartment",
      "image": "https://...",
      "slug_id": "apartment",
      "property_classification": 1,
      "parameter_types": [
        {
          "id": 1,
          "name": "Bedrooms",
          "type_of_parameter": "number",
          "type_values": null,
          "is_required": 1,
          "image": "https://..."
        }
      ],
      "meta_title": "...",
      "meta_description": "...",
      "meta_keywords": "..."
    }
  ]
}
```

### Example Request
```javascript
// Get all categories
GET /api/get-categories

// Get categories for specific classification
GET /api/get-categories?property_classification=2

// Search categories
GET /api/get-categories?search=apartment
```

---

## 2. Get Facilities for Filter API

### Endpoint
```
GET /api/get-facilities-for-filter
```

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `category_id` | integer | No | Get facilities ordered by category's parameter_types |

### Response Structure
```json
{
  "error": false,
  "message": "Data Fetched Successfully",
  "data": [
    {
      "id": 1,
      "name": "Bedrooms",
      "type_of_parameter": "number",
      "type_values": null,
      "is_required": 1,
      "image": "https://..."
    },
    {
      "id": 2,
      "name": "Bathrooms",
      "type_of_parameter": "number",
      "type_values": null,
      "is_required": 1,
      "image": "https://..."
    },
    {
      "id": 3,
      "name": "Swimming Pool",
      "type_of_parameter": "checkbox",
      "type_values": ["Yes", "No"],
      "is_required": 0,
      "image": "https://..."
    }
  ]
}
```

### Parameter Types
- `textbox` - Text input
- `textarea` - Multi-line text
- `dropdown` - Select dropdown (use `type_values` as options)
- `radiobutton` - Radio buttons (use `type_values` as options)
- `checkbox` - Checkboxes (use `type_values` as options)
- `file` - File upload
- `number` - Numeric input

### Example Request
```javascript
// Get all facilities
GET /api/get-facilities-for-filter

// Get facilities for specific category (ordered by category's parameter_types)
GET /api/get-facilities-for-filter?category_id=1
```

### Important Notes
- When `category_id` is provided, facilities are returned in the order defined in the category's `parameter_types`
- `type_values` is a JSON array when the parameter type is dropdown, radiobutton, or checkbox
- For number type parameters (like bedrooms, bathrooms), use exact match filtering

---

## 3. Get Cities Data API

### Endpoint
```
GET /api/get-cities-data
```

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `offset` | integer | No | Pagination offset (default: 0) |
| `limit` | integer | No | Number of results (default: 10) |

### Response Structure
```json
{
  "error": false,
  "message": "Data Fetched Successfully",
  "data": [
    {
      "City": "Cairo",
      "Count": 150,
      "image": "https://..."
    },
    {
      "City": "Alexandria",
      "Count": 85,
      "image": "https://..."
    }
  ],
  "total": 20
}
```

### Example Request
```javascript
GET /api/get-cities-data?limit=50
```

---

## 4. Get Property List API (Filtered Results)

### Endpoint
```
GET /api/get-property-list
```

### Request Parameters

#### Basic Filters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `offset` | integer | No | Pagination offset (default: 0) |
| `limit` | integer | No | Number of results (default: 10) |
| `property_classification` | integer | No | 1=Sell/Rent, 2=Commercial, 3=New Project, 4=Vacation Homes, 5=Hotel |
| `property_type` | integer | No | 0=Sell, 1=Rent |
| `category_id` | integer | No | Filter by category ID |
| `category_slug_id` | string | No | Filter by category slug |
| `country` | string | No | Filter by country |
| `state` | string | No | Filter by state |
| `city` | string | No | Filter by city |
| `min_price` | float | No | Minimum price |
| `max_price` | float | No | Maximum price |
| `posted_since` | integer | No | 0=Last Week, 1=Yesterday |
| `rent_package` | string | No | Rent package type |
| `hotel_apartment_type_id` | integer | No | Hotel apartment type (when property_classification=5) |

#### Location Filters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `latitude` | float | No | Location latitude |
| `longitude` | float | No | Location longitude |
| `radius` | float | No | Radius in km (requires latitude/longitude) |

#### Facility/Parameter Filters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bedrooms` | integer | No | Exact match for number of bedrooms |
| `bathrooms` | integer | No | Exact match for number of bathrooms |

#### Sorting
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `most_viewed` | integer | No | 1=Sort by most viewed |
| `most_liked` | integer | No | 1=Sort by most liked |

#### Date Filters (for Vacation Homes)
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `check_in_date` | date | No | Check-in date (format: YYYY-MM-DD) |
| `check_out_date` | date | No | Check-out date (format: YYYY-MM-DD, must be after check_in_date) |

### Response Structure
```json
{
  "error": false,
  "message": "Data Fetched Successfully",
  "data": [
    {
      "id": 1,
      "slug_id": "property-slug",
      "title": "Luxury Apartment",
      "price": 500000,
      "title_image": "https://...",
      "city": "Cairo",
      "state": "Cairo",
      "country": "Egypt",
      "property_type": "sell",
      "property_classification": 1,
      "is_premium": true,
      "latitude": 30.0444,
      "longitude": 31.2357,
      "category": {
        "id": 1,
        "category": "Apartment",
        "image": "https://...",
        "slug_id": "apartment"
      },
      "assign_facilities": [
        {
          "id": 1,
          "property_id": 1,
          "facility_id": 5,
          "distance": 500,
          "name": "Beach",
          "image": "https://..."
        }
      ],
      "parameters": [
        {
          "id": 1,
          "name": "Bedrooms",
          "type_of_parameter": "number",
          "type_values": null,
          "is_required": 1,
          "image": "https://...",
          "value": "3"
        },
        {
          "id": 2,
          "name": "Bathrooms",
          "type_of_parameter": "number",
          "type_values": null,
          "is_required": 1,
          "image": "https://...",
          "value": "2"
        }
      ],
      "favourite_count": 10,
      "total_click": 150
    }
  ],
  "total": 100
}
```

### Example Requests

```javascript
// Basic filter
GET /api/get-property-list?property_type=1&category_id=1&min_price=100000&max_price=500000

// Filter by bedrooms and bathrooms
GET /api/get-property-list?bedrooms=3&bathrooms=2

// Filter by location
GET /api/get-property-list?city=Cairo&state=Cairo

// Filter with radius
GET /api/get-property-list?latitude=30.0444&longitude=31.2357&radius=10

// Sort by most viewed
GET /api/get-property-list?most_viewed=1

// Vacation home with dates
GET /api/get-property-list?property_classification=4&check_in_date=2025-01-15&check_out_date=2025-01-20
```

### Important Notes
- `assign_facilities` are ordered by database insertion order
- `parameters` are ordered by the category's `parameter_types` field
- `bedrooms` and `bathrooms` filters use **exact match** on parameter values
- Parameters are matched by name containing "bedroom"/"bed" or "bathroom"/"bath"

---

## 5. Filter Form Implementation Guide

### Step 1: Load Initial Data

```javascript
// Load categories
const categories = await fetch('/api/get-categories?property_classification=1');

// Load cities
const cities = await fetch('/api/get-cities-data?limit=100');

// Load facilities (when category is selected)
const facilities = await fetch(`/api/get-facilities-for-filter?category_id=${selectedCategoryId}`);
```

### Step 2: Build Filter Form

```javascript
const filterForm = {
  // Basic filters
  property_type: null, // 0 or 1
  property_classification: null, // 1-5
  category_id: null,
  country: null,
  state: null,
  city: null,
  min_price: null,
  max_price: null,
  
  // Facility filters
  bedrooms: null,
  bathrooms: null,
  
  // Other filters
  posted_since: null, // 0 or 1
  most_viewed: null, // 1
  most_liked: null, // 1
  
  // Location
  latitude: null,
  longitude: null,
  radius: null, // in km
  
  // Pagination
  offset: 0,
  limit: 10
};
```

### Step 3: Handle Dynamic Facilities

```javascript
// When category changes, reload facilities
async function onCategoryChange(categoryId) {
  const facilities = await fetch(
    `/api/get-facilities-for-filter?category_id=${categoryId}`
  ).then(r => r.json());
  
  // Render facilities based on type_of_parameter
  facilities.data.forEach(facility => {
    if (facility.type_of_parameter === 'number') {
      // Render number input
    } else if (facility.type_of_parameter === 'dropdown') {
      // Render dropdown with facility.type_values as options
    } else if (facility.type_of_parameter === 'checkbox') {
      // Render checkboxes with facility.type_values as options
    }
  });
}
```

### Step 4: Apply Filters

```javascript
async function applyFilters(filterForm) {
  // Build query string
  const params = new URLSearchParams();
  
  Object.keys(filterForm).forEach(key => {
    if (filterForm[key] !== null && filterForm[key] !== '') {
      params.append(key, filterForm[key]);
    }
  });
  
  // Fetch filtered properties
  const response = await fetch(`/api/get-property-list?${params.toString()}`);
  const data = await response.json();
  
  return data;
}
```

### Step 5: Handle Bedrooms/Bathrooms Filter

```javascript
// These use exact match on parameter values
// The backend searches for parameters with names containing "bedroom"/"bed" or "bathroom"/"bath"
// and matches the exact value

filterForm.bedrooms = 3; // Exact match: value must be "3"
filterForm.bathrooms = 2; // Exact match: value must be "2"
```

---

## 6. Response Format

All APIs follow this standard response format:

### Success Response
```json
{
  "error": false,
  "message": "Data Fetched Successfully",
  "data": [...],
  "total": 100  // Optional, for paginated responses
}
```

### Error Response
```json
{
  "error": true,
  "message": "Error message here"
}
```

---

## 7. Important Implementation Notes

### Order Preservation
- **Facilities**: Order is preserved from API response (category's `parameter_types` order when `category_id` is provided)
- **Parameters**: Order is preserved from API response (category's `parameter_types` order)
- **Assign Facilities**: Order is preserved from API response (database insertion order)

### Filter Logic
- All filters are **AND** conditions (all must match)
- `bedrooms` and `bathrooms` use **exact match** on parameter values
- Price filters use `>=` for min_price and `<=` for max_price
- Location radius filter uses Haversine formula (distance in km)

### Parameter Value Matching
- For `bedrooms` filter: Searches parameters with name containing "bedroom" or "bed" and value exactly matching the filter value
- For `bathrooms` filter: Searches parameters with name containing "bathroom" or "bath" and value exactly matching the filter value

---

## 8. Example Complete Filter Request

```javascript
// Complete filter example
const filterParams = {
  property_type: 1,              // Rent
  property_classification: 1,     // Sell/Rent
  category_id: 5,                  // Apartment category
  city: "Cairo",
  state: "Cairo",
  country: "Egypt",
  min_price: 100000,
  max_price: 500000,
  bedrooms: 3,                     // Exact match
  bathrooms: 2,                    // Exact match
  most_viewed: 1,                  // Sort by views
  offset: 0,
  limit: 20
};

const queryString = new URLSearchParams(filterParams).toString();
const response = await fetch(`/api/get-property-list?${queryString}`);
```

---

## 9. Frontend Checklist

- [ ] Load categories on page load
- [ ] Load cities for location filter
- [ ] Load facilities when category is selected (ordered by category)
- [ ] Build dynamic form fields based on facility `type_of_parameter`
- [ ] Handle number inputs for bedrooms/bathrooms (exact match)
- [ ] Handle dropdown/checkbox for other facility types
- [ ] Apply all filters as AND conditions
- [ ] Preserve order of facilities/parameters from API response
- [ ] Implement pagination with offset/limit
- [ ] Handle loading and error states

---

## Support

For questions or issues, refer to the backend API documentation or contact the development team.

