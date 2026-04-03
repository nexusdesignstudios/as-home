<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HelperService;
use App\Models\Reservation;
use App\Models\Property;

// 1. Get the template
$template = system_setting('feedback_request_mail_template');
if (empty($template)) {
    echo "NO TEMPLATE IN SETTINGS. Using default.\n";
    $template = 'Dear {customer_name},

Thank you for choosing {app_name} for your recent stay!

We hope you had a wonderful experience at {property_name}. Your feedback is extremely valuable to us and helps us improve our services.

Please take a moment to share your experience by clicking on the link below to complete our feedback form:

{feedback_link}

Note: This feedback link is valid and unique to your reservation.';
}

echo "TEMPLATE FOUND:\n" . $template . "\n\n";

// 2. Pick a reservation (e.g. 2949)
$resId = 2949;
$reservation = Reservation::find($resId);

if (!$reservation) {
    echo "Reservation $resId not found.\n";
    exit;
}

$customer = $reservation->customer;
$property = null;
if ($reservation->reservable_type === 'App\Models\Property') {
    $property = $reservation->reservable;
} elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
    $property = $reservation->reservable->property;
}

$variables = [
    'app_name' => 'As-Home',
    'customer_name' => $customer->name ?? 'John Doe',
    'user_name' => $customer->name ?? 'John Doe', // Some templates use user_name
    'property_name' => $property->title ?? 'Sample Property',
    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
    'reservation_id' => $reservation->id,
    'feedback_link' => 'https://ashome-eg.com/feedback/test-token-123',
    'form_type' => 'Hotel',
];

$finalBody = HelperService::replaceEmailVariables($template, $variables);

// 3. Save as HTML preview
$htmlFile = __DIR__ . '/feedback_preview.html';
file_put_contents($htmlFile, $finalBody);
echo "Preview saved to $htmlFile\n";
