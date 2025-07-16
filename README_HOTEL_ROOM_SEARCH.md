# Hotel Room Search API

This documentation explains how to use the Hotel Room Search API endpoint to find available rooms based on room type and date range.

## Endpoint

```
GET /api/search-available-rooms
```

## Parameters

| Parameter     | Type     | Required | Description                                                |
|---------------|----------|----------|------------------------------------------------------------|
| room_type_id  | integer  | No       | Filter rooms by room type ID                               |
| from_date     | date     | Yes      | Start date for availability search (format: YYYY-MM-DD)    |
| to_date       | date     | Yes      | End date for availability search (format: YYYY-MM-DD)      |
| property_id   | integer  | No       | Filter rooms by property ID                                |

## Response

### Success Response

```json
{
  "error": false,
  "message": "Available rooms fetched successfully",
  "data": [
    {
      "id": 1,
      "property_id": 1,
      "room_type_id": 1,
      "room_number": "101",
      "price_per_night": 100.00,
      "discount_percentage": 10.00,
      "refund_policy": "Refundable",
      "description": "Comfortable room with a view",
      "status": true,
      "availability_type": "available_days",
      "available_dates": [
        {
          "from": "2023-01-01",
          "to": "2023-01-15"
        },
        {
          "from": "2023-02-01",
          "to": "2023-02-15"
        }
      ],
      "weekend_commission": 5.00,
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z",
      "room_type": {
        "id": 1,
        "name": "Standard Room",
        "description": "Standard room with basic amenities",
        "status": true,
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
      },
      "property": {
        "id": 1,
        "title": "Hotel Name",
        // Other property details...
      }
    }
  ]
}
```

### Error Response

```json
{
  "error": true,
  "message": "Validation error",
  "errors": {
    "from_date": ["The from date field is required."],
    "to_date": ["The to date field is required."]
  }
}
```

## How It Works

The search functionality works with two different availability types:

1. **Available Days (availability_type = 1)**:
   - The room's `available_dates` field contains date ranges when the room is available.
   - Each date range has a `from` and `to` date.
   - The API checks if the requested date range (from_date to to_date) falls completely within any of the room's available date ranges.
   - If the entire requested date range is within an available date range, the room is included in the results.

2. **Busy Days (availability_type = 2)**:
   - The room's `available_dates` field contains date ranges when the room is NOT available (busy days).
   - Each date range has a `from` and `to` date.
   - The API checks if the requested date range (from_date to to_date) overlaps with any of the room's busy date ranges.
   - If there's no overlap between the requested date range and any busy date range, the room is included in the results.

## Date Range Format

The `available_dates` field uses the following format:

```json
[
  {
    "from": "2023-01-01",
    "to": "2023-01-15"
  },
  {
    "from": "2023-02-01",
    "to": "2023-02-15"
  }
]
```

## Examples

### Example 1: Search for rooms available from January 1-5, 2023

```
GET /api/search-available-rooms?from_date=2023-01-01&to_date=2023-01-05
```

### Example 2: Search for Standard rooms available from January 1-5, 2023

```
GET /api/search-available-rooms?from_date=2023-01-01&to_date=2023-01-05&room_type_id=1
```

### Example 3: Search for rooms in a specific property available from January 1-5, 2023

```
GET /api/search-available-rooms?from_date=2023-01-01&to_date=2023-01-05&property_id=1
```

## Notes

- The endpoint returns rooms that are active (`status = true`).
- The response includes related room type and property details.
- Dates must be in YYYY-MM-DD format.
- The to_date must be equal to or after from_date. 
 