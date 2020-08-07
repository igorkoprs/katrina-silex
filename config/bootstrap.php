<?php

use \Silex\Provider\TwigServiceProvider;
use \Silex\Provider\SessionServiceProvider;
use \Silex\Provider\DoctrineServiceProvider;

$Lifetime = 60 * 60 * 24 * 30;
$DirectoryPath = '/tmp';
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('session.save_path', $DirectoryPath);
ini_set('session.gc_maxlifetime', $Lifetime);
ini_set('session.gc_divisor', '1');
ini_set('session.gc_probability', '1');
ini_set('session.cookie_lifetime', '0');
require_once(__DIR__ . '/../classes/Base.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../classes/JsonRPC.php');
$config_path = __DIR__ . '/config.json';


$autoload = require_once '/web/webautoload/vendor/autoload.php';
$autoload->add('App\\', __DIR__ . '/../');
$autoload->register();

$app = new \Silex\Application();
$app['debug'] = true;

if(file_exists($config_path)) {
    $app['config'] = json_decode(file_get_contents($config_path), true);
}

use Base\Base;
use Base\Auth;

$app['product_link'] = 'https://awery.katrina.ae/system/downloads/cake_product_file.php?file_id=';

$app->register(new DoctrineServiceProvider());
$app['db.options'] = array(
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => $app['config']['db']['dbname'],
    'user' => $app['config']['db']['user'],
    'password' => $app['config']['db']['password'],
    'charset' => 'utf8',
);

$app['path_img'] = __DIR__ . '/../uploads';

$app->register(new SessionServiceProvider());
$app['session.storage.options'] = array('cookie_lifetime' => $Lifetime);
$app['session']->start();

$base = new Base($app);
$auth = new Auth($app);

$app->register(new TwigServiceProvider(), array('twig.path' => __DIR__ . '/../templates'));

$app['transport'] = (new \Swift_SmtpTransport('email-smtp.eu-west-1.amazonaws.com', 25, 'tls'))
    ->setUsername('AKIAJ7MIULTY4QS2MY2Q')
    ->setPassword('BGuGDGSwyBwdKv/s0LPB1ClceDGhTnRG9A57BKu4rdmc')
    ->setAuthMode('login');

define('AWERY_ACM_COOKIE', '/tmp/katrina_test_cookie_curl');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin:' . $_SERVER['HTTP_HOST']);
}

function checkEmail($email)
{
    $result = exec('/usr/local/bin/checkmail.pl ' . $email);
    return $result == 'OK';
}

header('Access-Control-Allow-Credentials:true');
header('Access-Control-Allow-Headers:Origin,X-Requested-With,Content-Type,Accept,Authorization,X-Custom-Header,Content-Range,Content-Disposition,Content-Description');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');