<?php

namespace ProductShare\ProductShareBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\SimpleXMLElement;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use ProductShare\ProductShareBundle\Entity\Column2Db;
use ProductShare\ProductShareBundle\Entity\Document;
use ProductShare\ProductShareBundle\Entity\ExportRule;
use ProductShare\ProductShareBundle\Entity\Manufacturer;
use ProductShare\ProductShareBundle\Entity\Product;
use ProductShare\ProductShareBundle\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route("/productshare/secured")
 */
class SecuredController extends Controller
{
    /**
     * @Route("/login", name="_productshare_login")
     * @Template()
     */
    public function loginAction()
    {
        if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        return array(
            'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        );
    }

    /**
     * @Route("/login_check", name="_productshare_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/logout", name="_productshare_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/import", name="_productshare_import")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function importAction()
    {
        $document = new Document();

        $form = $this->createFormBuilder($document)
            ->add('file', null, array(
                    'label' => false
                ))
            ->getForm();

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/importsitemap", name="_productshare_import_sitemap")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function importsitemapAction(Request $request)
    {
        $form = $this->createFormBuilder(array())
            ->add('url', 'text', array(
                    'label' => 'Your sitemap URL'
                ))
            ->getForm();
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/importingsitemap", name="_productshare_importing_sitemap")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function importingsitemapAction(Request $request)
    {
        $form = $this->createFormBuilder(array())
            ->add('url', 'text')
            ->getForm();

        $sitemap_path = $this->get('kernel')->getRootDir() . '/../web/uploads/sitemap/sitemap.xml';
        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();
            }
            file_put_contents( $sitemap_path, file_get_contents($data['url']));
        }
        $xml = file_get_contents($sitemap_path);

        $links = new SimpleXMLElement($xml);
        $urls = array();
        foreach($links->url as $item) {
            $urls[] = trim((string) $item->loc);
        }

        $repositoryProd = $this->getDoctrine()->getRepository('ProductShareBundle:Product');
        $repositoryCat = $this->getDoctrine()->getRepository('ProductShareBundle:Category');
        $repositoryMan = $this->getDoctrine()->getRepository('ProductShareBundle:Manufacturer');
        $em = $this->getDoctrine()->getManager();

        $count = 0;
        $skip = 0;
        $product_count = 0;

        if ($request->get('skip_urls')) {
            $skip = $request->get('skip_urls');
            $product_count = $request->get('product_count');
            $urls = array_slice($urls, $skip);
            //var_dump($urls, $count); die;
        }

        foreach ($urls as $url) {
            $count++;
            $source = file_get_contents(
                $url,
                false,
                stream_context_create(
                    array(
                        'http' => array(
                            'ignore_errors' => true
                        )
                    )
                )
            );
            //if (strpos($source, 'product_table') === false) {
            if (strpos($source, 'http://schema.org/Product') === false) {
                // Nem termék
                continue;
            }
            $product_count++;
            $prod = array();
            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML($source);

            $xpath = new \DOMXPath($doc);

            $entry = $xpath->query("//*[@itemprop='name']")->item(0);
            if ($entry) {
                $prod['name'] = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
            }

            $entry = $xpath->query("//*[@itemprop='image']")->item(0);
            if ($entry) {
                $prod['image_link'] = $entry->getAttribute('src');
            }

            $cat = '';
            $entry = $xpath->query("//*[@itemprop='category']")->item(0);
            if ($entry) {
                $cat = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
            }

            $man = '';
            $entry = $xpath->query("//*[@itemprop='brand']")->item(0);
            if ($entry) {
                $man = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
            }

            $entry = $xpath->query("//*[@itemprop='price']")->item(0);
            if ($entry) {
                $prod['gross_price'] = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
            }

            $entry = $xpath->query("//*[@itemprop='sku']")->item(0);
            if ($entry) {
                $prod['sku'] = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
            }

            $entry = $xpath->query("//*[@itemprop='description']")->item(0);
            if ($entry) {
                $prod['description'] = $entry->getAttribute('content')
                    ? $entry->getAttribute('content')
                    : $entry->nodeValue;
                $prod['description'] = trim($prod['description']);
            }

            $prod['product_link'] = $url;

            /*$tmp = $doc->getElementsByTagName('h1');
            foreach ($tmp as $t) {
                // innerHTML
                //var_dump($t->ownerDocument->saveXML( $t )); die;
                $prod['name'] = $t->nodeValue;
                break;
            }

            $tmp = $doc->getElementsByTagName('span');
            foreach ($tmp as $t) {
                if ($t->getAttribute('class') == 'price price_color product_table_price') {
                    $prod['gross_price'] = (int) str_replace('.', '', $t->nodeValue);
                    break;
                }
            }

            $tmp = $doc->getElementsByTagName('td');
            $man = '';
            foreach ($tmp as $t) {
                if ($t->getAttribute('class') == 'param-value productsku-param') {
                    $prod['sku'] = $t->nodeValue;
                }
                if ($t->getAttribute('class') == 'param-value productstock-param') {
                    $prod['stock'] = (int) $t->nodeValue;
                }
                if ($t->getAttribute('class') == 'param-value manufacturer-param') {
                    $man = trim($t->nodeValue);
                }
            }
            $tmp = $doc->getElementsByTagName('div');
            foreach ($tmp as $t) {
                if ($t->getAttribute('class') == 'pathway_inner') {
                    $pathway = explode(' > ', $t->nodeValue);
                    array_shift($pathway);
                    array_pop($pathway);
                    $cat = trim(implode(' > ', $pathway));
                }
            }
            $tmp = $doc->getElementById('prod_image_link');
            $prod['image_link'] = $tmp->getAttribute('href');
            $urlParts = explode('_', $url);
            //$prod['id'] = $prod['product_id'] = array_pop($urlParts);

            $tmp = $doc->getElementById('tab_productdescription');
            if ($tmp) {
                $prod['description'] = trim($tmp->nodeValue);
            } */

            if (!$category = $repositoryCat->findOneByCategorypath($cat)) {
                $category = new Category();
                $category->setCategorypath($cat);
                $em->persist($category);
            }

            if (!$manufacturer = $repositoryMan->findOneByName($man)) {
                $manufacturer = new Manufacturer();
                $manufacturer->setName($man);
                $em->persist($manufacturer);
            }

            if (!$product = $repositoryProd->findOneBySku($prod['sku'])) {
                $product = new Product();
            }

            $product->setArray($prod);
            $product->setCategory($category);
            $product->setManufacturer($manufacturer);

            $em->persist($product);
            $em->flush();
            if (!$product->getProductId()) {
                $product->setProductId($product->getId());
                $em->persist($product);
                $em->flush();
            }

            if ($count >= 20) {
                return $this->redirect($this->generateUrl('_productshare_importing_sitemap') . '?skip_urls=' . ($skip + $count) . '&product_count=' . $product_count);
            }
        }
        return array('productCount' => $product_count);
    }

    /**
     * @Route("/import/firstline", name="_import_first_line")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function firstlineAction()
    {
        $document = new Document();
        $form = $this->createFormBuilder($document)
            ->add('file')
            ->getForm();

        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $document->upload();

                $first_line = $document->getFirstLine();

                $columns = array_merge(
                    $em->getClassMetadata('ProductShare\ProductShareBundle\Entity\Product')->getFieldNames(),
                    $em->getClassMetadata('ProductShare\ProductShareBundle\Entity\Product')->getAssociationNames()
                );

                $column2Db = new Column2Db();
                $column2Db->setColumnNames(
                    $columns,
                    $columns
                );
                $column2Db->setColumns($first_line);

                $form2 = $column2Db->createForm($this->createFormBuilder($column2Db));
                $form2->add('filepath', 'hidden', array(
                    'data' => $document->getPath(),
                ));
                return array(
                    'form' => $form2->createView()
                );
            }
        }
    }

    /**
     * @Route("/import/start", name="_import_start")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function importStartAction(Request $request)
    {
        $session = $request->getSession();

        $post = $request->request->get('form');
        if (!empty($post)) {
            $columns = array();
            foreach ($post as $key => $val) {
                if (
                    strpos($key, 'col') === false
                    || $val == ''
                ) {
                    continue;
                }
                $columns[substr($key, 3)] = $val;
            }
            $session->set('columns', $columns);
            $session->set('filepath', $post['filepath']);
        }

        $document = new Document();
        $document->setPath($session->get('filepath'));
        $document->setColumns($session->get('columns'));

        $skip = 0;
        if ($request->get('skip_product')) {
            $skip = $request->get('skip_product');
            $document->skipLines($request->get('skip_product'));
        }

        $repositoryProd = $this->getDoctrine()->getRepository('ProductShareBundle:Product');
        $repositoryCat = $this->getDoctrine()->getRepository('ProductShareBundle:Category');
        $repositoryMan = $this->getDoctrine()->getRepository('ProductShareBundle:Manufacturer');
        $em = $this->getDoctrine()->getManager();
        $count = 0;
        while($prod = $document->getNextProduct()) {
            $cat = $prod['category'];
            unset($prod['category']);

            $man = $prod['manufacturer'];
            unset($prod['manufacturer']);

            if (!$category = $repositoryCat->findOneByCategorypath($cat)) {
                $category = new Category();
                $category->setCategorypath($cat);
                $em->persist($category);
            }
            if (!$manufacturer = $repositoryMan->findOneByName($man)) {
                $manufacturer = new Manufacturer();
                $manufacturer->setName($man);
                $em->persist($manufacturer);
            }
            $count++;
            if (!$product = $repositoryProd->findOneBySku($prod['sku'])) {
                $product = new Product();
            }
            $product->setArray($prod);
            if (
                ($net = $product->getNetPrice())
                && !($gross = $product->getGrossPrice())
            ) {
                $product->setGrossPrice($net * (1 + $product->getTax()));
            }
            if (
                ($gross = $product->getGrossPrice())
                && !($net = $product->getNetPrice())
            ) {
                $product->setNetPrice($gross / (1 + $product->getTax()));
            }
            $product->setCategory($category);
            $product->setManufacturer($manufacturer);

            $em->persist($product);
            $em->flush();

            if ($count == 100) {
                return $this->redirect($this->generateUrl('_import_start') . '?skip_product=' . ($skip + $count));
            }
        }
        return array('productCount' => $skip+$count);
    }

    /**
     * @Route("/export", name="_exports")
     * @Secure(roles="ROLE_ADMIN")
     * @Template()
     */
    public function exportAction()
    {
        $exports = array('arukereso', 'argep', 'olcsobbat');
        return array('exports' => $exports);
    }

    /**
     * @Route("/export/management", name="_export_management")
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
     * @Route("/export/management/addrule", defaults={"id"=0}, name="_export_add_rule")
     * @Route("/export/management/addrule/{id}", name="_productshare_secured_hello")
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
                'choices' => array(
                    '' => 'All exports',
                    'arukereso' => 'arukereso',
                    'argep' => 'argep',
                    'olcsobbat' => 'olcsobbat',
                ),
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
     * @Route("/export/management/deleterule/{id}", name="_export_delete_rule")
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
     * @Route("/export/statistics", name="_export_statistics")
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
}
