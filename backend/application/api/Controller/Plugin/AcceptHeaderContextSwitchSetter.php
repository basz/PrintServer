<?php

/**
 * There issues with content negotiation and the accept header. The order of the
 * switch is important
 *
 * @see http://www.gethifi.com/blog/browser-rest-http-accept-headers
 *
 */
class Application_Api_Controller_Plugin_AcceptHeaderContextSwitchSetter extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown (Zend_Controller_Request_Abstract $request)
    {
        // Skip header check when we don't have a HTTP request (for instance: cli-scripts)
        if (! $request instanceof Zend_Controller_Request_Http) {
            return;
        }

        if ($request->controller != 'api')
            return;

        $this->getResponse()->setHeader('Vary', 'Accept');

        // Get the Accept header
        $header = $request->getHeader('Accept');

        switch (true) {
            case (strstr($header, 'application/json')) :
                $request->setParam('format', 'json');
                break;

            case (strstr($header, 'application/xhtml+xml')) :
            case (strstr($header, 'text/html')) :
                $request->setParam('format', null);
                break;

            case (strstr($header, 'application/xml')) :
                $request->setParam('format', 'xml');
                break;
            default:
                $format = $request->getParam('format');
                if (!isset ($format)) {
                    /* Format is not found, we don do context switch */
                    $request->setParam('format', null);
                }
                break;
        }
    }
}