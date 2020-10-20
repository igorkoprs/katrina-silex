<?php

namespace Base;


class JsonRPC
{
    CONST External_Url = 'https://awery.katrina.ae/rpc/external/';
//    CONST External_Url = 'https://demo-mamay.awery.com.ua/rpc/external/';
    public static function execute($method = '', $params = array())
    {
        global $app;

        $payload = array(
            'jsonrpc' => '2.0',
            'method' => $method,
            'id' => mt_rand()
        );

        if (!empty($params)) {
            $payload['params'] = $params;
        }

        $headers = array(
            'AWERY-ACM-REALM: katrina',
            'Content-Type: application/json',
            'Accept: application/json',
        );

        if (isset($_COOKIE['CUSTID'])) {
            $headers[] = 'AWERY-CUST-ID: ' . $_COOKIE['CUSTID'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIEFILE, AWERY_ACM_COOKIE);
        curl_setopt($ch, CURLOPT_URL, self::External_Url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $http_body = substr($response, $header_size);
        $response = json_decode($http_body, true);
        if (is_null($response))
            $response = array('result' => $http_body);

        curl_close($ch);

        if ($method == 'External_ProductBooking.login' || $method == 'External_Customers.getCustomerIdBySocial') {

            if (isset($response['result']['id']) && (int)$response['result']['id'] > 0)
                setcookie('CUSTID', base64_encode($response['result']['id']), strtotime('+100 days'), '/');

        }
        return $response['result'];
    }

    public static function loginRPC($method = 'login', $serial = array())
    {
        global $app;

        $payload = array(
            'serial' => $serial
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_COOKIEJAR, AWERY_ACM_COOKIE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, AWERY_ACM_COOKIE);
        curl_setopt($ch, CURLOPT_URL, $app['config']['host'] . '/system/' . $method . '.php');
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'AWERY-ACM-REALM: ' . $app['config']['acmRealm'],
                'AWERY-ACM-SN: ' . base64_encode('awery_external_json_rpc'),
                'Content-Type: application/json',
                'Accept: application/json'
            )
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $http_body = substr($response, $header_size);
        $response = json_decode($http_body, true);


        if (is_null($response))
            $response = array('data' => $http_body);

        return $response['data'];
    }

}
