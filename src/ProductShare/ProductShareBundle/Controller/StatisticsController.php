<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ProductShare\ProductShareBundle\Entity\Statistics;
use Symfony\Component\HttpFoundation\Cookie;

class StatisticsController extends Controller
{
    /**
     * @Route("/jump", defaults={"id"=0}, name="_export_jump")
     * @Route("/jump/{id}", name="_export_jump_id")
     * @Template()
     */
    public function indexAction($id)
    {
        $stat = new Statistics();

        $repositoryProd = $this->getDoctrine()->getRepository('ProductShareBundle:Product');
        $prod = $repositoryProd->find($id);
        $stat->setProduct($prod);
        $stat->setType(0);
        if (!$userHash = $this->getRequest()->cookies->get('userhash')) {
            $userHash = md5(uniqid(rand(), true));
        }
        $stat->setUserhash($userHash);

        $response = new Response();
        $response->headers->setCookie(new Cookie('userhash', $userHash, time() + 3600 * 24 * 365));

        $ids = array();
        if ($this->getRequest()->cookies->get('product_ids')) {
            $ids = unserialize($this->getRequest()->cookies->get('product_ids'));
        }
        $ids[$id] = $id;
        $response->headers->setCookie(new Cookie('product_ids', serialize($ids), time() + 3600 * 24 * 365));
        $response->send();

        $em = $this->getDoctrine()->getManager();
        $em->persist($stat);
        $em->flush();

        $prod = $this->getDoctrine()
            ->getRepository('ProductShareBundle:Product')
            ->findOneBy(array('product_id' => $id));
        return $this->redirect($prod->getProductLink());
    }

    /**
     * @Route("/buy", name="_buy")
     * @Template()
     */
    public function buyAction()
    {
        $ids = $this->getRequest()->get('id');
        if (!$this->getRequest()->cookies->get('product_ids')) {
            exit;
        }
        $clickedIds = unserialize($this->getRequest()->cookies->get('product_ids'));
        foreach($ids as $id) {
            if (!isset($clickedIds[$id])) {
                continue;
            }
            unset($clickedIds[$id]);

            $response = new Response();
            $response->headers->setCookie(new Cookie('product_ids', serialize($clickedIds), time() + 3600 * 24 * 365));
            $response->send();

            $stat = new Statistics();
            $stat->setProductId($id);
            $stat->setType(1);
            if (!$userHash = $this->getRequest()->cookies->get('userhash')) {
                $userHash = '-unknown-';
            }
            $stat->setUserhash($userHash);

            $em = $this->getDoctrine()->getManager();
            $em->persist($stat);
            $em->flush();
        }
        exit;
    }

}
