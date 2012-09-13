<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Event Handler
 * 
 * Implements a basic event handling system into CI and exposes an event library
 * class to be used when triggering and registering events.
 * 
 * @author      David Cole <neophyte@sourcetutor.com>
 * @version     0.2
 * @copyright   2008
 */

if (!defined('KH_EVENT'))
{    
    /*
     * CI versions 1.6.1 and below use include instead of include_once
     * which causes issues as this file is needed at the pre-system stage
     * so this little check ensures PHP does not try to declare the classes again.
     */
    
    define('KH_EVENT', true);
    define('KH_EVENT_VERSION', 0.2);
    
    /**
     * Event Library Class
     * 
     * Acts as a CI Library wrapper to the KH_Dispatcher class
     * exposing the register and trigger methods.
     */
    class Khevent
    {
        /**
         * CI Super Object
         *
         * @var object
         * @access private
         */
        var $_ci;
        
        /**
         * Event Dispatcher
         *
         * @var    object
         * @access private
         */
        var $_dispatcher;
           
        /**
         * Class Constructor
         *
         * @return Event
         */
        function Khevent()
        {
            $this->_dispatcher =& KH_Dispatcher::getInstance();
            $this->_ci         =& get_instance();
            
            // Ensure backwards compatability from the library name change
            $this->_ci->event =& $this;
        }        
        
        /**
         * Register Event
         *
         * @param string $event
         * @param mixed  $handler  function or an instance of an KH_Event object.
         */
        function register($event, $handler)
        { 
    		$this->_dispatcher->Register($event, $handler);
        }
        
        /**
         * Trigger Event
         *
         * @param string $event  Event to be triggered
         * @param array  $args   Arguments to be passed to the handler
         * 
         * @return array  Array of results received from the called event handlers
         */
        function trigger($event, $args = null)
        {
    		return $this->_dispatcher->Trigger($event, $args);
        }
    }
    
    /**
     * Observable
     *
     * Implements the observable part of the
     * observer design pattern.
     * 
     */
    class KH_Observable
    {
    	/**
    	 * An array of Observer objects to notify
    	 *
    	 * @access private
    	 * @var array
    	 */
    	var $_observers = array();
    
    	/**
    	 * The state of the observable object
    	 *
    	 * @access private
    	 * @var mixed
    	 */
    	var $_state = null;    
        
        /**
         * Constructor
         *
         * @return KH_Event_Observable
         */
        function KH_Observable()
        {
            $this->_observers = array();
        }
        
        /**
         * Get Observable State
         *
         * @return mixed
         */
        function GetState()
        {
            return $this->_state;
        }
        
    	/**
    	 * Notify Observers
    	 * 
    	 * Update each attached observer object and return an array
    	 * of their return values
    	 *
    	 * @return array Array of return values from the observers
    	 */
    	function Notify()
    	{
    	    $results = array();
    	    
    		foreach ($this->_observers as $observer)
    			$results[] = $observer->Update();
    		
    		return $results;
    	}
    
    	/**
    	 * Attach Observer
    	 *
    	 * @param object $observer An observer object to attach
    	 */
    	function Attach(&$observer)
    	{
    		if (is_object($observer)) // Observer Object
    		{ 
    			$class = get_class($observer);
    			
    			foreach ($this->_observers as $check)
    				if (is_a($check, $class))
    					return;
    
    			$this->_observers[] =& $observer;			
    		}
    		else // Callable Function
    			$this->_observers[] =& $observer;		
    	}
    
    	/**
    	 * Detach Observer
    	 *
    	 * @param object $observer
    	 * 
    	 * @return boolean 
    	 */
    	function Detach($observer)
    	{
    	    if (($key = array_search($observer, $this->_observers)) !== false)
    	    {
    	        unset($this->_observers[$key]);
    	        return true;
    	    }
    	    else 
    	       return false;
    	}    
    }    

    /**
     * Observer
     * 
     * Implements the observer part of the observer
     * design pattern
     *
     */
    class KH_Observer
    {
    	/**
    	 * Event object to observe
    	 *
    	 * @access private
    	 * @var object
    	 */
    	var $_subject = null;
    
    	/**
    	 * Constructor
    	 */
    	function KH_Observer(&$subject)
    	{
    		// Register the observer ($this) so we can be notified
    		$subject->Attach($this);
    
    		// Set the subject to observe
    		$this->_subject = & $subject;
    	}
    
    	/**
    	 * Method to update the state of observable objects
    	 *
    	 * @abstract Implement in child classes
    	 * @access public
    	 * @return mixed
    	 */
    	function Update()
    	{
    		false;
    	}
    }      
    
    /**
     * Dispatcher
     * 
     * Provides a common class instance through use of a singleton
     * from which events are triggered and registered.
     *
     */
    class KH_Dispatcher extends KH_Observable
    {
        /**
         * Enter description here...
         *
         * @var unknown_type
         */
        var $_config = array(
            'directory' => 'observers',
            'autoscan'  => true
        );
        
        /**
         * Enter description here...
         *
         * @var unknown_type
         */
        var $_map = array();
        
        /**
         * Enter description here...
         *
         * @var unknown_type
         */
        var $_loaded = array();
        
        /**
         * Constructor
         *
         * @return KH_Event_Dispatcher
         */
        function KH_Dispatcher()
        {
            parent::KH_Observable();
            
            /*
             * General Init Tasks
             */
            
            $this->_load_config();
            $this->_map_observers();
        }
        
    	/**
    	 * Singleton
    	 * 
    	 * Returns a reference to the global Event Dispatcher object, only creating it
    	 * if it doesn't already exist.
    	 *
    	 * @access	public
    	 * @return	KH_Dispatcher
    	 */
    	function &getInstance()
    	{
    		static $instance;
    
    		if (!is_object($instance))
    			$instance = new KH_Dispatcher();
    
    		return $instance;
    	}
    	
    	/**
    	 * Register Event
    	 *
    	 * @param string $event
    	 * @param mixed  $handler  Name of the event handler or a callable function/method
    	 * 
    	 * @return bool
    	 */
    	function Register($event, $handler)
    	{
    	    if (is_string($handler) && class_exists($handler))
    	    {
    	        $this->Attach(new $handler($this));
    	        return true;
    	    }
    	    else if (is_callable($handler))
    	    {
    	        $observer = array('event' => $event, 'handler' => $handler);
    	        $this->Attach($observer);
    	        
    	        return true;
    	    }
    	    else 
    	        return false;
    	}
    	
    	/**
    	 * Trigger Event
    	 *
    	 * @param string $event  Event to be triggered
    	 * @param array  $args   Array of args to be passed to each of the event handlers
    	 * 
    	 * @return array Array of results received from the called event handlers, false on error
    	 */
    	function Trigger($event, $args = null)
    	{    	   
    	    /*
    	     * Ensure all observers which are interested in this
    	     * event are loaded.
    	     */
    	    
    	    $event_map_key = strtolower($event);
    	    
    	    if (isset($this->_map[$event_map_key]))
    	    {
    	        for ($i = 0, $imax = count($this->_map[$event_map_key]); $i < $imax; $i++)
    	        {
    	            if (!isset($this->_loaded[$this->_map[$event_map_key][$i]['class']]))
    	            {
                	    include_once $this->_map[$event_map_key][$i]['location'];
                	    $instance = new $this->_map[$event_map_key][$i]['class']($this);
                	    $this->_loaded[$this->_map[$event_map_key][$i]['class']] = true;
    	            }
    	        }
    	    }
    	            
    		/*
    		 * Iterate over the registered observers triggering the event
    		 * for each observer that handles the event.
    		 */
    	    
    		$results = array ();
    
    		if ($args === null)
    			$args = array ();
    		
    		foreach ($this->_observers as $observer)
    		{
    			if (is_array($observer))
    			{
                    /*
                     * Observer in this case is simply a callable function
                     * or class method.
                     */
    			    
    				if ($observer['event'] == $event)
    				{
                        $results[] = call_user_func_array($observer['handler'], $args);
    				}
    				else
    					continue;
    			}
    			else if (is_object($observer) && method_exists($observer, 'update'))
    			{
    				/*
    				 * Observer is setup extending the KH_Observer class.
    				 */
    				
                    if (method_exists($observer, $event))
                    {
                        $args['event'] = $event;
                        $results[] = $observer->Update($args);
                    }
                    else
                        continue;
    			}
    			else 
    			    return false;
    		}
    		
    		return $results;	    
    	}
    	
    	/**
    	 * Map Observers
    	 *
    	 */
    	function _map_observers()
    	{
    	    $directory   = APPPATH.$this->_config['directory'].'/';
    	    $cache_file  = BASEPATH.'cache/kh_event_map';
    	    $force_build = false;
    	    
    	    // Should we autoscan ?

    	    if (($this->_config['autoscan'] == true) && file_exists($cache_file))
    	    {
    	        /*
    	         * autoscan is enabled and the cache file exists so lets compare
    	         * the cache to the actual directory structure and see if we need to
    	         * rebuild the observer map.
    	         */
    	        
    	        $cache = unserialize(file_get_contents($cache_file));
                
    	        if (($observers = $this->_scan_directory($directory)) != $cache['observers'])
    	            $force_build = true;
    	    }
    	    
    	    // Do we need to rebuild the observer map ?
    	    
            if (($force_build == true) || !file_exists($cache_file))
            {
                if (!isset($observers))
    	            $observers = $this->_scan_directory($directory);

    	        foreach ($observers as $observer)
    	        {
                    include_once $observer['location'];
                        
                    if (!class_exists($class = 'KH_Plugin_'.$observer['name']))
                        show_error('Khaos :: Event :: Error Locating Class \''.$class.'\' in file \''.$observer['location'].'\'.');
                    else 
                    {
                        $methods  = get_class_methods($class);
                            
                        foreach ($methods as $method)
                            if (($method != $class) && ($method[0] != '_'))
                                $this->_map[$method][] = array(
                                    'location' => $observer['location'],
                                    'class'    => $class
                                );
                    }
    	        }
    	        
    	        if (($fp = fopen($cache_file, 'w')) !== false)
    	        {
    	            fwrite($fp, serialize(array('observers' => $observers, 'map' => $this->_map)));
    	            fclose($fp);
    	        }
    	        else 
    	            show_error('Khaos :: Event :: Error Opening Cache File \''.$cache_file.'\' for writing.');
            }
    	    else 
    	    {
    	        if (!isset($cache))
    	            $cache = unserialize(file_get_contents($cache_file));
    	        
    	        $this->_map = $cache['map'];
    	    }
    	}
    	
    	/**
    	 * Scan Directory
    	 *
    	 * Recursively scans the specified directory building
    	 * up an array of all the observers it finds.
    	 * 
    	 * @param string $dir
    	 * 
    	 * @access private
    	 * @return array
    	 */
    	function _scan_directory($dir)
    	{
    	    $observers = array();
    	    
            if (($dh = @opendir($dir)) !== false)
            {
                while (($file = readdir($dh)) !== false)
                {
                    if ($file == '..' || $file == '.')
                        continue;
                        
                    if (is_dir($dir.$file))
                        $observers = array_merge($observers, $this->_scan_directory($dir.$file.'/'));
                    else 
                    {
                        list($observer, $ext) = explode('.', $file);
                        
                        if (('.'.$ext) != EXT)
                            continue;
                            
                        $observers[] = array(
                            'name'     => $observer,
                            'location' => $dir.$file,
                            'modified' => filemtime($dir.$file)
                        );
                    }
                }
                
                closedir($dh);
                
                return $observers;
            }
    	}
    	
    	/**
    	 * Load Config
    	 *
    	 * Overrides default config settings with the user set
    	 * settings found within the khaos config file.
    	 * 
    	 * @access private
    	 * @return void
    	 */
    	function _load_config()
    	{
    	    if (!file_exists($file = APPPATH.'config/khaos'.EXT))
    	        return;
    	    
    	    include $file;
    	    
    	    if (!isset($config['event']))
    	        return;
    	        
    	    $this->_config = array_merge($this->_config, $config['event']);
    	}
    }
    
    /**
     * Plugin
     * 
     * Upon an event being triggered this class ensures
     * the correct method within the observer object is called.
     *
     */
    class KH_Plugin extends KH_Observer
    {
        /**
         * Constructor
         *
         * @param object $subject  Object to be observed
         * 
         * @return KH_Event
         */
    	function KH_Plugin(&$subject)
    	{
    		parent::KH_Observer($subject);
    	}
    
    	/**
    	 * Trigger Event
    	 *
    	 * @param array Arguments
    	 * 
    	 * @return mixed Routine return value
    	 */
    	function Update(&$args)
    	{
    		/*
    		 * Retrieve the name of the event to be triggered from
    		 * the supplied args.
    		 */
    		
    		$event = $args['event'];
    		unset($args['event']);
    
            // Trigger event
            
    		if (method_exists($this, $event))
    			return call_user_func_array (array(&$this, $event), $args);
    		else
    			return null;	
    	} 
    }    
}

?>