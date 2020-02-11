# API Money Service

[API Money](https://www.api-money.com/)  
[Dashboard test API Money](https://test-emoney-services.w-ha.com/)  
[Doc API Money](https://www.api-money.com/docs/)  

## Description
A simple classe to use API Money with CURL because their shitty service doesn't provide any SDK.

## Installation
```bash
API_MONEY_PUBLIC=publicKey
API_MONEY_PRIVATE=privateKey
API_MONEY_TEST_ENVIRONMENT=https://test-emoney-services.w-ha.com/api
API_MONEY_TEST_PRODUCTION=prodUrl
API_MONEY_PROD=false
API_MONEY_PARTNER_ID=partnerId
```

Javascript library for WebCashIn
```php
@if (env('API_MONEY_PROD'))
    <script src="https://secure-cb.w-ha.com/secure-node-resources/js/secure-cb.min.js"></script>
@else
    <script src="https://preprod-cb.w-ha.com/secure-node-resources/js/secure-cb.min.js"></script>
@endif
```

## Exemple

```php
use App\Services\ApiConnectService;

$apiMoney = new ApiConnectService();

try {
    $userApiMoney = $apiMoney->getAccount($id);
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'errors' => json_decode($e->getMessage()) ? json_decode($e->getMessage()) : $e->getMessage(),
    ]);
}
```
## Method list

```php
createStandardAccount($user);
createBusinessAccount($user)
getAccounts($per_page = 1, $page = 1, $type = null)
getAccount($id)
getAccountLimit($id)

getTransaction($id)

createWallet($id, $type = "EMONEY", $currency = "EUR")
getWallet($id)
listWallet($account_type = null, $account_id = null, $per_page = 20, $page = 1) 

initWebCashIn($ref, $amount, $fees, $wallet, $walletFees, $return_url, $lang = "fr")
initBankwireCashIn($ref, $amount, $fees, $wallet, $walletFees)
confirmWebCashIn($id)
cancelWebCashIn($id)

addBankAccount($user, $iban, $bic, $holder)
getBankAccount($id)
deleteBankAccount($id)

cashOut($ref, $amount, $fees, $wallet, $walletFees, $bankAccount)

makeTransfer($ref, $amount, $fees, $senderWallet, $receiverWallet, $feesWallet = null)

requestKycValidation($id, $kyc_level = "LEVEL_2")
getLastKycValidation($id)
createDocument($id, $type, array $file)
getDocument($id, $type)
getDocuments($id)

```

## License
Me
