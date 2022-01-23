<?php

defined('REX_DIR') || define('REX_DIR', dirname(__FILE__));

require REX_DIR . '/Config.php';

class Rex
{
    protected static $_instance = null;
    public $config;
    public $router;
    public $pathInfo;
    public $dispatchInfo;
    protected function __construct()
    {
        $this->config = new Rex_Config(array(
            '_class' => array(
                'Rex_Model'               => REX_DIR . '/Model.php',
                'Rex_View'                => REX_DIR . '/View.php',
                'Rex_Controller'          => REX_DIR . '/Controller.php',
                'Rex_Router'              => REX_DIR . '/Router.php',
                'Rex_Request'             => REX_DIR . '/Request.php',
                'Rex_Response'            => REX_DIR . '/Response.php',
                'Rex_Ext_Validate'        => REX_DIR . '/Ext/Validate.php',
                'Rex_Exception'           => REX_DIR . '/Exception.php',
            ),
        ));
        Rex::registerAutoload();
    }

    public static function registerAutoload($func = 'Rex::loadClass', $enable = true)
    {
        $enable ? spl_autoload_register($func) : spl_autoload_unregister($func);
        return self::$_instance;
    }

    public static function loadClass($class, $file = '')
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }
        if ((!$file)) {
            $key = "_class.{$class}";
            $file = self::getConfig($key);
        }


        if ((!$file) && ('Rex' === substr($class, 0, 4))) {
            $file = dirname(REX_DIR) . DIRECTORY_SEPARATOR
                . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        }

        if ((!$file) && ('Controller' === substr($class, -10))) {
            $file = self::getConfig('_controllersHome') . "/{$class}.php";
        }

        if ((!$file) && ('Model' === substr($class, -5))) {
            $file = self::getConfig('_modelsHome') . "/{$class}.php";
        }

        if (file_exists($file)) {
            include $file;
        }

        return (class_exists($class, false) || interface_exists($class, false)) || self::psr4($class);
    }

    /**
     * psr-4 autoloading
     * @param string $class
     * @return boolean
     *
     */
    public static function psr4($class)
    {
        $prefix = $class;
        $psr4 = self::getConfig('_psr4');
        while (false !== ($pos = strrpos($prefix, '\\'))) {
            $prefix = substr($class, 0, $pos);
            $rest = substr($class, $pos + 1);
            if (empty($psr4[$prefix])) continue;
            $file = $psr4[$prefix] . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $rest)
                . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }

    public static function getConfig($name, $default = null, $delimiter = '.')
    {
        return self::getInstance()->config->get($name, $default, $delimiter);
    }


    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }



    public function getDispatchInfo($init = false)
    {
        if ((null === $this->dispatchInfo) && $init) {
            $this->router || ($this->router = new Rex_Router());

            if ($urls = self::getConfig('_urls')) {
                $this->router->rules += $urls;
            }

            $this->pathInfo || $this->pathInfo = @$_SERVER['PATH_INFO'];
            $this->dispatchInfo = $this->router->match($this->pathInfo);
        }

        return $this->dispatchInfo;
    }


    public function dispatch()
    {
        if (!$di = $this->getDispatchInfo(true)) {
            throw new Rex_Exception('No dispatch info found');
        }
        $di['file'] = $di['module'].'/'.$di['controller'].'.php';
        $defaultModuleHome = self::getConfig('_appHome') . '/' . $di['module'];
        $this->config->setnx('_moduleHome', $defaultModuleHome);
        $this->config->setnx('_controllersHome', $defaultModuleHome);

        if (isset($di['file']) && file_exists($di['file'])) {
            require_once $di['file'];
        }

        if (isset($di['controller'])) {
            $controller = new $di['controller'];
        }

        if (isset($di['action'])) {
            $func = isset($controller) ? array($controller, $di['action']) : $di['action'];
            call_user_func_array($func, $di['args']);
        }
    }


}