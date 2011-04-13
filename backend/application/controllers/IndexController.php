<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */


        //$printerDefinition = new Application\Model\Entity\PrinterDefinition();
        //$printerDefinition->setName('Mr.Right');
        // print_r($printerDefinition);
//        $em->persist($printerDefinition);
//        $em->flush();
    }

    public function indexAction() {
        // action body

        $form = $this->getPostPrintJobForm();

        $this->handleForm($form);
    }

    protected function handleForm(Zend_Form $form) {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            if ($form->isValid($data)) {

            }
        }

        $this->view->form = $form;
    }

    protected function getPostPrintJobForm() {
        return new Application_Form_PostPrintJob();
    }

    protected function generateAction() {

        $this->_helper->viewRenderer->setNoRender();

        $pdf->generate($data);

        $path = $this->getTmpPath('pdf');

        $pdf->save($path) || $pdf_string = $pdf->render();

        exec('open -a Preview ' . $path);

        $o = array();
        $pdf =  Zend_Pdf::load($path);
        if (count($pdf->pages[0])) {
            $w = $pdf->pages[0]->getWidth();
            $h = $pdf->pages[0]->getHeight();

            $o['media']='Custom.' . round($w / 7.2 * 2.54) . 'x' . round($h / 7.2 * 2.54) . 'mm';
        }

        $options = array();

        $options[] = '-d "LinePrinterHarkema"';
        $options[] = escapeshellarg($path);

        foreach($o as $key=>$value) {
            $options[] = '-o ' . $key . '=' . $value;
        }

        $options = implode(' ', $options);

       // die($options);
        $output = '';
        exec('lp ' . $options, $output);//"LinePrinterHarkema" "'.$path.'" -o media=Custom.77x1000mm', $output);

        Zend_Debug::dump($output);

        Zend_Debug::dump('lp ' . $options);
    }

    protected function generate2Action() {
        $path = $this->getTmpPath('txt');
    }

    protected function getTmpPath($extention = null) {
        $path = tempnam(APPLICATION_PATH . '/../data/tmp/', '');



        if (is_string($extention) && rename($path, $path . '.' . $extention))
            $path .= '.' . $extention;

        chmod($path, 0777);
        return $path;
    }
}
