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
        // Add vacation homes as-home managed premium package contract email template
        $vacationHomesAshomeManagedPremiumPackageTemplate = '<p>AY: {agreement_year}</p>

<p>Your Partner Agreement with {app_name} (LE ID:{le_id})</p>

<p>Between:</p>

<p>{app_name} for Asset Management<br>
Red Sea Governorate - North Hurghada - Al Nour and its Extension - Amlak Lands - 779/777<br>
|P.O. Box 25 l Hurghada, Egypt<br>
Commercial Register number: 262570, Tax Card number: 772-600-759</p>

<p>And You (the "Partner") For Vacation Homes As-home-Managed:</p>

<p>Full name: {partner_name}<br>
Main residential address: {partner_address}</p>

<p><strong>Preamble clause:</strong><br>
Whereas "{app_name} for Asset Management" is a company engaged in providing services related to the management, operation, marketing, purchase, sale, and maintenance of touristic properties, vacation homes, and hotels, in addition to offering smart management solutions for residential, commercial, and touristic units on behalf of their owners, through a specialized digital platform that organizes the contractual relationship between the Company and property owners or hotel establishments;</p>

<p>And whereas the Second Party wishes to cooperate with the Company in accordance with the service model specified in this Agreement;</p>

<p>Now therefore, the Parties have mutually agreed and consented to enter into this Agreement under the following terms and conditions.</p>

<p>Have agreed as follows:</p>

<p>For all properties which will be registered on {app_name} by, listed in the name of, or under the name of the Partner, the following local fee percentages apply:</p>

<p><strong>Fee 24.99%</strong></p>

<p><strong>Execution and performance</strong><br>
The Agreement is only effective after approval and confirmation by {app_name}.</p>

<p><strong>General delivery terms</strong><br>
This Agreement is subject to and governed by the General Delivery Terms (the "Terms"). The Partner confirms that they have read and hereby accept the GDTs.</p>

<p><strong>Additional properties</strong><br>
Any additional Partner registered on {app_name} and listed in the name of or under the name of the Partner will fall under the scope of this Agreement and, accordingly, will be subject to and governed by the Terms. In the event that any Partner falling under this Agreement was previously listed on {app_name} under another Partner agreement, the Partner will honor any outstanding reservations made prior to its inclusion in this Partner Agreement in accordance with (a) the Terms and (b) the relevant reservation conditions and fee percentage as applicable to the original reservation.</p>

<p><strong>The Partner has certified the following:</strong><br>
The Partner certifies that this is a legitimate Partners business with all necessary licenses and permits, which can be shown upon first request. {app_name} reserves the right to verify and investigate any detail the Partner provides in this registration.</p>

<p>Date: {contract_date}</p>';

        Setting::updateOrCreate(
            ['type' => 'vacation_homes_ashome_managed_premium_package_mail_template'],
            ['data' => $vacationHomesAshomeManagedPremiumPackageTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the vacation homes as-home managed premium package contract email template
        Setting::where('type', 'vacation_homes_ashome_managed_premium_package_mail_template')->delete();
    }
};
