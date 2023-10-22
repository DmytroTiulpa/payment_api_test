<?php

/**
 * Класс для взаимодействия с платежным шлюзом.
 *
 * @category Test
 * @package  Пакет, к которому относится класс
 * @author   Ваше имя или имя автора
 * @license  Лицензия, которая применяется к классу
 * @link     Ссылка на дополнительные ресурсы или документацию
 */
class PaymentGateway
{
    private $_paymentUrl;
    private $_clientKey;
    private $_clientPass;
    private $_notificationUrl;

    /**
     * @param $_paymentUrl
     * @param $_clientKey
     * @param $_clientPass
     * @param $_notificationUrl
     */
    public function __construct($_paymentUrl, $_clientKey, $_clientPass, $_notificationUrl)
    {
        $this->paymentUrl = $_paymentUrl;
        $this->clientKey = $_clientKey;
        $this->clientPass = $_clientPass;
        $this->notificationUrl = $_notificationUrl;
    }

    /**
     * @param  $email
     * @param  $_clientPass
     * @param  $cardNumber
     * @return string
     */
    public function calculateHash($email, $_clientPass, $cardNumber)
    {
        $reversedEmail = strrev($email); // перевернуть
        $first6Digits = substr($cardNumber, 0, 6);  // первые 6 цифр карты
        $last4Digits = substr($cardNumber, -4);            // последние 4 цифры карты
        $dataToHash = strtoupper($reversedEmail . $_clientPass . strrev($first6Digits . $last4Digits));
        return md5($dataToHash);
    }

    /**
     * @param  $transactionData
     * @return string
     */
    public function makeSale($transactionData)
    {
        // Prepare the data for the SALE request
        $requestData = [
            'action' => $transactionData['action'],
            'client_key' => $transactionData['client_key'],
            'order_id' => $transactionData['order_id'],
            'order_amount' => $transactionData['order_amount'],
            'order_currency' => $transactionData['order_currency'],
            'order_description' => $transactionData['order_description'],
            'card_number' => $transactionData['card_number'],
            'card_exp_month' => $transactionData['card_exp_month'],
            'card_exp_year' => $transactionData['card_exp_year'],
            'card_cvv2' => $transactionData['card_cvv2'],
            'payer_first_name' => $transactionData['payer_first_name'],
            'payer_last_name' => $transactionData['payer_last_name'],
            'payer_address' => $transactionData['payer_address'],
            'payer_country' => $transactionData['payer_country'],
            'payer_city' => $transactionData['payer_city'],
            'payer_zip' => $transactionData['payer_zip'],
            'payer_email' => $transactionData['payer_email'],
            'payer_phone' => $transactionData['payer_phone'],
            'payer_ip' => $transactionData['payer_ip'],
            'term_url_3ds' => $transactionData['term_url_3ds'],
            'hash' => $transactionData['hash']
        ];

        try {
            // Send a POST request to the Payment Platform
            $response = $this->sendRequest($requestData);

            // Check for errors in the response
            if (json_decode($response, false)->result === 'SUCCESS') {
                return 'Transaction successful' . $response;
            }

            throw new Exception('Transaction failed: ' . $response);

        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

    }

    /**
     * @param  $requestData
     * @return bool|string|void
     */
    private function sendRequest($requestData)
    {
        // Simulate sending a POST request
        $ch = curl_init($this->paymentUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // disable certificate verification procedure
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            echo 'cURL error: ' . $error;
            die();
        }

        curl_close($ch);
        return $response;
    }
}

// Example usage:
$_paymentUrl = 'https://dev-api.rafinita.com/post';
$_clientKey = '5b6492f0-f8f5-11ea-976a-0242c0a85007';
$_clientPass = 'd0ec0beca8a3c30652746925d5380cf3';
$clientEmail = 'dmytrotiulpa@gmail.com';
$_notificationUrl = 'your_notification_url_here';

$paymentGateway = new PaymentGateway($_paymentUrl, $_clientKey, $_clientPass, $_notificationUrl);

$hash = $paymentGateway->calculateHash($clientEmail, $_clientPass, '4111111111111111');

//echo '$_clientKey > '.$_clientKey.'<br>';
//echo '$_clientPass > '.$_clientPass.'<br>';
//echo '$hash > '.$hash.'<br>';

$transactionData = array(
    'action' => 'SALE',
    'client_key' => $_clientKey,
    'order_id' => 'ORDER-93333',
    'order_amount' => '1.99',
    'order_currency' => 'USD',
    'order_description' => 'Product',
    'card_number' => '4111111111111111',
    'card_exp_month' => '01',
    'card_exp_year' => '2025',
    'card_cvv2' => '000',
    'payer_first_name' => 'John',
    'payer_last_name' => 'Doe',
    'payer_address' => 'Big street',
    'payer_country' => 'US',
    'payer_state' => 'CA',
    'payer_city' => 'City',
    'payer_zip' => '123456',
    'payer_email' => $clientEmail,
    'payer_phone' => '199999999',
    'payer_ip' => '123.123.123.123',
    'term_url_3ds' => 'http://client.site.com/return.php',
    'hash' => $hash
);

$result = $paymentGateway->makeSale($transactionData);

echo $result;