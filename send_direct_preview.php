<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Services\HelperService;
use Illuminate\Support\Str;

$reservationId = 2971; 
$emailTo = 'nexlancer.eg@gmail.com';

$reservation = Reservation::with(['customer', 'reservable'])->find($reservationId);
if (!$reservation) { echo "Res not found"; exit; }

$customer = $reservation->customer;
$propertyName = '';
if ($reservation->reservable_type === 'App\Models\Property') {
    $propertyName = $reservation->reservable->title ?? 'N/A';
} elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
    $propertyName = $reservation->reservable->property->title ?? 'N/A';
}

$token = Str::random(60);
$appUrl = config('app.url') ?: 'https://ashome-eg.com';
$feedbackUrl = "{$appUrl}/feedback/{$token}";

$variables = [
    'app_name' => 'As-home',
    'customer_name' => $customer->name ?? 'John Doe',
    'user_name' => $customer->name ?? 'John Doe',
    'property_name' => $propertyName,
    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
    'reservation_id' => $reservation->id,
    'feedback_link' => $feedbackUrl,
    'form_type' => 'Hotel',
];

$template = system_setting('feedback_request_mail_template');
if (empty($template)) {
    $template = '<p>Dear <strong>{customer_name}</strong>,</p>
<p>Thank you for choosing <strong>{app_name}</strong> for your recent stay!</p>
<p>We hope you had a wonderful experience at <strong>{property_name}</strong>. Your feedback is extremely valuable to us and helps us improve our services.</p>
<p>Please take a moment to share your experience by clicking on the link below to complete our feedback form:</p>
<p><a href="{feedback_link}">{feedback_link}</a></p>
<p>Best regards,<br>
The <strong>{app_name}</strong> Team</p>';
}

$emailContent = HelperService::replaceEmailVariables($template, $variables);

$data = [
    'email' => $emailTo,
    'title' => '[PREVIEW] Share Your Feedback - As-home',
    'email_template' => $emailContent
];

try {
    HelperService::sendMail($data);
    echo "✓ Email sent successfully to $emailTo for reservation $reservationId\n";
} catch (\Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
}
