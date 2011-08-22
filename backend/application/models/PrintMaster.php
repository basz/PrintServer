<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Application\Model;

/**
 * Description of PrintMaster
 *
 * @author bas
 */
class PrintMaster {

    // return int when ok or error object
    static function createJob($printer_id, $path) {
        $em = \Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
        $printer = $em->getRepository('Application\Model\Entity\PrinterDefinition')->findOneBy(array('name' => $printer_id));

        if (!in_array($printer_id, self::getDefinedPrinters()) || !$printer)
            return array('message'=>'undefined printer', 'code'=>1);

        if (!file_exists($path) || !is_readable($path))
            return array('message'=>'unknown error', 'code'=>-1);

        try {
            ob_start();
            $pdf = \Zend_Pdf::load($path);
            ob_end_clean();
        } catch (\Zend_Pdf_Exception $e) {
            ob_end_clean();
            return array('message'=>'document not a PDF', 'code'=>4);
        }

        // create new job
        $job = new \Application\Model\Entity\PrinterJob();
        $job->setPrinter($printer);
        $job->setStatus('new');
        $job->setBasename(\pathinfo($path, \PATHINFO_BASENAME));
        \Zend_Debug::dump($job, 'DEFINING NEW JOB');
        $em->persist($job);
        
        $em->flush();
        
        return $job->getId();
    }
    
    static function queueNewJobs() {
        $em = \Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
        $jobs = $em->getRepository('Application\Model\Entity\PrinterJob')->findBy(array('status' => 'new'));

        $count = 0;
        
        foreach($jobs as $job) {
            $path = realpath(APPLICATION_PATH . '/../data/tmp/') . '/' . $job->getBasename();
        
            if (!file_exists($path)) {
                $job->setStatus('errorneous');
                $em->persist($job);

                continue;
            }
            
            // create custom printer options
            $o = array();
            try {
                ob_start();
                $pdf = \Zend_Pdf::load($path);
                ob_end_clean();
            } catch (\Zend_Pdf_Exception $e) {
                ob_end_clean();
                $job->setStatus('errorneous');
                $em->persist($job);
                continue;
            }
            if (count($pdf->pages)) {
                $w = $pdf->pages[0]->getWidth();
                $h = $pdf->pages[0]->getHeight();

                $o['media'] = 'Custom.' . round($w / 7.2 * 2.54) . 'x' . round($h / 7.2 * 2.54) . 'mm';
            }

            $options = array();

            $options[] = '-d "'.$job->getPrinter()->getCupsName().'"';
            $options[] = escapeshellarg($path);

            foreach ($o as $key => $value) {
                $options[] = '-o ' . $key . '=' . $value;
            }

            $options = implode(' ', $options);

            
            
            //\Zend_Debug::dump('lp ' . $options);
            $output = '';$result = '';
            exec('lp ' . $options, $output, $result);
            \Zend_Debug::dump($output, 'output'); // array("request id is Beehives_HP-336 (1 file(s))")
            //\Zend_Debug::dump($result, 'result'); // int(0) | int(1) 
            if ($result === 0 && preg_match('/^request id is ('.$job->getPrinter()->getCupsName().'\-[0-9]{1,8}).*$/', $output[0], $m)) {
                $job->setCupsJobId($m[1]);
            
                $job->setStatus('cupsed');
            } else {
                $job->setStatus('errorneous');
                $em->persist($job);

                continue;
            }
            
            $count++;
            $em->persist($job);
        }
        
        $em->flush();

        return $count;
    }
    
    static function cancelJob(\Application\Model\Entity\PrinterJob $job) {
        if ($job->getStatus() != 'cupsed')
            return;

        $id = (int) array_pop(@explode('-', $job->getCupsJobId())); // 'PrinterName-int'


        $output = '';$result = '';
        try {
            if ($job->getPrinter())
                exec(APPLICATION_PATH . '/../bin/cups/cancelJob.sh "' . $job->getPrinter()->getCupsName() . '" ' . $id, $output, $result);
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            
        }

        if ($result === 0) {
            \Zend_Debug::dump('CANCELED JOB :' . $job->getId());
            $job->setStatus('canceled');
            $em = \Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
            $em->persist($job);
            $em->flush();
        }
    }

    static function getDefinedPrinters() {
        $em = \Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
        
        $result = array();
        $printers = $em->getRepository('Application\Model\Entity\PrinterDefinition')->findAll();
        foreach($printers as $printer) {
            $result[$printer->getName()] = $printer->getName();
        }

        return $result;
    }

    static function getDefinedCupsPrinters() {
        $result = array();

        $output = array();
        exec(APPLICATION_PATH . '/../bin/cups/getDefinedCupsPrinters.sh', $output, $return);
        array_shift($output);
       // print_r($output);
        foreach($output as $line) {
            if (preg_match('/^device for (.*?): (.*)$/', $line, $m)) {
                //print_r($m);
                $result[] = (object) array('name'=>$m[1], 'uri'=>$m[2]);
            }
        }

        return $result;
    }
    
    static function getStatus($forceUpdate = false) {
        $cache = \Application_Api_Util_Bootstrap::getResource('Cache');
        
        if ($forceUpdate)
            $cache->remove('status');
        
        if ( ($status = $cache->load('status')) === false ) { // cache miss
            $status = self::_getStatus();

           $saved = $cache->save($status, 'status');
           // $saved should be true, if it is not we should act on it... (in the futere...
        }

        return $status;
    }
    
    private static function _getStatus() {
        $em = \Application_Api_Util_Bootstrap::getResource('doctrine')->getEntityManager();
        
        $result = (object) array('status'=>true, 'detail'=>array());
        
        $printers = $em->getRepository('Application\Model\Entity\PrinterDefinition')->findAll();

        // FIRST CHECK THE CUPS STATUSSES
        foreach($printers as $printer) {
            $output = array();
            exec(APPLICATION_PATH . '/../bin/cups/getDefinedCupsStatus.sh "'.$printer->getCupsName().'"', $output);
            
            if (isset($output[0]) AND strpos($output[0], $printer->getCupsName() . ' is ready') === 0)  {
                $result->status = (true && $result->status);
            } else {
                $result->status = false;
                $result->detail[] = "'".$printer->getName() . "' is not ready according to CUPS";
            }
        }

        // additional ping test based on printer type
        foreach($printers as $printer) {
            $type = $printer->getDefaultOption('printer-make-and-model');
            switch ($type) {
                case 'Star TSP100 Cutter':
                    $deviceUri = $printer->getDefaultOption('device-uri');
                     if (preg_match('/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/', $deviceUri, $matches))
                        $ip = $matches[0];
                     else $ip = null;

                     if ($ip) {
                        if ($pingable = self::ping($ip)) {
                            $result->status = (true && $result->status);
                        } else {
                            $result->status = false;
                            $result->detail[] = "'" . $printer->getName() . "' is not responding to pings to '$ip'";
                        }
                        
                        if ($pingable) {
                            $user = 'root';
                            $pass = 'public';

                            $output = array();
                            $exit = '';
                            exec(APPLICATION_PATH . "/../bin/printer-specific/starSP100-status.sh $ip $user $pass", $output, $exit);

                            if ($exit == 0 AND strpos($output[4], '23 86 00 00 00 00 00 00  00 00 00') !== FALSE) {
                                $result->status = (true && $result->status);
                            } else {
                                $result->status = false;
                                $detail = "'" . $printer->getName() . "' is not ready according to custom script (most likely OUT OF PAPER)";
                                $result->detail[] = $detail;
                            }
                        }
                    }
                    break;
                default:
            }
        }


        // test each printer for status script


        return $result;
    }

    private static function ping($host, $timeout = 1) {
        exec("/sbin/ping -c 1 -t $timeout $host 2>&1", $output, $retval);

        if ($retval != 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

}
/*

 * lpoptions -d LinePrinterHarkema

 * auth-info-required=none copies=1 device-uri=lpd://192.168.178.105/ finishings=3 job-hold-until=no-hold job-priority=50 job-sheets=none,none marker-change-time=0 media=iso_a4_210x297mm number-up=1 printer-commands=none printer-info=LinePrinterHarkema printer-is-accepting-jobs=true printer-is-shared=false printer-location=Keuken printer-make-and-model='Star TSP100 Cutter' printer-state=3 printer-state-change-time=1300895633 printer-state-reasons=none printer-type=2150404 printer-uri-supported=ipp://localhost:631/printers/LinePrinterHarkema
 *

lpstat -t
scheduler is running
system default destination: Canon_iP5200
device for Beehives_HP: lpd://192.168.178.47/
device for Canon_iP5200: dnssd://Canon%20iP5200._riousbprint._tcp.local.
device for EPSON_Perfection_3200: usb://00000000-0000-0000-0000-4800000F3E62
device for LinePrinterHarkema: lpd://192.168.178.105/
Beehives_HP accepting requests since ma  7 mrt 14:57:48 2011
Canon_iP5200 accepting requests since wo 23 mrt 20:04:54 2011
EPSON_Perfection_3200 accepting requests since wo  9 mrt 16:56:04 2011
LinePrinterHarkema accepting requests since wo 23 mrt 21:11:37 2011
printer Beehives_HP is idle.  enabled since ma  7 mrt 14:57:48 2011
printer Canon_iP5200 is idle.  enabled since wo 23 mrt 20:04:54 2011
	Ready to print.
printer EPSON_Perfection_3200 is idle.  enabled since wo  9 mrt 16:56:04 2011
printer LinePrinterHarkema now printing LinePrinterHarkema-334.  enabled since wo 23 mrt 21:11:37 2011
	Connecting to printer...
LinePrinterHarkema-334  _www              3072   wo 23 mrt 21:11:37 2011


 *
 * 
 */