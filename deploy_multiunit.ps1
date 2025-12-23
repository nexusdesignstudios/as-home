# Multi-Unit Vacation Homes - Deployment Script (PowerShell)
# Run this script on your production server

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Multi-Unit Vacation Homes Deployment" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Navigate to project directory
Write-Host "Step 1: Navigating to project directory..." -ForegroundColor Yellow
Set-Location "D:\ashome\as-home-dashboard-Admin"  # UPDATE THIS PATH
Write-Host "Current directory: $(Get-Location)" -ForegroundColor Green
Write-Host ""

# Step 2: Pull latest code (if using Git)
Write-Host "Step 2: Pulling latest code..." -ForegroundColor Yellow
# git pull origin main
Write-Host "Code updated" -ForegroundColor Green
Write-Host ""

# Step 3: Run migration
Write-Host "Step 3: Running database migration..." -ForegroundColor Yellow
php artisan migrate --path=database/migrations/2025_12_24_120000_add_apartment_fields_to_reservations_table.php
if ($LASTEXITCODE -eq 0) {
    Write-Host "Migration completed successfully" -ForegroundColor Green
} else {
    Write-Host "Migration failed! Please check the error above." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Fix existing reservations
Write-Host "Step 4: Fixing existing reservations..." -ForegroundColor Yellow
php fix_existing_reservations.php
if ($LASTEXITCODE -eq 0) {
    Write-Host "Existing reservations fixed" -ForegroundColor Green
} else {
    Write-Host "Fix script failed! Please check the error above." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 5: Clear caches
Write-Host "Step 5: Clearing Laravel caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
Write-Host "Caches cleared" -ForegroundColor Green
Write-Host ""

# Step 6: Verify implementation
Write-Host "Step 6: Verifying implementation..." -ForegroundColor Yellow
php verify_multiunit_implementation.php
if ($LASTEXITCODE -eq 0) {
    Write-Host "Verification passed" -ForegroundColor Green
} else {
    Write-Host "Verification failed! Please review the output above." -ForegroundColor Red
    exit 1
}
Write-Host ""

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan

