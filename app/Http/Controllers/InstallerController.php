<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use dacoto\EnvSet\Facades\EnvSet;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

    public function keysIndex() {
        // Check if migrations have been run
        try {
            return view('vendor.installer.steps.keys');
        } catch (\Exception $e) {
            return redirect()->route('install.migrations')->with('error', $e->getMessage());
        }
    }

    public function keysPost(Request $request) {
        try {
            $appUrl = $request->input('app_url');
            
            if (empty($appUrl)) {
                return redirect()->back()->withErrors(['app_url' => 'App URL is required']);
            }

            // Generate APP_KEY if it doesn't exist or is empty
            $envPath = base_path('.env');
            $needsKeyGeneration = false;
            
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                // Check if APP_KEY exists and has a valid value
                if (preg_match('/^APP_KEY=(.*)$/m', $envContent, $matches)) {
                    $appKey = trim($matches[1] ?? '');
                    // If APP_KEY is empty or doesn't start with base64:, generate a new one
                    if (empty($appKey) || strpos($appKey, 'base64:') !== 0) {
                        $needsKeyGeneration = true;
                    }
                } else {
                    // APP_KEY doesn't exist in .env
                    $needsKeyGeneration = true;
                }
            } else {
                // .env doesn't exist, generate key
                $needsKeyGeneration = true;
            }
            
            if ($needsKeyGeneration) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Create storage link
            try {
                $linkPath = public_path('storage');
                $targetPath = storage_path('app/public');
                
                if (!File::exists($linkPath)) {
                    if (PHP_OS_FAMILY === 'Windows') {
                        // Windows: Create junction or copy
                        if (is_dir($targetPath)) {
                            exec("mklink /J \"$linkPath\" \"$targetPath\"", $output, $return);
                            if ($return !== 0) {
                                // Fallback: copy directory structure
                                if (!File::exists($linkPath)) {
                                    File::makeDirectory($linkPath, 0755, true);
                                }
                            }
                        }
                    } else {
                        // Unix/Linux: Create symlink
                        if (File::exists($linkPath)) {
                            File::delete($linkPath);
                        }
                        symlink($targetPath, $linkPath);
                    }
                }
            } catch (\Exception $e) {
                // Continue even if storage link fails
            }

            // Update APP_URL in .env
            try {
                $envUpdates = [
                    'APP_URL' => $appUrl,
                ];
                updateEnv($envUpdates);
            } catch (\Exception $e) {
                try {
                    EnvSet::setKey('APP_URL', $appUrl);
                    EnvSet::save();
                } catch (\Exception $e2) {
                    // If both fail, continue anyway
                }
            }

            return redirect()->route('install.finish');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function finish() {
        $base = config('app.url', url('/'));
        $login = url(config('installer.login', '/'));
        
        return view('vendor.installer.steps.finish', [
            'base' => $base,
            'login' => $login
        ]);
    }

}
