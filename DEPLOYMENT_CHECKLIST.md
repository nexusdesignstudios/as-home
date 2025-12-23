# Multi-Unit Vacation Homes - Deployment Checklist

## Pre-Deployment
- [ ] Code committed and pushed to repository
- [ ] All files reviewed and tested locally
- [ ] Backup database (recommended)
- [ ] Backup current code (recommended)

## Deployment Steps
- [ ] Pull latest code to production server
- [ ] Run migration: `php artisan migrate --path=database/migrations/2025_12_24_120000_add_apartment_fields_to_reservations_table.php`
- [ ] Run fix script: `php fix_existing_reservations.php`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Verify: `php verify_multiunit_implementation.php`
- [ ] Restart services (if needed)

## Post-Deployment Testing
- [ ] Test multi-unit vacation home booking (quantity > 1)
- [ ] Test hotel reservation (should work as before)
- [ ] Test single-unit vacation home (should work as before)
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor for any errors

## Files to Deploy
- `app/Models/Reservation.php`
- `app/Services/ReservationService.php`
- `app/Http/Controllers/ReservationController.php`
- `database/migrations/2025_12_24_120000_add_apartment_fields_to_reservations_table.php`
- `fix_existing_reservations.php` (optional, for cleanup)
- `verify_multiunit_implementation.php` (optional, for verification)
- `deploy_multiunit.sh` (deployment script for Linux)
- `deploy_multiunit.ps1` (deployment script for Windows)
- `MULTI_UNIT_IMPLEMENTATION_SUMMARY.md` (documentation)

## Quick Deploy Command (One Line)
```bash
php artisan migrate --path=database/migrations/2025_12_24_120000_add_apartment_fields_to_reservations_table.php && php fix_existing_reservations.php && php artisan optimize:clear && php verify_multiunit_implementation.php
```

