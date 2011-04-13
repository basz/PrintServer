<?php

class Application_Form_PostPrintJob extends Zend_Form
{

    public function init()
    {
        $element = $this->createElement('select', 'Printer')
                ->setRequired(true)
                ->setMultiOptions(array(''=>'Kies', 'printer1'=>'Printer 1'));
        $this->addElement($element);

        $element = $this->createElement('file', 'Bestand')
                ->setRequired(true);
        $this->addElement($element);

        $element = $this->createElement('submit', 'Verzend');
        $this->addElement($element);
    }


}

