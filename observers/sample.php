<?php

class KH_Plugin_sample extends KH_Plugin 
{
    function KH_Plugin_sample(&$subject)
    {
        parent::KH_Plugin($subject);
    }
    
    function onPostController()
    {
        echo 'This is a sample KhEvent observer being triggered by the onPostController event';
    }
}

?>