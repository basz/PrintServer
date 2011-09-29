<?php

class ApiController extends Zend_Controller_Action {

    public function init() {
        if (!file_exists(APPLICATION_PATH . '/configs/printers-'.APPLICATION_DOMAIN.'.ini'))
            throw new Zend_Exception('/configs/printers-'.APPLICATION_DOMAIN.'.ini does not exists');            

        if (file_exists(APPLICATION_PATH . '/../data/database.sqlite') && !is_writable(APPLICATION_PATH . '/../data/database.sqlite'))
            throw new Zend_Exception('../data/database.sqlite is not writable');            
        
        $this->_helper->contextSwitch()
                ->addActionContext('envelope', array('json', 'xml'))
                ->addActionContext('status', array('json', 'xml'))
                ->addActionContext('printer-status', array('json', 'xml'))
                ->addActionContext('printers', array('json', 'xml'))
                ->addActionContext('submit', array('json', 'xml'))
                ->addActionContext('job-status', array('json', 'xml'))
                ->setAutoJsonSerialization(true)
                ->initContext();

        $this->_em = Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
    }

    /**
     * setsup database
     */
    public function setupAction() {
        try {
            $this->_helper->viewRenderer->setNoRender();

            try {
                $config = new Zend_Config_Ini( APPLICATION_PATH . '/configs/printers-'.APPLICATION_DOMAIN.'.ini', APPLICATION_ENV);
                $createPrinters = $config->__isset('printers') ? $config->printers->toArray() : array();
            } catch (Zend_Config_Exception $e) {
                $createPrinters = array();
            }
            
            $queued_jobs = $this->_em->getRepository('Application\Model\Entity\PrinterJob')->findBy(array('status' => 'cupsed'));
            foreach ($queued_jobs as $queued_job)
                \Application\Model\PrintMaster::cancelJob($queued_job);

            // remove all files in tmp
            foreach (new DirectoryIterator(APPLICATION_PATH . '/../data/tmp') as $file)
                if ($file->isFile())
                    @unlink($file->getRealPath());

            // deleting definitions
            $printers = $this->_em->getRepository('Application\Model\Entity\PrinterDefinition')->findAll();
            foreach ($printers as $printer)
                $this->_em->remove($printer);

            $cupsPrinterIds = \Application\Model\PrintMaster::getDefinedCupsPrinters();

            foreach ($createPrinters as $name => $cupsName) {
                $existInCups = false;
                foreach ($cupsPrinterIds as $cupsPrinter)
                    if ($cupsPrinter->name == $cupsName) {
                        $existInCups = true;
                        break;
                    }
                    
                if (!$existInCups) {
                    Zend_Debug::dump($cupsName, 'CONFIGURED QUEUE DOES NOT EXIST WITHIN CUPS');
                    continue;
                }

                // create printer definition
                $printer = new Application\Model\Entity\PrinterDefinition();
                $printer->setName($name);
                $printer->setCupsName($cupsName);

                // getting printer options
                $_printerOptions = array();
                exec('lpoptions -p "' . $cupsPrinter->name . '"', $_printerOptions);
               
                if (count($_printerOptions)) {
                    $_printerOptions = implode(' ', $_printerOptions);
                    $printerOptions = $this->parsePrinterOptions($_printerOptions);

                    $printer->setDefaultOptions($printerOptions);
                }

                Zend_Debug::dump($printer, 'DEFINING NEW PRINTER');
                $this->_em->persist($printer);
            }

            $this->_em->flush();
        } catch (Exception $e) {
            Zend_Debug::dump($e);
            die();
        }
    }

    /**
     * envelopes an error
     */
    public function envelopeAction() {
        $this->view->result = $this->getRequest()->getParam('result', false);

        if ($this->view->result)
            $this->view->response = $this->getRequest()->getParam('response', null);
        else
            $this->view->error = $this->getRequest()->getParam('error', null);
    }

    /**
     * Poll for problems with any printer
     */
    public function statusAction() {
        $result = \Application\Model\PrintMaster::getStatus();

        $status = ($result->status) ? 'OK' : 'PROBLEMATIC';

        $response = (object) array('status' => $status);

        if (!$result->status)
            $response->detail = $result->detail;

        $this->view->headMeta()->appendHttpEquiv('Refresh', '10;URL=' . $this->view->url());
        $this->view->showAlert = !$result->status;


        $this->_forward('envelope', null, null, array('result' => true, 'response' => $response));
    }

    /**
     * Poll for problems with a specific printer
     *
     * @throws undefined printer / 1
     */
    public function printerStatusAction() {
        $this->view->headMeta()->appendHttpEquiv('Refresh', '10;URL=' . $this->view->url());

        $this->_forward('envelope', null, null, array('result' => true, 'response' => (object) array('status' => 'NOT-YET-IMPLEMENTED')));
    }

    /**
     * Get a list of defined printers
     */
    public function printersAction() {
        $this->_forward('envelope', null, null, array('result' => true, 'response' => array_values(\Application\Model\PrintMaster::getDefinedPrinters())));
    }

    /**
     * Post a PDF
     *
     * @throws document missing / 3
     * @throws document not a PDF / 4
     * @throws document to large / 5
     * @throws undefined printer / 1
     */
    public function submitAction() {
        $this->_helper->contextSwitch()->setAutoJsonSerialization(false);

        $baseAction = Zend_Controller_Action_HelperBroker::getStaticHelper('url')->url(array('printer_id' => null), 'api_submit');
        $form = new Application_Form_Submit(array('action' => $baseAction));
        $form->getElement('action')->setValue($baseAction);

        if ($this->getRequest()->isGet()) {
            $form->getElement('printer_id')->setValue($this->getRequest()->getParam('printer_id', null));
        } elseif ($this->getRequest()->isPut()) {

        } elseif ($this->getRequest()->isPost()) {
            $data = array_merge(array('printer_id' => $this->getRequest()->getParam('printer_id', null)), $this->getRequest()->getPost());

            if ($form->isValid($data)) {
                $printer_id = $form->printer_id->getValue();
                $form->document->receive();
                $documentPath = $form->document->getFileName();

                $res = \Application\Model\PrintMaster::createJob($printer_id, $documentPath);

                if (is_numeric($res)) {
                    $this->_forward('envelope', null, null, array('result' => true, 'response' => (int) $res));
                } else {
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) $res));
                }

//                return;
            } else {
                $errors = $form->getErrors();
                if (isset($errors['printer_id']) && in_array('isEmpty', $errors['printer_id']))
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'undefined printer', 'code' => 1)));
                elseif (isset($errors['printer_id']) && in_array('notInArray', $errors['printer_id']))
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'undefined printer', 'code' => 1)));
                elseif (isset($errors['document']) && in_array('fileUploadErrorNoFile', $errors['document']))
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'document missing', 'code' => 3)));
                elseif (isset($errors['document']) && in_array('fileExtensionFalse', $errors['document']))
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'document not a PDF', 'code' => 4)));
                elseif (isset($errors['document']) && in_array('fileUploadErrorIniSize', $errors['document']))
                    $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'document to large', 'code' => 5)));
                else
                    Zend_Debug::dump($errors, 'uncatched FIX ME!');

                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * @throws unknown job / 2
     */
    public function jobStatusAction() {
        $this->_forward('envelope', null, null, array('result' => true, 'response' => (object) array('status' => 'NOT-YET-IMPLEMENTED')));
        return;

        $job_id = $this->getRequest()->getParam('job_id', null);

        $job = $this->_em->find('Application\Model\Entity\PrinterJob', $job_id);
        if (!$job) {
            $this->_forward('envelope', null, null, array('result' => false, 'error' => (object) array('message' => 'unknown job', 'code' => 2)));
            return;
        }

        $status = $job->getStatus() == 'NEW' ? 'PROBLEMATIC' : 'OK';


        $this->_forward('envelope', null, null, array('result' => true, 'response' => (object) array('status' => $status)));
    }

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
//    public static function indent($json) {
//
//        $result = '';
//        $pos = 0;
//        $strLen = strlen($json);
//        $indentStr = '  ';
//        $newLine = "\n";
//        $prevChar = '';
//        $outOfQuotes = true;
//
//        for ($i = 0; $i <= $strLen; $i++) {
//
//            // Grab the next character in the string.
//            $char = substr($json, $i, 1);
//
//            // Are we inside a quoted string?
//            if ($char == '"' && $prevChar != '\\') {
//                $outOfQuotes = !$outOfQuotes;
//
//                // If this character is the end of an element,
//                // output a new line and indent the next line.
//            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
//                $result .= $newLine;
//                $pos--;
//                for ($j = 0; $j < $pos; $j++) {
//                    $result .= $indentStr;
//                }
//            }
//
//            // Add the character to the result string.
//            $result .= $char;
//
//            // If the last character was the beginning of an element,
//            // output a new line and indent the next line.
//            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
//                $result .= $newLine;
//                if ($char == '{' || $char == '[') {
//                    $pos++;
//                }
//
//                for ($j = 0; $j < $pos; $j++) {
//                    $result .= $indentStr;
//                }
//            }
//
//            $prevChar = $char;
//        }
//
//        return $result;
//    }

    private function parsePrinterOptions($rawOptions) {
        $options = array();

        do {
            $key = '';
            $value = '';

            $firstEqualSignPos = strpos($rawOptions, '=');
            $key = trim(substr($rawOptions, 0, $firstEqualSignPos));
            $rawOptions = trim(substr($rawOptions, $firstEqualSignPos + 1, strlen($rawOptions)));


            // find value
            $firstSpacePos = strpos($rawOptions, ' ');
            $firstQuotePos = strpos($rawOptions, '\''); // values might be placed between signle quotes

            if ($firstQuotePos === false || $firstSpacePos < $firstQuotePos) {
                $value = trim(substr($rawOptions, 0, $firstSpacePos));
                $rawOptions = trim(substr($rawOptions, $firstSpacePos));
            } else {
                // remove first quote, which should be at position 0
                $rawOptions = substr($rawOptions, 1, strlen($rawOptions));

                $secondQuotePos = strpos($rawOptions, '\'');
                $value = trim(substr($rawOptions, 0, $secondQuotePos));

                $rawOptions = trim(substr($rawOptions, $secondQuotePos + 1, strlen($rawOptions)));
            }
            if (strlen($key) && strlen($value))
                $options[$key] = $value;
        } while (strlen($rawOptions));

        return $options;
    }

}
