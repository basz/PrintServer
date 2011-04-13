<?php

class GenerateController extends Zend_Controller_Action {

    public function init() {
        $this->_helper->viewRenderer->setNoRender();
    }

    public function indexAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->setHeader('Content-Type', 'text/html; charset=UTF-8', true)->setBody(
                $this->view->HtmlList(array(
                    Zend_Debug::dump($this->getExample_Invoice_Data(), '<a href="' . $this->_helper->url('generate', $this->getRequest()->controller, $this->getRequest()->module, array('pdf' => 'invoice')) . '">Invoice</a>', false),
                    Zend_Debug::dump($this->getExample_Tafelorderbon_Data(), '<a href="' . $this->_helper->url('generate', $this->getRequest()->controller, $this->getRequest()->module, array('pdf' => 'tafelorderbon', 'v' => 1)) . '">Tafelorderbon</a>', false),
                    Zend_Debug::dump($this->getExample_Tafelorderbon_Data(2), '<a href="' . $this->_helper->url('generate', $this->getRequest()->controller, $this->getRequest()->module, array('pdf' => 'tafelorderbon', 'v' => 2)) . '">Tafelorderbon v2</a>', false),
                    Zend_Debug::dump($this->getExample_Wachtrijbon_Data(), '<a href="' . $this->_helper->url('generate', $this->getRequest()->controller, $this->getRequest()->module, array('pdf' => 'wachtrijbon')) . '">Wachtrijbon</a>', false),
                        ), null, null, false));
    }

    protected function generateAction() {
        $this->_helper->layout()->disableLayout();

        // Setup nice currencies notation
        Zend_Registry::set('Zend_Locale', new Zend_Locale('nl_NL'));

        switch ($this->getRequest()->getParam('pdf')) {
            case 'invoice':
                $this->_forward('generate-invoice', null, null, array('pdf' => null));
                return;
                break;
            case 'tafelorderbon':
                $this->_forward('generate-tafelorderbon', null, null, array('pdf' => null));
                return;
                break;
            case 'wachtrijbon':
                $this->_forward('generate-wachtrijbon', null, null, array('pdf' => null));
                return;
                break;
            default:
        }
    }

    protected function generateInvoiceAction() {
        $pdf = new Harkema_Pdf_Document_Invoice();

        $pdf->generate($this->getExample_Invoice_Data());

        $this->getResponse()
                ->setHeader('Content-type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename=InvoiceTest.pdf')
                ->setBody($pdf->render());
    }

    public function generateTafelorderbonAction() {
        $pdf = new Harkema_Pdf_Document_Tafelorderbon();

        $pdf->generate($this->getExample_Tafelorderbon_Data($this->getRequest()->getParam('v', 1)));

        $this->getResponse()
                ->setHeader('Content-type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename=TafelorderbonTest.pdf')
                ->setBody($pdf->render());
    }

    public function generateWachtrijbonAction() {
        $pdf = new Harkema_Pdf_Document_Wachtrijbon();

        $pdf->generate($this->getExample_Wachtrijbon_Data());

        $this->getResponse()
                ->setHeader('Content-type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename=WachtrijbonTest.pdf')
                ->setBody($pdf->render());
    }

    private function getExample_Invoice_Data() {
        return (object) array(
            'date' => '21 mrt 2011',
            'tableNumber' => '34',
            'remark' => 'Remark? info@mydomain.com',
            'items' => array(
                (object) array('amount' => '1', 'description' => 'Thee', 'price' => '2.146'),
                (object) array('amount' => '11', 'description' => 'Bier', 'price' => '3.123'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item', 'price' => '10.10'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item', 'price' => '1.1'),
                (object) array('amount' => '1', 'description' => 'Item 5', 'price' => '1.1'),
            ),
            'vat' => array('btw hoog over € 16.10 = € 3.06', 'btw laag over € 1.00 = € 0.06',
            )
        );
    }

    private function getExample_Tafelorderbon_Data($version=1) {

        switch ($version) {
            case 2:
                return (object) array(
                    'tableNumber' => '34',
                    'items' => array(
                        (object) array('amount' => '1', 'gender' => 'v', 'description' => 'Thee'),
                        (object) array('amount' => '11', 'gender' => '', 'description' => 'Bier', 'remark' => 'Koud'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Dit is het laatste item, en de PDF is dan ook exact juist op maat (variabel dus)'),
                ));
                break;
            default:
                return (object) array(
                    'tableNumber' => '34',
                    'remark' => 'Remark? info@mydomain.com',
                    'items' => array(
                        (object) array('amount' => '1', 'gender' => 'v', 'description' => 'Thee'),
                        (object) array('amount' => '11', 'gender' => '', 'description' => 'Bier', 'remark' => 'Koud'),
                        (object) array('amount' => '1', 'gender' => 'v', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => '', 'description' => 'Vulsel Item'),
                        (object) array('amount' => '1', 'gender' => 'v', 'description' => 'Dit is het laatste item, en de PDF is dan ook exact juist op maat (variabel dus)'),
                ));
        }
    }

    private function getExample_Wachtrijbon_Data() {
        return (object) array(
            'items' => array(
                (object) array('amount' => '1', 'description' => 'Thee'),
                (object) array('amount' => '11', 'description' => 'Bier'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '3', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '4', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Vulsel Item'),
                (object) array('amount' => '1', 'description' => 'Dit is het laatste item, en de PDF is dan ook exact juist op maat (variabel dus)'),
        ));
    }
}

