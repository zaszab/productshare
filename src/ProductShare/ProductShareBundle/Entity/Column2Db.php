<?php
/**
 * Az osztály rövid leírása
 *
 * Az osztály hosszú leírása, példakód
 * akár több sorban is
 * 
 * @package
 * @author Szabolcs
 * @since 2013.04.14. 17:50
 */

namespace ProductShare\ProductShareBundle\Entity;


class Column2Db {
    protected $columnNames = array();

    protected $columns = array();

    public $col0, $col1, $col2, $col3, $col4, $col5, $col6, $col7, $col8, $col9, $col10, $col11, $col12;

    public $filepath;

    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumnNames($columnNames, $fieldNames)
    {
        foreach ($columnNames as $i => $colName) {
            $this->columnNames[$fieldNames[$i]] = $colName;
        }
    }

    public function getColumnNames()
    {
        return $this->columnNames;
    }

    public function createForm(\Symfony\Component\Form\FormBuilder $builder)
    {
        foreach($this->getColumns() as $i => $csvCol) {
            $builder->add('col'.$i, 'choice', array(
                    'choices'   => $this->getColumnNames(),
                    'required' => false,
                    'label' => $csvCol
                ));
        }
        return $builder->getForm();

    }
}