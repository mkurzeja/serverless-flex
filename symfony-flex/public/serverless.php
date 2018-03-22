<?php

use App\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
$_ENV['AWS_LAMBDA'] = true;
require __DIR__.'/../vendor/autoload.php';

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__.'/../.env.dist', __DIR__.'/../.env');
}

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) $_SERVER['APP_DEBUG'] ?? ('prod' !== $env);

if ($debug) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$_REQUEST['event'] = json_decode($argv[1], true) ?: [];
$_REQUEST['context'] = json_decode($argv[2], true) ?: [];

$kernel = new Kernel($env, $debug);
parse_str($_REQUEST['event']['body'], $_POST);
$request = new Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, $_REQUEST['event']['body']);
$request->headers->add($_REQUEST['event']['headers']);
$response = $kernel->handle($request);


$headers = $response->headers->all();

foreach ($headers as $key => $values) {
    $headersFlat[$key] = array_pop($values);
}

printf(json_encode([
    'statusCode' => $response->getStatusCode(),
    'body' => $response->getContent(),
    'headers' => $headersFlat,
]));

$kernel->terminate($request, $response);
