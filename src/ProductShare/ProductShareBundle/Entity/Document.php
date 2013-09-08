<?php
/**
 * Az osztály rövid leírása
 *
 * Az osztály hosszú leírása, példakód
 * akár több sorban is
 *
 * @package
 * @author Szabolcs
 * @since 2013.04.13. 19:18
 */

namespace ProductShare\ProductShareBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Document
{
    /**
     * @Assert\File(maxSize="6000000")
     */
    private $file;

    protected $path;

    protected $handle;

    protected $columns;

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        // use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move(
            $this->getUploadRootDir(),
            $this->getFile()->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->path = $this->getFile()->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/documents';
    }

    public function getFirstLine()
    {
        $this->handle = fopen($this->getUploadRootDir() . '/' . $this->path, "r");
        if (($buffer = fgets($this->handle, 4096)) !== false) {
            return $this->parseLine($buffer);
        } else {
            fclose($this->handle);
            return false;
        }
    }

    public function getNextProduct()
    {
        if (!isset($this->handle)) {
            $this->getFirstLine();
        }
        if (($buffer = fgets($this->handle, 4096)) !== false) {
            $row = $this->parseLine($buffer);
            return $this->parseProduct($row);
        } else {
            fclose($this->handle);
            return false;
        }
    }

    public function skipLines($num)
    {
        if (!isset($this->handle)) {
            $this->getFirstLine();
        }
        for ($i=0;$i<$num;$i++) {
            fgets($this->handle, 4096);
        }
    }
    protected function parseLine($line)
    {
        return str_getcsv(iconv('ISO-8859-2', 'UTF-8', $line), ';', '"');

    }

    protected function parseProduct($prod)
    {
        $data = array();
        foreach($this->columns as $key => $val) {
            $data[$val] = $prod[$key];
        }
        return $data;
    }
}
