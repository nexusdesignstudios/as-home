# Filter Form APIs - Quick Reference for Frontend

## Base URL
```
https://maroon-fox-767665.hostingersite.com/api
```

---

## 1. Get Categories
**Endpoint:** `GET /api/get-categories`

**Query Parameters:**
- `property_classification` (optional): 1-5
- `offset` (optional): default 0
- `limit` (optional): default 10

**Response:**
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
      "property_classification": 1
    }
  ]
}
```

**Usage:**
```javascript
fetch('/api/get-categories?property_classification=1')
```

---

## 2. Get Facilities for Filter
**Endpoint:** `GET /api/get-facilities-for-filter`

**Query Parameters:**
- `category_id` (optional): Returns facilities ordered by category's parameter_types

**Response:**
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
      "name": "Parking",
      "type_of_parameter": "dropdown",
      "type_values": ["1 Car", "2 Cars", "3+ Cars"],
      "is_required": 0,
      "image": "https://..."
    }
  ]
}
```

**Usage:**
```javascript
// Get all facilities
fetch('/api/get-facilities-for-filter')

// Get facilities for category (ordered by category)
fetch('/api/get-facilities-for-filter?category_id=1')
```

**Note:** When `category_id` is provided, facilities are returned in the order defined in the category's `parameter_types`.

---

## 3. Get Cities
**Endpoint:** `GET /api/get-cities-data`

**Query Parameters:**
- `offset` (optional): default 0
- `limit` (optional): default 10

**Response:**
```json
{
  "error": false,
  "message": "Data Fetched Successfully",
  "data": [
    {
      "City": "Cairo",
      "Count": 150,
      "image": "https://..."
    }
  ],
  "total": 20
}
```

**Usage:**
```javascript
fetch('/api/get-cities-data?limit=100')
```

---

## 4. Get Property List (Main Filter Endpoint)
**Endpoint:** `GET /api/get-property-list`

### All Available Filter Parameters:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `property_type` | integer | 0=Sell, 1=Rent | `1` |
| `property_classification` | integer | 1-5 | `1` |
| `category_id` | integer | Category ID | `5` |
| `category_slug_id` | string | Category slug | `"apartment"` |
| `country` | string | Country name | `"Egypt"` |
| `state` | string | State name | `"Cairo"` |
| `city` | string | City name | `"Cairo"` |
| `min_price` | float | Minimum price | `100000` |
| `max_price` | float | Maximum price | `500000` |
| `bedrooms` | integer | **Exact match** | `3` |
| `bathrooms` | integer | **Exact match** | `2` |
| `posted_since` | integer | 0=Last Week, 1=Yesterday | `0` |
| `rent_package` | string | Rent package type | `"monthly"` |
| `latitude` | float | Location latitude | `30.0444` |
| `longitude` | float | Location longitude | `31.2357` |
| `radius` | float | Radius in km | `10` |
| `most_viewed` | integer | Sort by views (1) | `1` |
| `most_liked` | integer | Sort by likes (1) | `1` |
| `check_in_date` | date | Check-in (YYYY-MM-DD) | `"2025-01-15"` |
| `check_out_date` | date | Check-out (YYYY-MM-DD) | `"2025-01-20"` |
| `offset` | integer | Pagination offset | `0` |
| `limit` | integer | Results per page | `20` |

### Response Structure:
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
          "name": "Beach",
          "distance": 500,
          "image": "https://..."
        }
      ],
      "parameters": [
        {
          "id": 1,
          "name": "Bedrooms",
          "type_of_parameter": "number",
          "value": "3",
          "image": "https://..."
        },
        {
          "id": 2,
          "name": "Bathrooms",
          "type_of_parameter": "number",
          "value": "2",
          "image": "https://..."
        }
      ],
      "favourite_count": 10,
      "total_click": 150
    }
  ],
  "total": 100
}
```

### Example Requests:

```javascript
// Basic filter
GET /api/get-property-list?property_type=1&category_id=1&min_price=100000&max_price=500000

// Filter by bedrooms and bathrooms (exact match)
GET /api/get-property-list?bedrooms=3&bathrooms=2

// Filter by location
GET /api/get-property-list?city=Cairo&state=Cairo&country=Egypt

// Filter by location with radius
GET /api/get-property-list?latitude=30.0444&longitude=31.2357&radius=10

// Sort by most viewed
GET /api/get-property-list?most_viewed=1&limit=20

// Complete filter example
GET /api/get-property-list?property_type=1&category_id=1&city=Cairo&bedrooms=3&bathrooms=2&min_price=200000&max_price=600000&most_viewed=1&offset=0&limit=20
```

---

## Frontend Implementation Example

```javascript
// 1. Initialize filter state
const [filters, setFilters] = useState({
  property_type: null,
  property_classification: null,
  category_id: null,
  city: null,
  state: null,
  country: null,
  min_price: null,
  max_price: null,
  bedrooms: null,
  bathrooms: null,
  offset: 0,
  limit: 20
});

// 2. Load categories
const loadCategories = async () => {
  const response = await fetch('/api/get-categories?property_classification=1');
  const data = await response.json();
  return data.data;
};

// 3. Load facilities when category is selected
const loadFacilities = async (categoryId) => {
  const response = await fetch(`/api/get-facilities-for-filter?category_id=${categoryId}`);
  const data = await response.json();
  return data.data; // Ordered by category's parameter_types
};

// 4. Load cities
const loadCities = async () => {
  const response = await fetch('/api/get-cities-data?limit=100');
  const data = await response.json();
  return data.data;
};

// 5. Apply filters and get properties
const getFilteredProperties = async (filters) => {
  // Build query string (remove null/empty values)
  const params = new URLSearchParams();
  Object.keys(filters).forEach(key => {
    if (filters[key] !== null && filters[key] !== '') {
      params.append(key, filters[key]);
    }
  });
  
  const response = await fetch(`/api/get-property-list?${params.toString()}`);
  const data = await response.json();
  return data;
};

// 6. Handle facility form fields based on type
const renderFacilityField = (facility) => {
  switch (facility.type_of_parameter) {
    case 'number':
      // For bedrooms/bathrooms - exact match filter
      return (
        <input 
          type="number" 
          onChange={(e) => setFilters({...filters, [facility.name.toLowerCase()]: e.target.value})}
        />
      );
    
    case 'dropdown':
      return (
        <select onChange={(e) => setFilters({...filters, [facility.id]: e.target.value})}>
          {facility.type_values.map(option => (
            <option key={option} value={option}>{option}</option>
          ))}
        </select>
      );
    
    case 'checkbox':
      return (
        <div>
          {facility.type_values.map(option => (
            <label key={option}>
              <input 
                type="checkbox" 
                value={option}
                onChange={(e) => {
                  // Handle checkbox filter logic
                }}
              />
              {option}
            </label>
          ))}
        </div>
      );
    
    default:
      return null;
  }
};
```

---

## Important Notes

1. **Bedrooms/Bathrooms Filter:**
   - Uses **exact match** on parameter values
   - Backend searches for parameters with names containing "bedroom"/"bed" or "bathroom"/"bath"
   - Value must match exactly (e.g., "3" not "3+")

2. **Facility Ordering:**
   - When `category_id` is provided to `/api/get-facilities-for-filter`, facilities are returned in the order defined in the category's `parameter_types`
   - Frontend should preserve this order (no sorting needed)

3. **Parameter Ordering:**
   - Parameters in property list are ordered by category's `parameter_types`
   - Frontend should preserve this order

4. **Filter Logic:**
   - All filters are **AND** conditions (all must match)
   - Price: `min_price` uses `>=`, `max_price` uses `<=`
   - Location radius uses Haversine formula (distance in km)

5. **Response Format:**
   - Success: `{error: false, message: "...", data: [...], total: 100}`
   - Error: `{error: true, message: "Error message"}`

---

## Property Classifications
- `1` = Sell/Rent
- `2` = Commercial
- `3` = New Project
- `4` = Vacation Homes
- `5` = Hotel Booking

## Property Types
- `0` = Sell
- `1` = Rent

