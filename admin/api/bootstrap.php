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

$autoload->add('Controllers\\', __DIR__);
$autoload->register();

$config_path = __DIR__ . '/../../config/config.json';

$app = new Silex\Application();

if(file_exists($config_path)) {
    $app['config'] = json_decode(file_get_contents($config_path), true);
}

$app->register(new \Silex\Provider\SessionServiceProvider(), array(
    'session.storage.options' => array(
        'cookie_lifetime' => 72000,
//        'cookie_lifetime' => 3600 * 24 * 7
    )
));

$app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
        'dbname' => $app['config']['db']['dbname'],
        'user' => $app['config']['db']['user'],
        'password' => $app['config']['db']['password'],
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




