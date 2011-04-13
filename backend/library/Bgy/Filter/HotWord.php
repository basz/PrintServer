<?php
require_once 'Zend/Filter/Interface.php';

class Bgy_Filter_HotWord implements Zend_Filter_Interface
{
    protected $_wrapper = '<strong>%string%</strong>';

    protected $_keywords = array();

    public function filter($value)
    {
        usort($this->_keywords, function($a,$b){return(strlen($a)<strlen($b));});
        $wrapper = explode('%string%', $this->_wrapper);
        $wrapper = array_map(function($a) { return addcslashes($a, '<>/="\'-()[]^*.$: ');}, $wrapper);

        foreach ($this->_keywords as $keyword) {
            $value = preg_replace(
                '/((?<!'.$wrapper[0].')('.$keyword.'\b)(?!'.$wrapper[1].'))/i',
                str_replace('%string%', '\2', $this->_wrapper) . '\3',
                $value
            );
        }

        return $value;
    }

    public function setKeywords(array $keywords = array())
    {
        $this->_keywords = $keywords;

        return $this;
    }

    public function setWrapper($string = '')
    {
        $this->_wrapper = $string;

        return $this;
    }

    public function getKeywords()
    {
        return $this->_keywords;
    }

    public function getWrapper()
    {
        return $this->_wrapper;
    }
}
