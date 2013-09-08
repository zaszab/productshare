<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/productshare/export")
 */
class ExportController extends Controller
{
    /**
     * @var array
     */
    protected $availableExports = array('argep', 'arukereso', 'olcsobbat');

    /**
     * @Route("/", name="_productshare_export")
     */
    public function indexAction()
    {

    }

    /**
     * @Route("/{name}")
     */
    public function exportAction($name)
    {
        if (!in_array($name, $this->availableExports)) {
            throw new NotFoundHttpException("Export not found");
        }

        $em = $this->getDoctrine()->getManager();
        $qb = $this->getDoctrine()->getRepository('ProductShareBundle:ExportRule')->createQueryBuilder('e');

        $query = $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('e.export', "''"),
                    $qb->expr()->eq('e.export', ':export')
                )
            )
            ->andWhere('e.published = 1')
            ->setParameter('export', $name);
        $exportRules = $query->getQuery()->getResult();

        $dql = 'SELECT p FROM ProductShareBundle:Product p
            JOIN p.category c
            JOIN p.manufacturer m
            WHERE 1 = 1';
        foreach ($exportRules as $er) {
            $dql .= ' AND ' . $er->getRuleSql();
        }
        $query = $em->createQuery($dql);
        $products = $query->getResult();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'ProductShareBundle:Export:' . $name . '.xml.twig',
            array(
                'products' => $products,
                'jump_url' => $this->generateUrl('_export_jump', array(), true)
            ),
            $response
        );
    }
}