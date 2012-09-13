INSTALLATION
------------

1) Upload the files and folders as you see them to your application folder.
2) enable hooks in config/config.php
3) add the following to config/hooks.php

$hook['pre_system'] = array(
    'function' => '_hook_kh_event_ci',
    'filename' => 'event.php',
    'filepath' => 'hooks/khaos',
    'params'   => array('onPreSystem')
);  

$hook['pre_controller'] = array(
    'function' => '_hook_kh_event_ci',
    'filename' => 'event.php',
    'filepath' => 'hooks/khaos',
    'params'   => array('onPreController')
);  

$hook['post_controller_constructor'] = array(
    'function' => '_hook_kh_event_ci',
    'filename' => 'event.php',
    'filepath' => 'hooks/khaos',
    'params'   => array('onPostControllerConstructor')
);  

$hook['post_controller'] = array(
    'function' => '_hook_kh_event_ci',
    'filename' => 'event.php',
    'filepath' => 'hooks/khaos',
    'params'   => array('onPostController')
);  

$hook['post_system'] = array(
    'function' => '_hook_kh_event_ci',
    'filename' => 'event.php',
    'filepath' => 'hooks/khaos',
    'params'   => array('onPostSystem')
);  

4) add 'khevent' to your config/autoload.php libraries array