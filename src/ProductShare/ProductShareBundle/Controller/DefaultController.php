<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="_productshare")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/{name}", name="_productshare_hello")
     * @Template()
     */
    public function helloAction($name)
    {
        return $this->render('ProductShareBundle:Default:hello.html.twig', array('name' => $name));
    }

}
