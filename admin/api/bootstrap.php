<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_lifetime', 72000);
//ini_set('session.cookie_lifetime', 3600 * 24 * 7);
ini_set('session.gc_maxlifetime', 36000);

header("Access-Control-Allow-Origin: http://localhost:4200");
header('Access-Control-Allow-Credentials:true');
header("Access-Control-Allow-Headers:Origin,X-Requested-With,Content-Type,Accept,Authorization,X-Custom-Header,Content-Range,Content-Disposition,Content-Description");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

$autoload = require_once '/web/webautoload/vendor/autoload.php';
use \Silex\Provider\SessionServiceProvider;

$autoload->add('Controllers\\', __DIR__);
$autoload->register();
$app = new \Silex\Application();

$config_path = __DIR__ . '/../../../config/config.json';
if(file_exists($config_path)) {
    $app['config_katrina'] = json_decode(file_get_contents($config_path), true)['katrina'];
}

$app['checkExistField'] = $app->protect(function ($key, $field) use ($app) {
    return (isset($app['config_katrina']) && isset($app['config_katrina'][$key]) && isset($app['config_katrina'][$key][$field])) ? true : false;
});

$db_config = array(
    'host' => $app['checkExistField']('mysql', 'host') ? $app['config_katrina']['mysql']['host'] : '127.0.0.1',
    'port' => $app['checkExistField']('mysql','port') ? $app['config_katrina']['mysql']['port'] :'3306',
    'dbname' => $app['checkExistField']('mysql','dbname') ? $app['config_katrina']['mysql']['dbname'] : 'newkatrinasite',
    'user' => $app['checkExistField']('mysql','user') ? $app['config_katrina']['mysql']['user'] : 'newkatrinasite',
    'pass' => $app['checkExistField']('mysql','pass') ? $app['config_katrina']['mysql']['pass']  : 'ktA5hdk8M'
);

$redis_config = array(
    'host' => 'tcp://'.($app['checkExistField']('redis', 'host') ? $app['config_katrina']['redis']['host'] : '127.0.0.1'),
    'port' => $app['checkExistField']('redis','port') ? $app['config_katrina']['redis']['port'] :'6379',
);

$lifeTime = 60 * 60 * 24 * 30;
ini_set("session.save_handler", "redis");
ini_set("session.save_path", $redis_config['host']. ":" .$redis_config['port'] . "?prefix=katrina-" . md5('https://katrina.ae'));
ini_set("session.gc_probability", "1");
ini_set("session.gc_divisor", "100");
ini_set("session.gc_maxlifetime", $lifeTime);
ini_set("session.cookie_lifetime", $lifeTime);

$app->register(new SessionServiceProvider());
$app['session.storage.options'] = array('cookie_lifetime' => $lifeTime);
$app['session.storage.handler'] = null;
$app['session']->start();

$app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => $db_config['host'],
        'port' => $db_config['port'],
        'dbname' => $db_config['dbname'],
        'user' => $db_config['user'],
        'password' => $db_config['pass'],
        'charset' => 'utf8'
    )
));

$app['path_img'] = __DIR__ . '/../../uploads';

$app->register(new \Silex\Provider\SwiftmailerServiceProvider());

use Controllers\PagesControllerProvider;
use \Controllers\NewsControllerProvider;
use \Controllers\TranslationsControllerProvider;

$app['debug'] = true;

$app->mount('/pages', new PagesControllerProvider());
$app->mount('/news', new NewsControllerProvider());
$app->mount('/translations', new TranslationsControllerProvider());




