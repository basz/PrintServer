<?php


class Application_Form_Submit extends Zend_Form
{

    public function init()
    {
        $this->setName('submit');
        $this->setAttrib('enctype', 'multipart/form-data');
        
        $element = $this->createElement('hidden', 'action');
        $element->setDecorators(array('ViewHelper'));
        $this->addElement($element);

        $element = $this->createElement('select', 'printer_id')
                ->setLabel('Printer')
                ->setRequired(true)
                ->addMultiOption(null, 'kies')
                ->addMultiOptions(\Application\Model\PrintMaster::getDefinedPrinters());
        $this->addElement($element);

        $element = $this->createElement('file', 'document')
                ->setRequired(true)
                ->setDestination(APPLICATION_PATH . '/../data/tmp/')
                ->addFilter('Rename', array('target' => $this->_getTmpPath(), 'overwrite' => true))
                ->addValidator('Count', true, 1)
                ->addValidator('Size', false, 1024*1024*10)
                ->addValidator('Extension', false, 'pdf');
        
        $this->addElement($element);

        $element = $this->createElement('submit', 'verzend');
        $this->addElement($element);
    }

    private function _getTmpPath($extention = 'pdf') {
        $path = tempnam(APPLICATION_PATH . '/../data/tmp/', '');

        if (is_string($extention) && rename($path, $path . '.' . $extention))
            $path .= '.' . $extention;

        return $path;
    }
}               
