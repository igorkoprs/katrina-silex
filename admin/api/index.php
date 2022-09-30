<?php

require_once(__DIR__ . '/bootstrap.php');

use Base\JsonRPC;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app->match('/login', function (Request $request) use ($app) {
    $data = json_decode($request->getContent(), true);

    if (!isset($data['password'])) {
        return $app->json(array(
            'error' => 1,
            'message' => 'Received incorrect password',
            'data_list' => array('data' => $data)
        ), 400);
    }

    $sql = "SELECT * FROM `admin` WHERE `email` = ?";
    $account = $app['db']->fetchAssoc($sql, array($data['email']));

    if (!$account) {
        return $app->json(array(
            'error' => 2,
            'message' => 'Account with this ' . (!preg_match('/\@/', $data['email']) ? 'login' : 'email') . ' not found in DB',
            'data_list' => array()
        ), 400);
    }

    if (!password_verify($data['password'], $account['password']))
        return $app->json(array(
            'error' => 3,
            'message' => 'Wrong password',
            'data_list' => array()
        ), 400);

    $app['session']->set('account_id', $account['id']);
    unset($account['password']);

    return $app->json(array(
        'error' => 0,
        'message' => 'Authorized',
        'data_list' => array(
            'fullAccount' => $account
        )
    ));

})->bind('login');

$app->match('/logout', function () use ($app) {

    $app['session']->clear();
    $app['user'] = null;
    return $app->json(array('error' => 0, 'message' => 'Logged Out'));

})->bind('logout');

$app->match('/clearCategoriesCache', function () use ($app) {
    $cat_path = __DIR__ . '/../../cache/categories.json';

    if(file_exists($cat_path)) {
        unlink($cat_path);
    } else{
        return $app->json(array(
            'error' => 1,
            'message' => 'File not found'
        ), 400);
    }

    return $app->json(array('error' => 0, 'message' => 'Cache clear successfully'));
})->bind('logout');

$app->match('/registration', function (Request $request) use ($app) {
    $data = json_decode($request->getContent(), true);

    if (!isset($data['password']) || strlen($data['password']) < 3 || preg_match('/[^a-zA-Z0-9_]/', $data['password']))
        return $app->json(array(
            'error' => 1,
            'message' => 'Receive incorrect password'
        ));

    if (!isset($data['email']))
        return $app->json(array(
            'error' => 2,
            'message' => 'Receive incorrect email'
        ));

    if ($data['email'] == '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        return $app->json(array(
            'error' => 3,
            'message' => 'Not valid email'
        ));

    $sql = "SELECT * FROM `users` WHERE `email` = ?";
    $user_a = $app['db']->fetchAssoc($sql, array($data['email']));
    if ($user_a) {
        return $app->json(array(
            'error' => 5,
            'message' => 'User with this email already registered'
        ));
    } else {
        $app['db']->insert('users', array(
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'email' => $data['email'],
            'date' => date('Y-m-d H:i:s')
        ));

        return $app->json(array(
            'error' => 0,
            'message' => 'You registered successfully! Please login to system use your username and password'
        ));
    }

})->bind('registration');

$app->match('/isLogin', function () use ($app) {

    return $app->json(array(
        'error' => 0,
        'message' => 'Authorized',
        'data_list' => array(
            'fullAccount' => $app['user']
        )
    ));
})->bind('isLogin');

$app->match('/change_password', function (Request $request) use ($app) {
    $data = json_decode($request->getContent(), true);

    if (!isset($data['passwd']) || isset($data['passwd']) && strlen($data['passwd']) < 6) {
        return $app->json(array('error' => 1, 'message' => 'Not Valid Password!', 'data_list' => array('data' => $data)));
    }

    $hash = password_hash($data['passwd'], PASSWORD_BCRYPT);

    $app['db']->update('`admin`', array(
        'password' => $hash,
        'date' => date('Y-m-d H:i:s')
    ), array('id' => $app['user']['id']));

    return $app->json(array(
        'error' => 0,
        'message' => 'Changes Saved successfully',
        'data_list' => array(
            'fullAccount' => $app['user']
        )
    ));
})->bind('ChangePassword');

$app->match('/get_image/{id}', function ($id) use ($app) {
    $file = array_slice(array_diff(scandir(__DIR__.'/../../uploads/images/background/'.$id), array('..', '.')), 0);
    if ($file)
        $image = $file[0];
    $response = new  \Symfony\Component\HttpFoundation\Response();

    // Set headers
    $response->headers->set('Cache-Control', 'private');
    $data['ext'] = stristr($image, '.');

    $response->headers->set('Content-type', mime_content_type(__DIR__.'../../../uploads/images/background/'.$id.'/'.$image));
    $response->headers->set('Content-Disposition', 'inline; filename="' . $image . '";');
    $response->headers->set('Content-length', filesize(__DIR__.'/../../uploads/images/background/'.$id.'/'.$image));
    $response->sendHeaders();
    $response->setContent(file_get_contents(__DIR__.'../../../uploads/images/background/'.$id.'/'.$image));

    return $response;
})->bind('background');

$app->before(function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $app['user'] = null;
    $app['request'] = $request;
    if ($request->getMethod() == 'OPTIONS') {
        return $app->json(array(
            'error' => 0,
            'message' => 'Preflight request success'
        ));
    }

    if (!$app['session']->has('account_id')) {
        $app['session']->set('account_id', -1);
        $app['user'] = null;
    }

    $current_route = $request->attributes->get('_route');
    $anonymous_routes = array(
        'login',
        'forgotPassword',
        'resetPassword',
        'checkHash',
        'registration',
        'getAirports',
        'getCountries',
        'unSubscribe',
        'getAirports',
        'getActypes',
        'getCurrencies',
        'getCountries',
        'generateQRAuthBrowser',
        'checkQRAuthBrowser',
        'authQRDevice',
        'checkPasswordHash',
        'authQRDevice',
        'uploadImagePages',
        'uploadImageNews',
    );
    $route = $request->getRequestUri();

    if ($app['session']->get('account_id') >= 0) {
        $sql = 'SELECT * FROM `admin` WHERE `id` = ?';
        $user = $app['db']->fetchAssoc($sql, array($app['session']->get('account_id')));
        if ($user) {
            unset($user['password']);
            $app['user'] = $user;
        } else {
            $app['session']->clear();
            $app['user'] = false;
            return $app->json(array(
                'error' => 500,
                'message' => 'Error. Please try to login again'
            ));
        }
    }

    if (preg_match('/\/api/', $route)) {
        if ($app['user'] == false && !in_array($current_route, $anonymous_routes) && $current_route != 'email') {

            return $app->json(array(
                'error' => 401,
                'message' => 'Unauthorized',
                'data_list' => array()
            ));
        }
    }
});

$app->run();
