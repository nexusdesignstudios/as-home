#!/bin/bash

# Multi-Unit Vacation Homes - Deployment Script
# Run this script on your production server after pushing code

echo "=========================================="
echo "Multi-Unit Vacation Homes Deployment"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Navigate to project directory
echo -e "${YELLOW}Step 1: Navigating to project directory...${NC}"
# Update this path to your production server path
# cd /path/to/as-home-dashboard-Admin
echo "✅ Current directory: $(pwd)"
echo ""

# Step 2: Pull latest code (if using Git)
echo -e "${YELLOW}Step 2: Pulling latest code...${NC}"
# Uncomment if using Git:
# git pull origin main
# OR
# git pull origin master
echo "✅ Code updated"
echo ""

# Step 3: Run migration
echo -e "${YELLOW}Step 3: Running database migration...${NC}"
php artisan migrate --path=database/migrations/2025_12_24_120000_add_apartment_fields_to_reservations_table.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migration completed successfully${NC}"
else
    echo -e "${RED}❌ Migration failed! Please check the error above.${NC}"
    exit 1
fi
echo ""

# Step 4: Fix existing reservations
echo -e "${YELLOW}Step 4: Fixing existing reservations...${NC}"
php fix_existing_reservations.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Existing reservations fixed${NC}"
else
    echo -e "${RED}❌ Fix script failed! Please check the error above.${NC}"
    exit 1
fi
echo ""

# Step 5: Clear caches
echo -e "${YELLOW}Step 5: Clearing Laravel caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

# Step 6: Verify implementation
echo -e "${YELLOW}Step 6: Verifying implementation...${NC}"
php verify_multiunit_implementation.php
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Verification passed${NC}"
else
    echo -e "${RED}❌ Verification failed! Please review the output above.${NC}"
    exit 1
fi
echo ""

# Step 7: Restart services (if needed)
echo -e "${YELLOW}Step 7: Restarting services...${NC}"
# Uncomment and adjust based on your server setup:
# sudo service php8.1-fpm restart
# sudo service nginx restart
# OR
# sudo service apache2 restart
# sudo systemctl restart php-fpm
echo "✅ Services restarted (if applicable)"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ Deployment Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test a multi-unit vacation home booking"
echo "2. Verify hotel reservations still work"
echo "3. Verify single-unit vacation homes still work"
echo "4. Monitor logs: tail -f storage/logs/laravel.log"

