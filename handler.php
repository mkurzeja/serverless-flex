<?php

$loader = require __DIR__ . '/vendor/autoload.php';

use Raines\Serverless\Context;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

// Get event data and context object
$event = json_decode($argv[1], true) ?: [];
$logger = new Logger('handler');
$logger->pushHandler(new ErrorLogHandler());
$fd = fopen('php://fd/3', 'r+');
$context = new Context($logger, $argv[2], $fd);

// Get the handler service and execute

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/hello', 'sayHello');
});
$logger->info('Got new request', $event);
// Fetch method and URI from somewhere
$httpMethod = $event['httpMethod'];
$uri = $event['path'];

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $logger->error('404 Not found', $event);
        printf(json_encode([
            'statusCode' => 404,
            'body' => 'Not found',
        ]));
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $logger->info('Running '.$handler);
        call_user_func_array($handler, $vars);
        break;
}

function sayHello(){
  printf(json_encode([
      'statusCode' => 200,
      'body' => 'Hello',
  ]));
}
