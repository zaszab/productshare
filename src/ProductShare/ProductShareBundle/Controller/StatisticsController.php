<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use ProductShare\ProductShareBundle\Entity\Statistics;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Measuring the click-throughs and purchases of the feed products
 *
 * @package ProductShare\ProductShareBundle\Controller
 */
class StatisticsController extends Controller
{
    /**
     * Logs the clickthroughs on a product, and creates a cookie for
     *
     * @Route("/jump", defaults={"id"=0}, name="_export_jump")
     * @Route("/jump/{id}", name="_export_jump_id")
     * @Template()
     */
    public function jumpAction($id)
    {
        $prod = $this->getDoctrine()
            ->getRepository('ProductShareBundle:Product')
            ->findOneBy(array('product_id' => $id));

        $userHash = $this->logClick($prod);

        $this->setCookie($id, $userHash);

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
        foreach ($ids as $id) {
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

    /**
     * Lists the click and purchase statistics
     *
     * @Route("/productshare/secured/export/statistics", name="_export_statistics")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function statisticsAction()
    {
        $em = $this->getDoctrine()->getManager();

        $dql = 'SELECT s, COUNT(s.product) as num FROM ProductShareBundle:Statistics s
            WHERE s.type = 1
            GROUP BY s.product
            ORDER BY num DESC';
        $bought = $em->createQuery($dql)->getResult();
        $buyStats = array();
        foreach($bought as $b) {
            $buyStats[$b[0]->getProductId()] = $b['num'];
        }

        $dql = 'SELECT s, COUNT(s.product) as num FROM ProductShareBundle:Statistics s
            WHERE s.type = 0
            GROUP BY s.product
            ORDER BY num DESC';
        $clickStats = $em->createQuery($dql)->getResult();

        return array(
            'clickstats' => $clickStats,
            'buystats' => $buyStats,
        );

    }

    /**
     * Logs the clickthrough on a product
     *
     * @param $prod
     * @return string
     */
    private function logClick($prod)
    {
        $stat = new Statistics();
        $stat->setProduct($prod);
        $stat->setType(0);
        if (!$userHash = $this->getRequest()->cookies->get('userhash')) {
            $userHash = md5(uniqid(rand(), true));
        }
        $stat->setUserhash($userHash);

        $em = $this->getDoctrine()->getManager();
        $em->persist($stat);
        $em->flush();

        return $userHash;
    }

    /**
     * Sets a cookie in the browser, to be able to measure the purchase
     *
     * @param $id
     * @param $userHash
     */
    private function setCookie($id, $userHash)
    {
        $response = new Response();
        $response->headers->setCookie(new Cookie('userhash', $userHash, time() + 3600 * 24 * 365));

        $ids = array();
        if ($this->getRequest()->cookies->get('product_ids')) {
            $ids = unserialize($this->getRequest()->cookies->get('product_ids'));
        }
        $ids[$id] = $id;
        $response->headers->setCookie(new Cookie('product_ids', serialize($ids), time() + 3600 * 24 * 365));
        $response->send();
    }

}
