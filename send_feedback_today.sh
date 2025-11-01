#!/bin/bash
# Script to send feedback email for customer with email neclancer.eg@gmail.com
# For reservations checking out TODAY

echo "Sending feedback email for customer: neclancer.eg@gmail.com"
echo "Looking for reservations checking out TODAY..."

# Method 1: Use guaranteed feedback command with email filter
php artisan feedback:guaranteed-send --email=neclancer.eg@gmail.com

echo ""
echo "Done! Check the output above for results."

