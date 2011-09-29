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
        
        // check status and report when changed
        $start = microtime(true);
        $resultCached = \Application\Model\PrintMaster::getStatus();
        $resultCurrent = \Application\Model\PrintMaster::getStatus(true);
        if (!isset($resultCached->status) || serialize($resultCached) != serialize($resultCurrent)) {
            echo date('Y-m-d H:i:s') . ' status changed : ' . (($resultCurrent->status) ? 'OK' : 'PROBLEMATIC' ) . PHP_EOL;
            if (!$resultCurrent->status) {
                foreach($resultCurrent->detail as $detail)
                    echo '  - ' . $detail . PHP_EOL;
            }
        }

        $statusTimed = microtime(true) - $start;


        // queue submitted jobs to cups
        $start = microtime(true);
        if (!DIRECT_POSTING) {
            $result = \Application\Model\PrintMaster::queueNewJobs();
            if ($result > 0) {
                printf('%s %d Job(s) send to CUPS', date('Y-m-d H:i:s'), $result);
            }
        }

        $cupsTimed = microtime(true) - $start;

        // report execution times
        echo sprintf("execution times (mode=%s); status = %01.4fs, cups = %01.4fs", (DIRECT_POSTING) ? 'direct on pdf submition' : 'pdf\'s are posted per interval', $statusTimed, $cupsTimed). PHP_EOL;
        
        \Application\Model\PrintMaster::doGarbageCollect(0.1 /* 0.1% = 1 op de 1000 keer */);
    }
}

