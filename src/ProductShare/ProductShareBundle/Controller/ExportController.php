<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Response;
use ProductShare\ProductShareBundle\Entity\ExportRule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Responsible for creating the feeds
 *
 * @package ProductShare\ProductShareBundle\Controller
 */
class ExportController extends Controller
{
    /**
     * Currently compatible with 3 Hungarian comparison sites
     * @var array
     */
    protected $availableExports = array('argep', 'arukereso', 'olcsobbat');

    /**
     * @Route("/productshare/export", name="_productshare_export")
     */
    public function indexAction()
    {

    }

    /**
     * Exports a feed for the selected site
     *
     * @Route("/productshare/export/{name}")
     * @param $name Export name
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function exportAction($name)
    {
        if (!in_array($name, $this->availableExports)) {
            throw new NotFoundHttpException("Export not found");
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'ProductShareBundle:Export:' . $name . '.xml.twig',
            array(
                'products' => $this->getProductsForExport($name),
                'jump_url' => $this->generateUrl('_export_jump', array(), true)
            ),
            $response
        );
    }

    /**
     * Lists the available exports
     *
     * @Route("/productshare/secured/export", name="_exports")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function exportsAction()
    {
        return array('exports' => $this->availableExports);
    }

    /**
     * Lists export rules
     *
     * @Route("/productshare/secured/export/management", name="_export_management")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function managementAction()
    {
        $rules = $this->getDoctrine()
            ->getRepository('ProductShareBundle:ExportRule')
            ->findAll();

        return array(
            'rules' => $rules
        );
    }

    /**
     * Adds or edits the export rule
     *
     * @Route("/productshare/secured/export/management/addrule", defaults={"id"=0}, name="_export_add_rule")
     * @Route("/productshare/secured/export/management/addrule/{id}", name="_productshare_secured_hello")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function addRuleAction($id)
    {
        if ($id) {
            $exportRule = $this->getDoctrine()
                ->getRepository('ProductShareBundle:ExportRule')
                ->find($id);
        } else {
            $exportRule = new ExportRule();
        }
        $em = $this->getDoctrine()->getManager();

        $fields = array_merge(
            $em->getClassMetadata('ProductShare\ProductShareBundle\Entity\Product')->getFieldNames(),
            $em->getClassMetadata('ProductShare\ProductShareBundle\Entity\Product')->getAssociationNames()
        );

        $exportChoices = array('' => 'All exports');
        foreach ($this->availableExports as $exp) {
            $exportChoices[$exp] = $exp;
        }

        $columns = array();
        foreach ($fields as $field) {
            $columns[$field] = $field;
        }

        $form = $this->createFormBuilder($exportRule)
            ->add('column', 'choice', array(
                    'choices' => $columns
                ))
            ->add('ruletype', 'choice', array(
                    'choices' => ExportRule::getTypes()
                ))
            ->add('export', 'choice', array(
                    'choices' => $exportChoices,
                    'required' => false
                ))
            ->add('filter', 'text')
            ->add('published', 'checkbox', array(
                    'label' => 'Publish',
                    'data' => (boolean) $exportRule->getPublished(),
                    'required' => false
                ))
            ->getForm();

        if ('POST' === $this->getRequest()->getMethod()) {
            $form->bind($this->getRequest());
            if ($form->isValid()) {
                $exportRule = $form->getData();
                $em->persist($exportRule);
                $em->flush();
                return $this->redirect($this->generateUrl('_export_management'));
            }
        }
        return array(
            'form' => $form->createView(),
            'id' => $id
        );
    }

    /**
     * Deletes the export rule
     *
     * @Route("/productshare/secured/export/management/deleterule/{id}", name="_export_delete_rule")
     * @Secure(roles="ROLE_ADMIN")
     */
    public function deleteRuleAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $exportRule = $this->getDoctrine()
            ->getRepository('ProductShareBundle:ExportRule')
            ->find($id);

        if ($exportRule) {
            $em->remove($exportRule);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('_export_management'));
    }

    /**
     * Fetches products to be exported
     *
     * @param $exportName Export name
     */
    protected function getProductsForExport($exportName)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $this->getDoctrine()->getRepository('ProductShareBundle:ExportRule')->createQueryBuilder('e');

        // Fetching the export rules for this export
        $query = $qb->where(
            $qb->expr()->orX(
                $qb->expr()->eq('e.export', "''"),
                $qb->expr()->eq('e.export', ':export')
            )
        )
            ->andWhere('e.published = 1')
            ->setParameter('export', $exportName);
        $exportRules = $query->getQuery()->getResult();

        // Fetching every product data according with the rules
        $dql = 'SELECT p FROM ProductShareBundle:Product p
            JOIN p.category c
            JOIN p.manufacturer m
            WHERE 1 = 1';
        foreach ($exportRules as $er) {
            $dql .= ' AND ' . $er->getRuleSql();
        }
        $query = $em->createQuery($dql);

        return $query->getResult();
    }
}