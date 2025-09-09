<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultTemplate = '<p><strong>{app_name}</strong> received a new inquiry.</p>
<p><strong>From:</strong> {first_name} {last_name} ({email})</p>
<p><strong>Subject:</strong> {subject}</p>
<p><strong>Message:</strong><br>{message}</p>';

        Setting::updateOrCreate(
            ['type' => 'inquiry_form_mail_template'],
            ['data' => $defaultTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('type', 'inquiry_form_mail_template')->delete();
    }
};
