<?php

namespace Base;


class JsonRPC
{
    CONST External_Url = 'https://awery.katrina.ae/booking_cake/index.php';
//    CONST External_Url = 'http://demo-mamay.awery.com.ua/rpc/external/';
    public static function execute($method = '', $params = array())
    {
        global $app, $ch;

        $headers = array(
            'AWERY-ACM-SN: ' . base64_encode('DirectRpc'),
            'Content-Type: application/json',
            'Accept: application/json'
        );

        $payload = array(
            'method' => $method,
            'id' => mt_rand()
        );

        if (!empty($params)) {
            $payload['params'] = $params;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $app['config']['host']);  //if server needs to think this post came from elsewhere
        //curl_setopt($ch, CURLOPT_COOKIEJAR, AWERY_ACM_COOKIE);  //initiates cookie file if needed
        //curl_setopt($ch, CURLOPT_COOKIEFILE, AWERY_ACM_COOKIE);  // Uses cookies from previous session if exist
        curl_setopt($ch, CURLOPT_POST, 1);
        if($method != 'login' && isset($_COOKIE['ACM_PHPSESSID'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.base64_decode($_COOKIE['ACM_PHPSESSID']));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $http_body = substr($response, $header_size);
        if(preg_match("/PHPSESSID=(.*?)(?:;|\r\n)/", $header, $matches)){
            if($matches[1] && $matches[1] != ''){
                setcookie('ACM_PHPSESSID', base64_encode($matches[1]), strtotime('+100 days'), '/');
            }
        }
        $response = json_decode($http_body, true);
        if(isset($response['result'])) {
            $response = $response['result'];
        }

        if ($method == 'login') {
            if (isset($response['data_list']['id']) && (int)$response['data_list']['id'] > 0)
                setcookie('CUSTID', base64_encode($response['data_list']['id']), strtotime('+100 days'), '/');

        }

        return $response;
    }

    public static function loginRPC($method = 'login', $login = '', $passwd = '')
    {
        global $app, $ch;

        $payload = array(
            'serial' => $method === 'login' ? array('login' => $login, 'passwd' => $passwd) : array(),
            'method' => "loginExternal"
        );
        $headers = array(
            'AWERY-ACM-SN: ' . base64_encode('DirectRpc'),
            'Content-Type: application/json',
            'Accept: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $app['config']['host'] . '/system/' . $method . '.php');  //if server needs to think this post came from elsewhere
        curl_setopt($ch, CURLOPT_COOKIEJAR, AWERY_ACM_COOKIE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, AWERY_ACM_COOKIE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $output = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($output, true);

        if (is_null($response))
            $response = array('data' => $response);

        return $response['data'];
    }

}
