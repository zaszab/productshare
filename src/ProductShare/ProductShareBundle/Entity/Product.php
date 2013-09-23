<?php
/**
 * Az osztály rövid leírása
 *
 * Az osztály hosszú leírása, példakód
 * akár több sorban is
 * 
 * @package
 * @author Szabolcs
 * @since 2013.04.14. 15:20
 */

namespace ProductShare\ProductShareBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product")
 * @package ProductShare\ProductShareBundle\Entity
 */
class Product {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $product_id = 0;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $sku = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $model = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name = '';

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    protected $net_price = 0;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    protected $gross_price = 0;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    protected $tax = 0.27;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    protected $discount_price = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="Manufacturer")
     * @ORM\JoinColumn(name="manufacturer_id", referencedColumnName="id")
     */
    protected $manufacturer;

    /**
     * @ORM\Column(type="text")
     */
    protected $description = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $product_link = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $image_link = '';

    /**
     * @ORM\Column(type="integer")
     */
    protected $stock = 99999;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set product_id
     *
     * @param integer $productId
     * @return Product
     */
    public function setProductId($productId)
    {
        $this->id = $productId;
        $this->product_id = $productId;

        return $this;
    }

    /**
     * Get product_id
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return Product
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    
        return $this;
    }

    /**
     * Get sku
     *
     * @return string 
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set model
     *
     * @param string $model
     * @return Product
     */
    public function setModel($model)
    {
        $this->model = $model;
    
        return $this;
    }

    /**
     * Get model
     *
     * @return string 
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set net_price
     *
     * @param float $netPrice
     * @return Product
     */
    public function setNetPrice($netPrice)
    {
        $this->net_price = $netPrice;
    
        return $this;
    }

    /**
     * Get net_price
     *
     * @return float 
     */
    public function getNetPrice()
    {
        return $this->net_price;
    }

    public function setGrossPrice($gross_price)
    {
        $this->gross_price = $gross_price;
    }

    public function getGrossPrice()
    {
        return $this->gross_price;
    }

    /**
     * Set tax
     *
     * @param float $tax
     * @return Product
     */
    public function setTax($tax)
    {
        $this->tax = $tax;
    
        return $this;
    }

    /**
     * Get tax
     *
     * @return float 
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Set discount_price
     *
     * @param float $discountPrice
     * @return Product
     */
    public function setDiscountPrice($discountPrice)
    {
        $this->discount_price = $discountPrice;
    
        return $this;
    }

    /**
     * Get discount_price
     *
     * @return float 
     */
    public function getDiscountPrice()
    {
        return $this->discount_price;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return Product
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    public function setManufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getManufacturer()
    {
        return $this->manufacturer->getName();
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category->getCategorypath();
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set product_link
     *
     * @param string $productLink
     * @return Product
     */
    public function setProductLink($productLink)
    {
        $this->product_link = $productLink;
    
        return $this;
    }

    /**
     * Get product_link
     *
     * @return string 
     */
    public function getProductLink()
    {
        return $this->product_link;
    }

    /**
     * Set image_link
     *
     * @param string $imageLink
     * @return Product
     */
    public function setImageLink($imageLink)
    {
        $this->image_link = $imageLink;
    
        return $this;
    }

    /**
     * Get image_link
     *
     * @return string 
     */
    public function getImageLink()
    {
        return $this->image_link;
    }

    /**
     * Set stock
     *
     * @param integer $stock
     * @return Product
     */
    public function setStock($stock)
    {
        $this->stock = $stock;
    
        return $this;
    }

    /**
     * Get stock
     *
     * @return integer 
     */
    public function getStock()
    {
        return $this->stock;
    }

    public function setArray($array)
    {
        foreach ($array as $key => $val) {
            if (
                property_exists($this, $key)
                && $val !== false
            ) {
                $this->$key = $val;
            }
        }
    }

    public function productJumpUrl()
    {

        return $this->generateUrl('_export_jump') . '/' . $this->id;
    }
}