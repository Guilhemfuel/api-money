<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ApiConnectService
{
    private $baseUrl;
    private $partner;
    private $auth;

    public function __construct()
    {
        $this->baseUrl = env('API_MONEY_PROD') ? env('API_MONEY_TEST_PRODUCTION') : env('API_MONEY_TEST_ENVIRONMENT');
        $this->partner = env('API_MONEY_PARTNER_ID');
        $this->auth();
    }

    private function auth($requestBody = null) {
        $publicKey = env('API_MONEY_PUBLIC');
        $privateKey = env('API_MONEY_PRIVATE');
        $version = 1;
        $timestamp = Carbon::now()->getPreciseTimestamp(3);

        $StringToConvert = $publicKey . ':' . $timestamp . ':' . $version . ':' . $requestBody;
        $StringToSign = $publicKey . ':' . $timestamp . ':' . $version . ':';
        $Sign = hash_hmac('sha256', $StringToConvert, $privateKey);
        $auth = $StringToSign . $Sign;

        $headers = array();
        $headers[] = 'Authorization: AUTH '. $auth;
        $headers[] = 'Content-Type: application/json';

        $this->auth = $headers;
    }

    private function getCurl($url, $customRequest = "GET") {
        $this->auth();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->auth);

        $response = curl_exec($ch);
        curl_close($ch);

        if (isset($response->code)) {
            throw new \Exception(json_encode($response));
        }

        return json_decode($response);
    }

    private function postCurl($url, $data, $customRequest = "POST") {
        $data = json_encode($data);
        $this->auth($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->auth);

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response);

        if (isset($response->code)) {
            throw new \Exception(json_encode($response));
        }

        return $response;
    }

    public function createStandardAccount(User $user)
    {
        $url = '/accounts/standard';

        $data = [
            "subscriber" => [
                "lastname" => $user->lastname,
                "firstname" => $user->firstname,
                "birthdate" => $user->birthdate,
                "birth_country" => $user->birth_country,
                "birth_city" => $user->birth_city,
                "nationality" => $user->nationality,
                "citizen_us" => false,
                "fiscal_us" => false,
                "fiscal_out_france" => false
            ],
            "address" => [
                "label1" => $user->address,
                "zip_code" => $user->zip_code,
                "city" => $user->city_address,
                "country" => $user->country_address,
            ],
            "email" => $user->email,
            "tag" => "",
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function createBusinessAccount(User $user)
    {
        $url = '/accounts/business';

        $data = [
            "name" => $user->name_association,
            "business_type" => "ASSOCIATION",
            "email" => $user->email,
            "registration_number" => $user->registration_number,
            "phone_number" => $user->phone_number,
            "representative" => [
                "lastname" => $user->lastname,
                "firstname" => $user->firstname,
                "birthdate" => $user->birthdate,
                "nationality" => $user->nationality,
            ],
            "address" => [
                "label1" => $user->address,
                "zip_code" => $user->zip_code,
                "city" => $user->city_address,
                "country" => $user->country_address,
            ],
            "tag" => "account_type2",
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function getAccounts($per_page = 1, $page = 1, $type = null) {
        $url = '/accounts?per_page=' . $per_page . '&page=' . $page;

        if ($type) {
            $url = $url . '&type=' . $type;
        }

        $response = $this->getCurl($url);
        return $response;
    }

    public function getAccount($id) {
        $url = '/accounts/'.$id;

        $response = $this->getCurl($url);
        return $response;
    }

    public function getAccountLimit($id) {
        $url = '/accounts/'. $id .'/limits';

        $response = $this->getCurl($url);
        return $response;
    }

    public function getTransaction($id) {
        $url = '/transactions/'.$id;

        $response = $this->getCurl($url);
        return $response;
    }

    public function createWallet($id, $type = "EMONEY", $currency = "EUR") {
        $url = '/wallets';

        $data = [
            'account_id' => $id,
            'type' => $type,
            'currency' => $currency
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function getWallet($id) {
        $url = '/wallets/'.$id;

        $response = $this->getCurl($url);
        return $response;
    }

    public function listWallet($account_type = null, $account_id = null, $per_page = 20, $page = 1) {
        $url = "/wallets?per_page=$per_page&page=$page";

        if ($account_type) {
            $url = $url . '&account_type=' . $account_type;
        }

        if ($account_id) {
            $url = $url . '&account_id=' . $account_id;
        }

        $response = $this->getCurl($url);
        return $response;
    }

    public function initWebCashIn($ref, $amount, $fees, $wallet, $walletFees, $return_url, $lang = "fr") {
        $url = '/cash-in/creditcards/init';

        $data = [
            'partner_ref' => $ref,
            'tag' => "",
            'receiver_wallet_id' => $wallet,
            'fees_wallet_id' => $walletFees,
            'amount' => $amount,
            'fees' => $fees,
            'return_url' => $return_url,
            'lang' => $lang,
            'auth_timeout_delay' => 86400,
            'save_card_option' => "no",
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function initBankwireCashIn($ref, $amount, $fees, $wallet, $walletFees) {
        $url = '/cash-in/bankwire/authorize';

        $data = [
            'partner_ref' => $ref,
            'tag' => "",
            'receiver_wallet_id' => $wallet,
            'fees_wallet_id' => $walletFees,
            'amount' => $amount,
            'fees' => $fees,
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function confirmWebCashIn($id) {
        $url = '/cash-in/'.$id;

        $response = $this->getCurl($url, "PUT");
        return $response;
    }

    public function cancelWebCashIn($id) {
        $url = '/cash-in/'.$id;

        $response = $this->getCurl($url, "DELETE");
        return $response;
    }

    public function addBankAccount($user, $iban, $bic, $holder) {
        $url = '/bankaccounts';

        $data = [
            'account_id' => $user,
            'tag' => "",
            'number' => $iban,
            'bic' => $bic,
            'holder_name' => $holder
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function getBankAccount($id) {
        $url = '/bankaccounts/'.$id;

        $response = $this->getCurl($url);
        return $response;
    }

    public function deleteBankAccount($id) {
        $url = '/bankaccounts/'.$id;

        $response = $this->getCurl($url, "DELETE");
        return $response;
    }

    public function cashOut($ref, $amount, $fees, $wallet, $walletFees, $bankAccount) {
        $url = '/cash-out';

        $data = [
            'partner_ref' => $ref,
            'tag' => "",
            'sender_wallet_id' => $wallet,
            'fees_wallet_id' => $walletFees,
            'amount' => $amount,
            'fees' => $fees,
            'bankaccount_id' => $bankAccount
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }

    public function requestKycValidation($id, $kyc_level = "LEVEL_2") {
        $url = '/accounts/'. $id .'/validations/'. $kyc_level .'/submit';

        $response = $this->getCurl($url, "PUT");
        return $response;
    }

    public function getLastKycValidation($id) {
        $url = '/accounts/'. $id .'/validations/last';

        $response = $this->getCurl($url);
        return $response;
    }

    public function createDocument($id, $type, array $file) {
        $url = '/accounts/'. $id .'/documents';

        $data = [
            [
                "type" => $type,
                "files" => [
                    [
                        "file_name" => $file['name'],
                        "content" => $file['content'],
                    ],
                ]
            ]
        ];

        $response = $this->postCurl($url, $data, "PUT");
        return $response;
    }

    public function getDocument($id, $type) {
        $url = '/accounts/'. $id .'/documents/' . $type;

        $response = $this->getCurl($url);
        return $response;
    }

    public function getDocuments($id) {
        $url = '/accounts/'. $id .'/documents/';

        $response = $this->getCurl($url);
        return $response;
    }

    public function makeTransfer($ref, $amount, $fees, $senderWallet, $receiverWallet, $feesWallet = null) {
        $url = '/transfers';

        $data = [
            'partner_ref' => $ref,
            'tag' => "",
            'sender_wallet_id' => $senderWallet,
            'receiver_wallet_id' => $receiverWallet,
            'fees_wallet_id' => $feesWallet,
            'amount' => $amount,
            'fees' => $fees
        ];

        $response = $this->postCurl($url, $data);
        return $response;
    }
}