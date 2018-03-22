<?php

namespace App\Event\Listener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CreateJsonErrorResponse
{
    public function onKernelException(GetResponseForExceptionEvent $event) {
        $e = $event->getException();
        $response = new Response(json_encode(["error" => $e->getMessage()]), Response::HTTP_INTERNAL_SERVER_ERROR);
        if  ($e instanceof HttpExceptionInterface) {
            $response->setStatusCode($e->getStatusCode());
            $response->headers->add($e->getHeaders());
        }
        $event->setResponse($response);
        $event->stopPropagation();
    }
}
