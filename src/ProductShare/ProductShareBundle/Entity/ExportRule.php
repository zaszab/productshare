<?php

namespace ProductShare\ProductShareBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExportRule
 *
 * @ORM\Table(name="exportrule")
 * @ORM\Entity
 */
class ExportRule
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
     * @ORM\Column(name="`column`", type="string", length=100)
     */
    private $column;

    /**
     * @var integer
     *
     * @ORM\Column(name="ruletype", type="integer")
     */
    private $ruletype;

    /**
     * @var string
     *
     * @ORM\Column(name="filter", type="string", length=255)
     */
    private $filter;

    /**
     * @var integer
     *
     * @ORM\Column(name="published", type="integer")
     */
    private $published;

    /**
     * @var string
     *
     * @ORM\Column(name="export", type="string", length=100)
     */
    private $export;

    /**
     * @var array
     */
    private static $types = array(
        '0' => 'contains',
        '1' => 'not contains',
        '2' => 'exactly',
        '3' => 'exactly not',
        '4' => '<',
        '5' => '<=',
        '6' => '>',
        '7' => '>=',
    );

    private $operatorForTypes = array(
        '0' => " LIKE '%[F]%'",
        '1' => " NOT LIKE '%[F]%'",
        '2' => " = '[F]'",
        '3' => " != '[F]'",
        '4' => " < [F]",
        '5' => " <= [F]",
        '6' => " > [F]",
        '7' => " >= [F]",
    );

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
     * Set column
     *
     * @param string $column
     * @return ExportRule
     */
    public function setColumn($column)
    {
        $this->column = $column;
    
        return $this;
    }

    /**
     * Get column
     *
     * @return string 
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set ruletype
     *
     * @param integer $ruletype
     * @return ExportRule
     */
    public function setRuletype($ruletype)
    {
        $this->ruletype = $ruletype;
    
        return $this;
    }

    /**
     * Get ruletype
     *
     * @return integer 
     */
    public function getRuletype()
    {
        return $this->ruletype;
    }

    /**
     * Get ruletype name
     *
     * @return String
     */
    public function getRuletypeName()
    {
        return self::$types[$this->ruletype];
    }

    /**
     * Set filter
     *
     * @param string $filter
     * @return ExportRule
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    
        return $this;
    }

    /**
     * Get filter
     *
     * @return string 
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param int $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    /**
     * @return int
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param string $export
     */
    public function setExport($export)
    {
        $this->export = $export;
    }

    /**
     * @return string
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * @return string
     */
    public function getRuleSql()
    {
        $pre = 'p';
        $column = $this->column;
        if ($this->column == 'category') {
            $pre = 'c';
            $column = 'categorypath';
        }
        if ($this->column == 'manufacturer') {
            $pre = 'm';
            $column = 'name';
        }
        return $pre . '.' . $column .
            str_replace('[F]', $this->filter, $this->operatorForTypes[$this->ruletype]);
    }

    public static function getTypes()
    {
        return self::$types;
    }
}
