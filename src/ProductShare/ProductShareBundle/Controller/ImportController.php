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
use ProductShare\ProductShareBundle\Entity\Manufacturer;
use ProductShare\ProductShareBundle\Entity\Product;
use ProductShare\ProductShareBundle\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class ImportController extends Controller {

    private $xpath;
    
    /**
     * @Route("/productshare/secured/import", name="_productshare_import")
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
     * @Route("/productshare/secured/importsitemap", name="_productshare_import_sitemap")
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
     * Imports products from a sitemap.xml
     *
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
            if (strpos($source, 'http://schema.org/Product') === false) {
                // Not a product
                continue;
            }
            list ($catPath, $manName, $prod) = $this->parseProductUrl($source, $url);

            if (!$category = $repositoryCat->findOneByCategorypath($catPath)) {
                $category = new Category();
                $category->setCategorypath($catPath);
                $em->persist($category);
            }

            if (!$manufacturer = $repositoryMan->findOneByName($manName)) {
                $manufacturer = new Manufacturer();
                $manufacturer->setName($manName);
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

            $product_count++;
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

    protected function getItemFromSource($prop, $return = false)
    {
        $entry = $this->xpath->query("//*[@itemprop='".$prop."']")->item(0);
        if ($entry) {
            $return = $entry->getAttribute('content')
                ? $entry->getAttribute('content')
                : $entry->nodeValue;
        }
        return $return;
    }

    protected function parseProductUrl($source, $url)
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($source);

        $this->xpath = new \DOMXPath($doc);

        $catPath = $this->getItemFromSource('category', '');
        $manName = $this->getItemFromSource('brand', '');
        $prod = array();
        $prod['name'] = $this->getItemFromSource('name');
        $prod['gross_price'] = $this->getItemFromSource('brand');
        $prod['gross_price'] = $this->getItemFromSource('sku');
        $prod['description'] = trim($this->getItemFromSource('description'));
        $prod['product_link'] = $url;
        $entry = $this->xpath->query("//*[@itemprop='image']")->item(0);
        if ($entry) {
            $prod['image_link'] = $entry->getAttribute('src');
        }

        return array(
            $catPath,
            $manName,
            $prod
        );
    }
}