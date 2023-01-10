<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: "index")]
    public function index() : Response{
        return $this->redirectToRoute('main');
    }
    
    #[Route('/main', name: 'main')]
    public function main(): Response
    {
        return $this->render('main.html.twig',[
            
        ]);
    }

    #[Route('/panel', name: 'panel')]
    public function panel(): Response
    {
        return $this->render('panel.html.twig',[
            
        ]);
    }
}
