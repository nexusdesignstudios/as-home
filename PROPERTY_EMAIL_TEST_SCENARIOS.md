# Property Email Test Scenarios

## Test Overview
**Test User Email:** `nexlancer.eg@gmail.com`  
**Test Objective:** Verify that contract emails are sent immediately after property creation for 3 different property types.

---

## Prerequisites

1. **User Account Setup:**
   - User with email `nexlancer.eg@gmail.com` must exist in the system
   - User must be logged in and authenticated
   - User must have permission to create properties

2. **Email Templates Configuration:**
   - `list_property_sell_contract` template must be configured in admin panel
   - `basic_package_renting` template must be configured in admin panel
   - `premium_package_renting` template must be configured in admin panel

3. **Email Service:**
   - Email service must be properly configured and functional
   - Email logs should be accessible for verification

---

## Test Scenarios

### Test Scenario 1: Sell Property - List Property Sell Contract Email

**Test Case ID:** TC-PROP-EMAIL-001  
**Test Type:** Functional Test  
**Priority:** High

#### Test Description
Verify that when a user creates a **Sell Property** (`propery_type = 0`), the system sends a `list_property_sell_contract` email immediately after successful property submission.

#### Test Steps

1. **Login as Test User:**
   - Login to the system using `nexlancer.eg@gmail.com`
   - Verify authentication is successful

2. **Navigate to Property Creation:**
   - Go to "Add Property" or "Create Property" page
   - Select property type as **"Sell"** (`property_type = 0`)

3. **Fill Required Fields:**
   ```
   - title: "Test Sell Property - Luxury Apartment"
   - description: "Beautiful 3-bedroom apartment in prime location"
   - category_id: [Select any valid category]
   - property_type: 0 (Sell)
   - address: "123 Test Street, Cairo, Egypt"
   - price: 5000000
   - title_image: [Upload a valid image file]
   - city: "Cairo"
   - state: "Cairo Governorate"
   - country: "Egypt"
   ```

4. **Submit Property:**
   - Click "Submit" or "Save Property" button
   - Wait for successful submission response

5. **Verify Email Sent:**
   - Check email inbox for `nexlancer.eg@gmail.com`
   - Verify email with subject containing "List Property Sell Contract" or similar
   - Verify email contains contract PDF attachment
   - Verify email body contains welcoming message

#### Expected Results

✅ **Property Creation:**
- Property is created successfully
- API returns success response: `{"error": false, "message": "Property Post Successfully"}`

✅ **Email Delivery:**
- Email is sent to `nexlancer.eg@gmail.com` **immediately** after property creation
- Email subject matches the configured template title
- Email contains:
  - Welcoming message addressed to partner name
  - Contract PDF attachment
  - App name and contact information

✅ **Email Content Verification:**
- Email body includes:
  - "Dear [Partner Name]"
  - "Thank you for choosing [App Name] to list your property"
  - "Please find the [App Name] Property Listing Contract attached as a PDF"
- PDF attachment contains the full contract template with variables replaced:
  - `{app_name}` → Actual app name
  - `{partner_name}` → User's name
  - `{partner_address}` → User's address
  - `{agreement_year}` → Current date in "d F Y" format
  - `{le_id}` → "LE-[property_id]"
  - `{contract_date}` → Current date in "F d, Y" format

#### Test Data

```json
{
  "title": "Test Sell Property - Luxury Apartment",
  "description": "Beautiful 3-bedroom apartment in prime location",
  "category_id": 1,
  "property_type": 0,
  "address": "123 Test Street, Cairo, Egypt",
  "price": 5000000,
  "city": "Cairo",
  "state": "Cairo Governorate",
  "country": "Egypt",
  "latitude": "30.0444",
  "longitude": "31.2357"
}
```

#### Pass/Fail Criteria

**PASS:** All expected results are met  
**FAIL:** Any of the following:
- Property creation fails
- Email is not sent
- Email is sent with delay (not immediately)
- Email content is incorrect or missing
- PDF attachment is missing or corrupted
- Variables are not replaced correctly

---

### Test Scenario 2: Rent Property with Basic Package - Basic Package Renting Email

**Test Case ID:** TC-PROP-EMAIL-002  
**Test Type:** Functional Test  
**Priority:** High

#### Test Description
Verify that when a user creates a **Rent Property** (`propery_type = 1`) with **Basic Package** (`rent_package = "basic"`), the system sends a `basic_package_renting` email immediately after successful property submission.

#### Test Steps

1. **Login as Test User:**
   - Login to the system using `nexlancer.eg@gmail.com`
   - Verify authentication is successful

2. **Navigate to Property Creation:**
   - Go to "Add Property" or "Create Property" page
   - Select property type as **"Rent"** (`property_type = 1`)

3. **Select Basic Package:**
   - In the rent package selection, choose **"Basic Package"** (`rent_package = "basic"`)

4. **Fill Required Fields:**
   ```
   - title: "Test Rent Property - Basic Package Apartment"
   - description: "Cozy 2-bedroom apartment available for rent"
   - category_id: [Select any valid category]
   - property_type: 1 (Rent)
   - rent_package: "basic"
   - address: "456 Rental Avenue, Giza, Egypt"
   - price: 5000
   - title_image: [Upload a valid image file]
   - city: "Giza"
   - state: "Giza Governorate"
   - country: "Egypt"
   - property_classification: 1 or 2 (Commercial or New Project)
   ```

5. **Submit Property:**
   - Click "Submit" or "Save Property" button
   - Wait for successful submission response

6. **Verify Email Sent:**
   - Check email inbox for `nexlancer.eg@gmail.com`
   - Verify email with subject containing "Basic Package Renting Contract" or similar
   - Verify email contains contract PDF attachment
   - Verify email body contains welcoming message

#### Expected Results

✅ **Property Creation:**
- Property is created successfully with `propery_type = 1` and `rent_package = "basic"`
- API returns success response: `{"error": false, "message": "Property Post Successfully"}`

✅ **Email Delivery:**
- Email is sent to `nexlancer.eg@gmail.com` **immediately** after property creation
- Email subject matches "Basic Package Renting Contract"
- Email contains:
  - Welcoming message addressed to partner name
  - Contract PDF attachment
  - App name and contact information

✅ **Email Content Verification:**
- Email body includes:
  - "Dear [Partner Name]"
  - "Thank you for choosing [App Name] to list your property"
  - "Please find the [App Name] Property Listing Contract attached as a PDF"
- PDF attachment contains the full Basic Package Renting Contract template with all variables replaced correctly

#### Test Data

```json
{
  "title": "Test Rent Property - Basic Package Apartment",
  "description": "Cozy 2-bedroom apartment available for rent",
  "category_id": 1,
  "property_type": 1,
  "rent_package": "basic",
  "address": "456 Rental Avenue, Giza, Egypt",
  "price": 5000,
  "city": "Giza",
  "state": "Giza Governorate",
  "country": "Egypt",
  "latitude": "30.0131",
  "longitude": "31.2089",
  "property_classification": 1
}
```

#### Pass/Fail Criteria

**PASS:** All expected results are met  
**FAIL:** Any of the following:
- Property creation fails
- Email is not sent
- Email is sent with delay (not immediately)
- Wrong email template is used (should be `basic_package_renting`, not `basic_package_renting_self_managed`)
- Email content is incorrect or missing
- PDF attachment is missing or corrupted
- Variables are not replaced correctly

---

### Test Scenario 3: Rent Property with Premium Package - Premium Package Renting Email

**Test Case ID:** TC-PROP-EMAIL-003  
**Test Type:** Functional Test  
**Priority:** High

#### Test Description
Verify that when a user creates a **Rent Property** (`propery_type = 1`) with **Premium Package** (`rent_package = "premium"`), the system sends a `premium_package_renting` email immediately after successful property submission.

#### Test Steps

1. **Login as Test User:**
   - Login to the system using `nexlancer.eg@gmail.com`
   - Verify authentication is successful

2. **Navigate to Property Creation:**
   - Go to "Add Property" or "Create Property" page
   - Select property type as **"Rent"** (`property_type = 1`)

3. **Select Premium Package:**
   - In the rent package selection, choose **"Premium Package"** (`rent_package = "premium"`)

4. **Fill Required Fields:**
   ```
   - title: "Test Rent Property - Premium Package Villa"
   - description: "Luxurious 4-bedroom villa with premium amenities"
   - category_id: [Select any valid category]
   - property_type: 1 (Rent)
   - rent_package: "premium"
   - address: "789 Premium Boulevard, Alexandria, Egypt"
   - price: 15000
   - title_image: [Upload a valid image file]
   - city: "Alexandria"
   - state: "Alexandria Governorate"
   - country: "Egypt"
   - property_classification: 1 or 2 (Commercial or New Project)
   ```

5. **Submit Property:**
   - Click "Submit" or "Save Property" button
   - Wait for successful submission response

6. **Verify Email Sent:**
   - Check email inbox for `nexlancer.eg@gmail.com`
   - Verify email with subject containing "Premium Package Renting Contract" or similar
   - Verify email contains contract PDF attachment
   - Verify email body contains welcoming message

#### Expected Results

✅ **Property Creation:**
- Property is created successfully with `propery_type = 1` and `rent_package = "premium"`
- API returns success response: `{"error": false, "message": "Property Post Successfully"}`

✅ **Email Delivery:**
- Email is sent to `nexlancer.eg@gmail.com` **immediately** after property creation
- Email subject matches "Premium Package Renting Contract"
- Email contains:
  - Welcoming message addressed to partner name
  - Contract PDF attachment
  - App name and contact information

✅ **Email Content Verification:**
- Email body includes:
  - "Dear [Partner Name]"
  - "Thank you for choosing [App Name] to list your property"
  - "Please find the [App Name] Property Listing Contract attached as a PDF"
- PDF attachment contains the full Premium Package Renting Contract template with all variables replaced correctly

#### Test Data

```json
{
  "title": "Test Rent Property - Premium Package Villa",
  "description": "Luxurious 4-bedroom villa with premium amenities",
  "category_id": 1,
  "property_type": 1,
  "rent_package": "premium",
  "address": "789 Premium Boulevard, Alexandria, Egypt",
  "price": 15000,
  "city": "Alexandria",
  "state": "Alexandria Governorate",
  "country": "Egypt",
  "latitude": "31.2001",
  "longitude": "29.9187",
  "property_classification": 1
}
```

#### Pass/Fail Criteria

**PASS:** All expected results are met  
**FAIL:** Any of the following:
- Property creation fails
- Email is not sent
- Email is sent with delay (not immediately)
- Email content is incorrect or missing
- PDF attachment is missing or corrupted
- Variables are not replaced correctly

---

## Test Execution Checklist

### Pre-Test Setup
- [ ] User `nexlancer.eg@gmail.com` exists and is active
- [ ] User is logged in successfully
- [ ] All 3 email templates are configured in admin panel:
  - [ ] `list_property_sell_contract`
  - [ ] `basic_package_renting`
  - [ ] `premium_package_renting`
- [ ] Email service is configured and working
- [ ] Test environment is accessible

### Test Execution
- [ ] **TC-PROP-EMAIL-001:** Sell Property email test executed
- [ ] **TC-PROP-EMAIL-002:** Rent Property (Basic Package) email test executed
- [ ] **TC-PROP-EMAIL-003:** Rent Property (Premium Package) email test executed

### Post-Test Verification
- [ ] All 3 emails received in `nexlancer.eg@gmail.com` inbox
- [ ] All emails contain correct PDF attachments
- [ ] All email variables are replaced correctly
- [ ] Email timestamps confirm immediate sending (within seconds of property creation)
- [ ] No errors in application logs related to email sending

---

## Technical Implementation Details

### Email Sending Logic Location
**File:** `as-home-dashboard-Admin/app/Http/Controllers/ApiController.php`  
**Method:** `post_property()`  
**Lines:** 2292-2328

### Email Sending Method
**Method:** `sendContractEmail($propertyData, $contractType)`  
**Location:** `ApiController.php` (private method)

### Email Trigger Conditions

1. **Sell Property Email:**
   ```php
   if ($propertyType == 0) {
       $this->sendContractEmail($propertyData, "list_property_sell_contract");
   }
   ```

2. **Rent Basic Package Email:**
   ```php
   if ($propertyType == 1 && $rent_package == 'basic') {
       $this->sendContractEmail($propertyData, "basic_package_renting");
   }
   ```

3. **Rent Premium Package Email:**
   ```php
   if ($propertyType == 1 && $rent_package == 'premium') {
       $this->sendContractEmail($propertyData, "premium_package_renting");
   }
   ```

### Email Variables Used
- `{app_name}` - Application name from `env("APP_NAME")`
- `{partner_name}` - Customer name from `$propertyData->customer->name`
- `{partner_address}` - Customer address from `$propertyData->customer->address`
- `{agreement_year}` - Current date in "d F Y" format (e.g., "15 January 2024")
- `{le_id}` - Generated as "LE-[property_id]"
- `{contract_date}` - Current date in "F d, Y" format (e.g., "January 15, 2024")

---

## Error Handling

### Expected Behavior
- If email sending fails, the error is logged but **property creation still succeeds**
- Error is logged with details: `Log::error("Failed to send contract email after property creation: ...")`
- Property is saved to database regardless of email sending status

### Verification Points
- Check application logs for any email sending errors
- Verify property is created even if email fails
- Verify error messages are descriptive and include property ID

---

## Test Results Template

### Test Execution Date: _______________
### Test Executed By: _______________

| Test Case ID | Test Scenario | Status | Notes |
|-------------|---------------|--------|-------|
| TC-PROP-EMAIL-001 | Sell Property Email | ⬜ Pass / ⬜ Fail | |
| TC-PROP-EMAIL-002 | Rent Basic Package Email | ⬜ Pass / ⬜ Fail | |
| TC-PROP-EMAIL-003 | Rent Premium Package Email | ⬜ Pass / ⬜ Fail | |

### Overall Test Result: ⬜ PASS / ⬜ FAIL

### Issues Found:
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

### Recommendations:
1. _________________________________________________
2. _________________________________________________
3. _________________________________________________

---

## Notes

- All emails should be sent **immediately** after property creation, not waiting for admin approval
- Email sending happens after `DB::commit()`, ensuring property is saved before email is sent
- The `sendContractEmail` method handles PDF generation and email delivery
- Email templates are retrieved from system settings using `HelperService::getEmailTemplatesTypes()`
- If email template is empty, a default message is used: "Your Partner Agreement with {app_name}"

---

**Document Version:** 1.0  
**Last Updated:** [Current Date]  
**Author:** Test Team

