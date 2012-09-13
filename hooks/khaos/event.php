<?php

// Event library also holds other utility classes needed to setup the event handling
require_once APPPATH.'libraries/Khevent.php';

/**
 * Trigger CI Event
 *
 * Triggers events for each of the applicable
 * CI hooks.
 * 
 * @param array $args
 */
function _hook_kh_event_ci($args)
{
    static $dispatcher;
    
    if (!is_object($dispatcher))
        $dispatcher =& KH_Dispatcher::getInstance();
        
    $dispatcher->Trigger($args[0]);
}

?>