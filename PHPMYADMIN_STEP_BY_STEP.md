# How to Check Properties with Documents in phpMyAdmin

## Step-by-Step Guide

### Step 1: Open phpMyAdmin
1. Open your browser
2. Go to your phpMyAdmin URL (usually `http://localhost/phpmyadmin` or similar)
3. Login with your database credentials

### Step 2: Select Your Database
1. Click on your database name in the left sidebar
2. Make sure you're in the correct database (should contain the `propertys` table)

### Step 3: Open SQL Tab
1. Click on the **"SQL"** tab at the top
2. You'll see a text area where you can paste SQL queries

### Step 4: Copy and Paste the Query
1. Open the file: `PHPMYADMIN_CHECK_PROPERTIES_DOCS.sql`
2. **Copy the entire query** (the first SELECT statement)
3. **Paste it into the SQL text area** in phpMyAdmin

### Step 5: Run the Query
1. Click the **"Go"** button (or press Ctrl+Enter)
2. You'll see a table with results showing:
   - Property ID
   - Property Title
   - Owner ID
   - Type
   - Status
   - Which documents exist (YES ✓ or NO ✗)
   - Total document count
   - Admin Edit Link

### Step 6: View Results
The results will show:
- ✅ **YES ✓** = Document is saved in database
- ❌ **NO ✗** = Document is NOT saved

### Step 7: Check Properties in Admin Panel
1. Look at the **"Admin Edit Link"** column
2. Copy the URL (e.g., `http://localhost:8000/property/180/edit`)
3. Paste it in a new browser tab to check the property in admin panel
4. Look for the "Agreement Documents" section to verify documents are displayed

---

## Quick Summary Query

If you want to see just the statistics first, run this query:

```sql
SELECT 
    COUNT(*) AS 'Total Properties',
    SUM(IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0)) AS 'With Identity Proof',
    SUM(IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0)) AS 'With National ID',
    SUM(IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0)) AS 'With Utilities Bills',
    SUM(IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)) AS 'With Power of Attorney',
    SUM(IF(
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != ''),
        1, 0
    )) AS 'Properties with Any Document',
    SUM(IF(
        (identity_proof IS NOT NULL AND identity_proof != '') AND
        (national_id_passport IS NOT NULL AND national_id_passport != '') AND
        (utilities_bills IS NOT NULL AND utilities_bills != '') AND
        (power_of_attorney IS NOT NULL AND power_of_attorney != ''),
        1, 0
    )) AS 'Properties with ALL 4 Documents'
FROM propertys;
```

This will show you a single row with summary statistics.

---

## Tips

1. **Export Results**: Click "Export" button in phpMyAdmin to save results as CSV/Excel
2. **Sort Results**: Click on column headers to sort
3. **Filter**: Use the search box to filter results
4. **Copy URLs**: Right-click on Admin Edit Link and "Copy link address" to open in new tab

---

## What to Look For

- **Total Docs = 4**: Property has all documents ✅
- **Total Docs = 1-3**: Property has some documents (check which ones are missing)
- **YES ✓**: Document exists in database
- **NO ✗**: Document is missing

---

## Troubleshooting

**If you get an error:**
- Make sure you selected the correct database
- Check that the table name is `propertys` (not `properties`)
- Verify you have SELECT permissions on the table

**If no results appear:**
- It means no properties have documents saved yet
- Check if documents are being uploaded through the API/frontend

