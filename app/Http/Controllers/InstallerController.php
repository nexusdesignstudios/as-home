<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use dacoto\EnvSet\Facades\EnvSet;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request as RequestFacades;
use dacoto\LaravelWizardInstaller\Controllers\InstallFolderController;
use dacoto\LaravelWizardInstaller\Controllers\InstallServerController;

class InstallerController extends Controller {
    public function purchaseCodeIndex() {
        if ((new InstallServerController())->check() === false || (new InstallFolderController())->check() === false) {
            return redirect()->route('install.folders');
        }
        return view('vendor.installer.steps.purchase-code');
    }


    public function checkPurchaseCode(Request $request) {
        try {
            // Skip purchase code validation for local development
            if (config('app.env') === 'local' || config('app.env') === 'development') {
                $purchaseCode = $request->input('purchase_code', 'LOCAL-DEV-KEY');
                
                try {
                    EnvSet::setKey('APPSECRET', $purchaseCode);
                    EnvSet::save();
                } catch (\Exception $e) {
                    // If EnvSet fails, use updateEnv directly
                    $envUpdates = [
                        'APPSECRET' => $purchaseCode,
                    ];
                    updateEnv($envUpdates);
                }
                
                $envUpdates = [
                    'APP_URL' => RequestFacades::root(),
                ];
                updateEnv($envUpdates);
                
                return redirect()->route('install.database');
            }
            
            $app_url = (string)url('/');
            $app_url = preg_replace('#^https?://#i', '', $app_url);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://wrteam.in/validator/ebroker_validator?purchase_code=' . urlencode($request->input('purchase_code')) . '&domain_url=' . urlencode($app_url),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_SSL_VERIFYPEER => false,
            ));
            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($curlError || $response === false) {
                throw new Exception("Connection error: " . ($curlError ?: "Failed to connect to validation server"));
            }
            
            $response = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If JSON decode fails, allow local development to proceed
                if (app()->environment('local') || app()->environment('development')) {
                    $purchaseCode = $request->input('purchase_code', 'LOCAL-DEV-KEY');
                    
                    try {
                        EnvSet::setKey('APPSECRET', $purchaseCode);
                        EnvSet::save();
                    } catch (\Exception $e) {
                        $envUpdates = ['APPSECRET' => $purchaseCode];
                        updateEnv($envUpdates);
                    }
                    
                    $envUpdates = ['APP_URL' => RequestFacades::root()];
                    updateEnv($envUpdates);
                    
                    return redirect()->route('install.database');
                }
                throw new Exception("Invalid response from validation server");
            }
            
            if (isset($response['error']) && $response['error']) {
                return view('vendor.installer.steps.purchase-code', [
                    'values' => ['purchase_code' => $request->input('purchase_code')],
                    'error' => $response["message"] ?? 'Invalid purchase code'
                ]);
            }

            try {
                EnvSet::setKey('APPSECRET', $request->input('purchase_code'));
                EnvSet::save();
            } catch (\Exception $e) {
                // If EnvSet fails, use updateEnv directly
                $envUpdates = [
                    'APPSECRET' => $request->input('purchase_code'),
                ];
                updateEnv($envUpdates);
            }

            $envUpdates = [
                'APP_URL' => RequestFacades::root(),
            ];
            updateEnv($envUpdates);

            return redirect()->route('install.database');
        } catch (Exception $e) {
            $values = [
                'purchase_code' => $request->get("purchase_code"),
            ];
            return view('vendor.installer.steps.purchase-code', ['values' => $values, 'error' => $e->getMessage()]);
        }
    }

}
