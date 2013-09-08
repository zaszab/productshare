<?php

namespace ProductShare\ProductShareBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category
 *
 * @ORM\Table(name="category")
 * @ORM\Entity
 */
class Category
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="categorypath", type="string", length=255)
     */
    private $categorypath;


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
     * Set categorypath
     *
     * @param string $categorypath
     * @return Category
     */
    public function setCategorypath($categorypath)
    {
        $this->categorypath = $categorypath;
    
        return $this;
    }

    /**
     * Get categorypath
     *
     * @return string 
     */
    public function getCategorypath()
    {
        return $this->categorypath;
    }
}
