# Property Edit Field Classification

## Overview
This document classifies all property fields based on their approval behavior when edited by property owners.

## Field Categories

### 🔴 Fields Requiring Admin Approval

These fields require admin approval before changes are visible on the property listing. When a property owner edits these fields, an edit request is created and the property status is set to "pending" until admin approval.

| Field Name | Database Field | Description | Notes |
|------------|---------------|-------------|-------|
| Property Title (English) | `title` | Main property title in English | Both title fields checked separately |
| Property Title (Arabic) | `title_ar` | Main property title in Arabic | Both title fields checked separately |
| Property Description (English) | `description` | Detailed property description in English | Both description fields checked separately |
| Property Description (Arabic) | `description_ar` | Detailed property description in Arabic | Both description fields checked separately |
| Area Description (English) | `area_description` | Area/neighborhood description in English | Both area description fields checked separately |
| Area Description (Arabic) | `area_description_ar` | Area/neighborhood description in Arabic | Both area description fields checked separately |
| Title Image | `title_image` | Main property image | Checked via `hasFile()` - only new uploads trigger approval |
| Gallery Images | `gallery_images` | Property gallery images | Checked via `hasFile()` - only new uploads trigger approval |
| 3D Image | `three_d_image` | 3D tour image | Checked via `hasFile()` - only new uploads trigger approval |
| Meta Image (OG Image) | `meta_image` | Social media preview image | Checked via `hasFile()` - only new uploads trigger approval |
| Hotel Room Descriptions | `hotel_rooms[].description` | Description field within hotel rooms | Only the description field, not other room properties |

**Note:** Address/location fields (`address`, `latitude`, `longitude`, `state`, `city`, `country`) are currently in the approval-required list in the code, but were not specified in user requirements. This may need clarification.

### ✅ Fields with Automatic Approval (Live Update)

These fields are saved immediately without requiring admin approval. Changes are applied instantly and visible on the property listing right away.

#### Basic Property Information
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Price | `price` | Property price/rental rate |
| Property Type | `propery_type` | Sell/Rent/Sold/Rented status |
| Rent Duration | `rentduration` | Rental duration (daily, weekly, monthly, etc.) |
| Weekend Commission | `weekend_commission` | Commission percentage for weekends |
| Category | `category_id` | Property category |
| Property Classification | `property_classification` | Classification type (1-5) |
| Rent Package | `rent_package` | Basic or Premium package |
| Status | `status` | Active/Inactive status |
| Is Premium | `is_premium` | Premium listing flag |

#### Facilities & Parameters
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Outdoor Facilities | `facilities` (via `AssignedOutdoorFacilities`) | Nearby facilities with distances |
| Parameters | `parameters` (via `AssignParameters`) | Custom property parameters |

#### Hotel-Specific Fields
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Check-in Time | `check_in` | Hotel check-in time |
| Check-out Time | `check_out` | Hotel check-out time |
| Available Rooms | `available_rooms` | Number of available hotel rooms |
| Hotel VAT | `hotel_vat` | VAT percentage for hotel |
| Hotel Apartment Type | `hotel_apartment_type_id` | Type of hotel apartment |
| Instant Booking | `instant_booking` | Enable instant booking (for vacation homes) |
| Non-refundable | `non_refundable` | Non-refundable booking option |
| Agent Addons | `agent_addons` | Additional agent services |
| Corresponding Day | `corresponding_day` | Day-based pricing rules |

#### Vacation Home Fields
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Availability Type | `availability_type` | Available days vs. busy days |
| Available Dates | `available_dates` | Date-based availability and pricing |
| Vacation Apartment Quantities | `vacation_apartments[].quantity` | Quantity of each apartment type |

#### Contact Information
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Company Employee Username | `company_employee_username` | Contact person username |
| Company Employee Email | `company_employee_email` | Contact person email |
| Company Employee Phone | `company_employee_phone_number` | Contact person phone |
| Company Employee WhatsApp | `company_employee_whatsappnumber` | Contact person WhatsApp |
| Revenue User Name | `revenue_user_name` | Revenue contact name |
| Revenue Email | `revenue_email` | Revenue contact email |
| Revenue Phone Number | `revenue_phone_number` | Revenue contact phone |
| Reservation User Name | `reservation_user_name` | Reservation contact name |
| Reservation Email | `reservation_email` | Reservation contact email |
| Reservation Phone Number | `reservation_phone_number` | Reservation contact phone |
| Client Address | `client_address` | Client's address |

#### Other Fields
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Video Link | `video_link` | YouTube video URL |
| Meta Title | `meta_title` | SEO meta title |
| Meta Description | `meta_description` | SEO meta description |
| Meta Keywords | `meta_keywords` | SEO meta keywords |
| Slug ID | `slug_id` | URL-friendly identifier |

#### Related Data (Saved Separately)
| Field Name | Database Field | Description |
|------------|---------------|-------------|
| Hotel Rooms (non-description fields) | `hotel_rooms` | Room type, price, availability, etc. (only description requires approval) |
| Hotel Packages | `addons_packages` | Hotel addon packages |
| Hotel Certificates | `certificates` | Hotel certificates |
| Documents | `documents` | Property documents |
| Policy Data | `policy_data` | Policy documents |
| Identity Proof | `identity_proof` | Identity verification documents |
| National ID/Passport | `national_id_passport` | ID documents |
| Alternative ID | `alternative_id` | Alternative identification |
| Utilities Bills | `utilities_bills` | Utility bill documents |
| Power of Attorney | `power_of_attorney` | POA documents |
| Ownership Contract | `ownership_contract` | Ownership documents |
| Fact Sheet | `fact_sheet` | Hotel fact sheet |

## Approval Logic

### How Fields Are Classified

1. **Approval-Required Fields** are defined in:
   - Backend: `ApiController.php` lines 2981-2988 (`$approvalRequiredFields` array)
   - Backend: `PropertyEditRequestService.php` lines 17-36 (`getAllowedEditableFields()`)
   - Frontend: `EditPropertyTabs.jsx` lines 539-546 (`APPROVAL_REQUIRED_FIELDS`)

2. **Automatic Approval Fields** are all other fields not in the approval-required list.

### Field Change Detection

**Backend (`ApiController.php` - `hasApprovalRequiredChanges()` method):**
- Compares request values with current property values
- For images: Checks if new files are uploaded using `hasFile()`
- For hotel rooms: Compares only the description field
- Returns `true` if any approval-required field changed

**Frontend (`EditPropertyTabs.jsx` - `hasApprovalRequiredChanges()` method):**
- Compares form state (`tab1`, `tab5`, etc.) with `originalPropertyData`
- For images: Checks if File objects are present
- For hotel rooms: Compares description fields
- Returns `true` if any approval-required field changed

## Behavior Summary

### When Approval-Required Fields Are Changed:
1. Edit request is created (if auto-approve is OFF)
2. Property `request_status` set to "pending"
3. Changes stored in `property_edit_requests` table
4. Admin must approve/reject via admin panel
5. Only after approval are changes visible on listing

### When Only Automatic Approval Fields Are Changed:
1. Changes saved directly to property
2. Property `request_status` remains "approved"
3. Changes visible immediately on listing
4. No edit request created

### Mixed Changes (Both Types):
1. Approval-required fields → Create edit request (pending)
2. Automatic approval fields → Saved immediately
3. User sees both behaviors:
   - Immediate save message for auto-approved fields
   - Pending approval message for approval-required fields

## Code References

### Backend
- **Approval-Required Fields Definition:** `ApiController.php:2981-2988`
- **Approval Check Method:** `ApiController.php:12537-12609`
- **Field Filtering:** `PropertyEditRequestService.php:46-74`
- **Direct Save Logic:** `ApiController.php:3110-3136`

### Frontend
- **Approval-Required Fields Definition:** `EditPropertyTabs.jsx:539-546`
- **Approval Check Method:** `EditPropertyTabs.jsx:581-637`
- **Changed Fields List:** `EditPropertyTabs.jsx:639-700`
- **Success Message:** `EditPropertyTabs.jsx:2794-2852`

## Notes

1. **Selective Approval:** The system uses selective approval - only specific fields trigger the approval process. Other fields save immediately.

2. **Auto-Approve Setting:** When `auto_approve_edited_listings` is ON, ALL fields bypass approval regardless of classification.

3. **Admin Edits:** Admin users (`added_by == 0`) can edit all fields without approval, regardless of field classification.

4. **Image Handling:** Only NEW image uploads trigger approval. If images aren't changed, no approval is needed.

5. **Hotel Rooms:** Only the description field within hotel rooms requires approval. Other room properties (price, availability, etc.) save immediately.

