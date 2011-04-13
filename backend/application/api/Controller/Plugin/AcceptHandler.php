<?php

class Application_Api_Controller_Plugin_AcceptHandler extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown (Zend_Controller_Request_Abstract $request)
    {
        // Skip header check when we don't have a HTTP request (for instance: cli-scripts)
        if (! $request instanceof Zend_Controller_Request_Http) {
            return;
        }

        $this->getResponse()->setHeader('Vary', 'Accept');

        // Get the Accept header
        $header = $request->getHeader('Accept');
        switch (true) {
            // Depending on the value, set the correct format
            case (strstr($header, HTTP_HEADER_APPLICATION_TYPE.'+json')) :
                $request->setParam('format', 'json');
                break;
            case (strstr($header, HTTP_HEADER_APPLICATION_TYPE.'+xml')) :
                $request->setParam('format', 'xml');
                break;
            case (strstr($header, HTTP_HEADER_APPLICATION_TYPE.'+html')) :
                $request->setParam('format', 'html');
                break;
            default:
                // Default: return whatever is default, but only when the format is not set
                $format = $request->getParam('format');
                if (!isset ($format)) {
                    /* Format is not found, so we use HTML. Used so we don't need to specify
                     * a format when checking the REST server at the browser */
                    $request->setParam('format', 'html');
                }
                break;
        }
    }
}