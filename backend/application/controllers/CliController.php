<?php

class CliController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }

    public function updateStatusAction()
    {
        $resultCached = \Application\Model\PrintMaster::getStatus();
        $resultCurrent = \Application\Model\PrintMaster::getStatus(true);
        if (!isset($resultCached->status) || serialize($resultCached) != serialize($resultCurrent)) {
            echo date('Y-m-d H:i:s') . ' status changed : ' . (($resultCurrent->status) ? 'OK' : 'PROBLEMATIC' ) . PHP_EOL;
            if (!$resultCurrent->status) {
                foreach($resultCurrent->detail as $detail)
                    echo '  - ' . $detail . PHP_EOL;
            }
        }

        $result = \Application\Model\PrintMaster::queueNewJobs();
        if ($result > 0) {
            echo date('Y-m-d H:i:s') . ' ' . $result . ' job(s) send to CUPS' . PHP_EOL;
        }
    }
}

