<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SignupController extends AbstractController
{
    public function index()
    {
        return $this->render('signup/index.html.twig', ['site_name' =>  $this->getParameter('app.site_name')]);
    }
}

