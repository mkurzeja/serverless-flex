<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function index()
    {
        return new Response('Hi from Symfony!', Response::HTTP_OK);
    }

    public function hello(Request $request, LoggerInterface $logger)
    {
        $logger->info('Hello request', $_REQUEST);
        $logger->info('Hello POST', $_POST);

        return new Response('['.$request->getMethod().'] Hi '. $request->request->get('name', 'Anonymous'));
    }
}
