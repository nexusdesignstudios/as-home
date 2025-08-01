<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class PaymobPayoutService
{
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;
    private $environment;
    private $baseUrl;

    public function __construct()
    {
        $this->clientId = config('paymob.api_key');
        $this->clientSecret = config('paymob.api_secret');
        $this->username = config('paymob.username');
        $this->password = config('paymob.password');
        $this->environment = config('paymob.environment', 'staging');

        // Set base URL based on environment
        $this->baseUrl = $this->environment === 'production'
            ? 'https://payouts.paymobsolutions.com/api/secure'
            : 'https://stagingpayouts.paymobsolutions.com/api/secure';
    }

    /**
     * Generate or refresh OAuth 2.0 access token
     *
     * @param string|null $refreshToken
     * @return array
     * @throws RuntimeException
     */
    public function generateToken($refreshToken = null): array
    {
        try {
            $tokenData = [
                'grant_type' => $refreshToken ? 'refresh_token' : 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];

            if ($refreshToken) {
                $tokenData['refresh_token'] = $refreshToken;
            } else {
                $tokenData['username'] = $this->username;
                $tokenData['password'] = $this->password;
            }

            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($this->baseUrl . '/o/token/', $tokenData);

            if ($response->successful()) {
                $data = $response->json();

                // Cache the access token for 55 minutes (to be safe, as it expires in 60 minutes)
                Cache::put('paymob_payout_access_token', $data['access_token'], 55 * 60);
                Cache::put('paymob_payout_refresh_token', $data['refresh_token'], 24 * 60 * 60); // 24 hours

                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_in' => $data['expires_in'],
                    'token_type' => $data['token_type'],
                    'scope' => $data['scope'] ?? ''
                ];
            }

            throw new RuntimeException('Failed to generate token: ' . $response->body());
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to generate token: ' . $e->getMessage());
        }
    }

    /**
     * Get cached access token or generate new one
     *
     * @return string
     * @throws RuntimeException
     */
    private function getAccessToken(): string
    {
        $accessToken = Cache::get('paymob_payout_access_token');

        if (!$accessToken) {
            $refreshToken = Cache::get('paymob_payout_refresh_token');

            if ($refreshToken) {
                // Try to refresh the token
                try {
                    $result = $this->generateToken($refreshToken);
                    $accessToken = $result['access_token'];
                } catch (\Exception $e) {
                    // If refresh fails, generate new token
                    $result = $this->generateToken();
                    $accessToken = $result['access_token'];
                }
            } else {
                // Generate new token
                $result = $this->generateToken();
                $accessToken = $result['access_token'];
            }
        }

        return $accessToken;
    }

    /**
     * Process instant cashin (payout) to a recipient
     *
     * @param array $payoutData
     * @return array
     * @throws RuntimeException
     */
    public function processInstantCashin(array $payoutData): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Validate required fields based on issuer
            $this->validatePayoutData($payoutData);

            // Prepare request data
            $requestData = [
                'issuer' => $payoutData['issuer'],
                'amount' => (float) $payoutData['amount'],
            ];

            // Add conditional fields based on issuer
            if (in_array($payoutData['issuer'], ['vodafone', 'etisalat', 'orange', 'aman', 'bank_wallet'])) {
                $requestData['msisdn'] = $payoutData['msisdn'];
            }

            if ($payoutData['issuer'] === 'aman') {
                $requestData['first_name'] = $payoutData['first_name'] ?? '';
                $requestData['last_name'] = $payoutData['last_name'] ?? '';
                $requestData['email'] = $payoutData['email'] ?? '';
            }

            if ($payoutData['issuer'] === 'bank_card') {
                $requestData['bank_card_number'] = $payoutData['bank_card_number'];
                $requestData['bank_transaction_type'] = $payoutData['bank_transaction_type'];
                $requestData['bank_code'] = $payoutData['bank_code'];
                $requestData['full_name'] = $payoutData['full_name'];
            }

            // Add optional client reference ID
            if (isset($payoutData['client_reference_id'])) {
                $requestData['client_reference_id'] = $payoutData['client_reference_id'];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ])->post($this->baseUrl . '/disburse/', $requestData);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Paymob payout successful', [
                    'transaction_id' => $data['transaction_id'],
                    'issuer' => $data['issuer'],
                    'amount' => $data['amount'],
                    'status' => $data['disbursement_status']
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'issuer' => $data['issuer'],
                    'amount' => $data['amount'],
                    'disbursement_status' => $data['disbursement_status'],
                    'status_code' => $data['status_code'],
                    'status_description' => $data['status_description'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'paid' => $data['paid'] ?? null,
                    'aman_cashing_details' => $data['aman_cashing_details'] ?? null,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at']
                ];
            }

            $errorData = $response->json();
            throw new RuntimeException('Payout failed: ' . ($errorData['status_description'] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error('Paymob payout error', [
                'error' => $e->getMessage(),
                'payout_data' => $payoutData
            ]);
            throw new RuntimeException('Failed to process payout: ' . $e->getMessage());
        }
    }

    /**
     * Cancel Aman transaction
     *
     * @param string $transactionId
     * @return array
     * @throws RuntimeException
     */
    public function cancelAmanTransaction(string $transactionId): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ])->post($this->baseUrl . '/transaction/aman/cancel/', [
                'transaction_id' => $transactionId
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $data['transaction_id'],
                    'issuer' => $data['issuer'],
                    'amount' => $data['amount'],
                    'disbursement_status' => $data['disbursement_status'],
                    'status_code' => $data['status_code'],
                    'status_description' => $data['status_description'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'paid' => $data['paid'] ?? null,
                    'aman_cashing_details' => $data['aman_cashing_details'] ?? null,
                    'created_at' => $data['created_at'],
                    'updated_at' => $data['updated_at']
                ];
            }

            $errorData = $response->json();
            throw new RuntimeException('Failed to cancel Aman transaction: ' . ($errorData['status_description'] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error('Paymob cancel Aman transaction error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            throw new RuntimeException('Failed to cancel Aman transaction: ' . $e->getMessage());
        }
    }

    /**
     * Bulk transaction inquiry
     *
     * @param array $transactionIds
     * @param bool $isBankTransactions
     * @return array
     * @throws RuntimeException
     */
    public function bulkTransactionInquiry(array $transactionIds, bool $isBankTransactions = false): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $requestData = [
                'transactions_ids_list' => $transactionIds,
                'bank_transactions' => $isBankTransactions
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ])->get($this->baseUrl . '/transaction/inquire/', $requestData);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'count' => $data['count'],
                    'next' => $data['next'] ?? null,
                    'previous' => $data['previous'] ?? null,
                    'results' => $data['results']
                ];
            }

            $errorData = $response->json();
            throw new RuntimeException('Transaction inquiry failed: ' . ($errorData['status_description'] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error('Paymob transaction inquiry error', [
                'error' => $e->getMessage(),
                'transaction_ids' => $transactionIds
            ]);
            throw new RuntimeException('Failed to inquire transactions: ' . $e->getMessage());
        }
    }

    /**
     * Get user budget (balance)
     *
     * @return array
     * @throws RuntimeException
     */
    public function getUserBudget(): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ])->get($this->baseUrl . '/budget/inquire/');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'current_budget' => $data['current_budget'],
                    'status_description' => $data['status_description'] ?? null,
                    'status_code' => $data['status_code'] ?? null
                ];
            }

            $errorData = $response->json();
            throw new RuntimeException('Budget inquiry failed: ' . ($errorData['status_description'] ?? $response->body()));
        } catch (\Exception $e) {
            Log::error('Paymob budget inquiry error', [
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Failed to get user budget: ' . $e->getMessage());
        }
    }

    /**
     * Validate payout data based on issuer requirements
     *
     * @param array $payoutData
     * @throws RuntimeException
     */
    private function validatePayoutData(array $payoutData): void
    {
        $requiredFields = ['issuer', 'amount'];
        $validIssuers = ['vodafone', 'etisalat', 'orange', 'aman', 'bank_wallet', 'bank_card'];

        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($payoutData[$field])) {
                throw new RuntimeException("Missing required field: {$field}");
            }
        }

        // Validate issuer
        if (!in_array($payoutData['issuer'], $validIssuers)) {
            throw new RuntimeException("Invalid issuer. Must be one of: " . implode(', ', $validIssuers));
        }

        // Validate amount
        if (!is_numeric($payoutData['amount']) || $payoutData['amount'] <= 0) {
            throw new RuntimeException("Amount must be a positive number");
        }

        // Validate issuer-specific fields
        if (in_array($payoutData['issuer'], ['vodafone', 'etisalat', 'orange', 'aman', 'bank_wallet'])) {
            if (!isset($payoutData['msisdn'])) {
                throw new RuntimeException("MSISDN is required for issuer: {$payoutData['issuer']}");
            }

            // Validate MSISDN format (11 digits, +2 is added automatically)
            if (!preg_match('/^[0-9]{11}$/', $payoutData['msisdn'])) {
                throw new RuntimeException("MSISDN must be 11 digits (e.g., 01020304050)");
            }
        }

        if ($payoutData['issuer'] === 'aman') {
            if (!isset($payoutData['first_name']) || !isset($payoutData['last_name'])) {
                throw new RuntimeException("First name and last name are required for Aman issuer");
            }
        }

        if ($payoutData['issuer'] === 'bank_card') {
            $bankRequiredFields = ['bank_card_number', 'bank_transaction_type', 'bank_code', 'full_name'];
            foreach ($bankRequiredFields as $field) {
                if (!isset($payoutData[$field])) {
                    throw new RuntimeException("Missing required field for bank_card issuer: {$field}");
                }
            }

            $validBankTransactionTypes = ['salary', 'credit_card', 'prepaid_card', 'cash_transfer'];
            if (!in_array($payoutData['bank_transaction_type'], $validBankTransactionTypes)) {
                throw new RuntimeException("Invalid bank_transaction_type. Must be one of: " . implode(', ', $validBankTransactionTypes));
            }
        }
    }

    /**
     * Get bank codes mapping
     *
     * @return array
     */
    public function getBankCodes(): array
    {
        return [
            'AUB' => 'Ahli United Bank',
            'MIDB' => 'MIDBANK',
            'BDC' => 'Banque Du Caire',
            'HSBC' => 'HSBC Bank Egypt S.A.E',
            'CAE' => 'Credit Agricole Egypt S.A.E',
            'EGB' => 'Egyptian Gulf Bank',
            'UB' => 'The United Bank',
            'QNB' => 'Qatar National Bank Alahli',
            'ARAB' => 'Arab Bank PLC',
            'ENBD' => 'Emirates National Bank of Dubai',
            'ABK' => 'Al Ahli Bank of Kuwait – Egypt',
            'NBK' => 'National Bank of Kuwait – Egypt',
            'ABC' => 'Arab Banking Corporation - Egypt S.A.E',
            'FAB' => 'First Abu Dhabi Bank',
            'ADIB' => 'Abu Dhabi Islamic Bank – Egypt',
            'CIB' => 'Commercial International Bank - Egypt S.A.E',
            'HDB' => 'Housing And Development Bank',
            'MISR' => 'Banque Misr',
            'AAIB' => 'Arab African International Bank',
            'EALB' => 'Egyptian Arab Land Bank',
            'EDBE' => 'Export Development Bank of Egypt',
            'FAIB' => 'Faisal Islamic Bank of Egypt',
            'BLOM' => 'Blom Bank',
            'ADCB' => 'Abu Dhabi Commercial Bank – Egypt',
            'BOA' => 'Alex Bank Egypt',
            'SAIB' => 'Societe Arabe Internationale De Banque',
            'NBE' => 'National Bank of Egypt',
            'ABRK' => 'Al Baraka Bank Egypt B.S.C.',
            'POST' => 'Egypt Post',
            'NSB' => 'Nasser Social Bank',
            'IDB' => 'Industrial Development Bank',
            'SCB' => 'Suez Canal Bank',
            'MASH' => 'Mashreq Bank',
            'AIB' => 'Arab Investment Bank',
            'GASC' => 'General Authority For Supply Commodities',
            'ARIB' => 'Arab International Bank',
            'PDAC' => 'Agricultural Bank of Egypt',
            'NBG' => 'National Bank of Greece',
            'CBE' => 'Central Bank Of Egypt',
            'BBE' => 'ATTIJARIWAFA BANK Egypt'
        ];
    }

    /**
     * Get bank transaction types
     *
     * @return array
     */
    public function getBankTransactionTypes(): array
    {
        return [
            'salary' => 'For concurrent or repeated payments',
            'credit_card' => 'For credit cards payments',
            'prepaid_card' => 'For prepaid cards and Meeza cards payments',
            'cash_transfer' => 'For bank accounts, debit cards etc.'
        ];
    }
}
