<?php

class Application_Api_Controller_Action_Helper_RestContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{

    public function getActionContexts($action = null)
    {
        // All actions point back to the global action, or return the complete list of actions
        return parent::getActionContexts($action ? 'global' : null);
    }

    // Every action has context
    public function hasActionContext($action, $context)
    {
        return true;
    }

    // It's not possible to add specific action contexts
    public function addActionContext($action, $context)
    {
        // You cannot add context to actions
        throw new Zend_Controller_Action_Exception('You must call addGlobalContext() instead of addActionContext()');
    }

    // Add global context, this will trigger for ALL actions found
    public function addGlobalContext($contexts)
    {
        return parent::addActionContext('global', $contexts);
    }

    // This is almosts an exact 1:1 copy of the Zend_Controller_Action_Helper_ContextSwitch::initContext().
    // We just need to modify 2 or 3 lines inside.
    public function initContext($format = null)
    {
        $this->_currentContext = null;

        $controller = $this->getActionController();
        $request    = $this->getRequest();
        $action     = $request->getActionName();

        // Return if no context switching enabled, or no context switching
        // enabled for this action
        $contexts = $this->getActionContexts($action);
        if (empty($contexts)) {
            return;
        }

        // Return if no context parameter provided
        if (!$context = $request->getParam($this->getContextParam())) {
            if ($format === null) {
                return;
            }
            $context = $format;
            $format  = null;
        }

        // Check if context allowed by action controller
        if (!$this->hasActionContext($action, $context)) {
            return;
        }

        // Return if invalid context parameter provided and no format or invalid
        // format provided
        if (!$this->hasContext($context)) {
            $context = $this->getDefaultContext();
        }

        // Use provided format if passed
        if (!empty($format) && $this->hasContext($format)) {
            $context = $format;
        }

        $suffix = $this->getSuffix($context);

        $this->_getViewRenderer()->setViewSuffix($suffix);

        $headers = $this->getHeaders($context);
        if (!empty($headers)) {
            $response = $this->getResponse();
            foreach ($headers as $header => $content) {
                $response->setHeader($header, $content);
            }
        }

        if ($context != 'html' && $this->getAutoDisableLayout()) {
            /**
             * @see Zend_Layout
             */
            $layout = Zend_Layout::getMvcInstance();
            if (null !== $layout) {
                $layout->disableLayout();
            }
        }

        if (null !== ($callback = $this->getCallback($context, self::TRIGGER_INIT))) {
            if (is_string($callback) && method_exists($this, $callback)) {
                $this->$callback();
            }
            else if (is_string($callback) && function_exists($callback))
            {
                $callback();
            }
            else if (is_array($callback))
            {
                call_user_func($callback);
            }
            else
            {
                /**
                 * @see Zend_Controller_Action_Exception
                 */
                // require_once 'Zend/Controller/Action/Exception.php';
                throw new Zend_Controller_Action_Exception(
                    sprintf('Invalid context callback registered for context "%s"', $context));
            }
        }

        $this->_currentContext = $context;
    }

}
