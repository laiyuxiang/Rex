<?php
class Rex_Config implements ArrayAccess
{

    protected $_data = array();

    public $delimiter = '.';


    public function __construct(array $data = array())
    {
        $this->_data = $data;
    }


    public function get($name = null, $default = null, $delimiter = '.')
    {
        if (null === $name) {
            return $this->_data;
        }

        if (false === strpos($name, $delimiter)) {
            return isset($this->_data[$name]) ? $this->_data[$name] : $default;
        }

        $name = explode($delimiter, $name);

        $ret = $this->_data;
        foreach ($name as $key) {
            if (!isset($ret[$key])) return $default;
            $ret = $ret[$key];
        }

        return $ret;
    }


    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value, $delimiter = '.')
    {
        $pos = & $this->_data;
        if (!is_string($delimiter) || false === strpos($name, $delimiter)) {
            $key = $name;
        } else {
            $name = explode($delimiter, $name);
            $cnt = count($name);
            for ($i = 0; $i < $cnt - 1; $i ++) {
                if (!isset($pos[$name[$i]])) $pos[$name[$i]] = array();
                $pos = & $pos[$name[$i]];
            }
            $key = $name[$cnt - 1];
        }

        $pos[$key] = $value;


        return $this;
    }

    /**
     * Set if not exists
     *
     */
    public function setnx($name, $value, $delimiter = '.')
    {
        if (is_null($this->get($name, null, $delimiter))) {
            return $this->set($name, $value, $delimiter);
        }

        return $this;
    }

    /**
     * @param  string $name
     * @param  mixed  $value
     * @throws Rex_Exception
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return null !== $this->get($name);
    }

    /**
     *
     * @param  string $name
     * @throws Rex_Exception
     * @return void
     */
    public function __unset($name)
    {
        $pos = & $this->_data;
        $name = explode($delimiter, $name);
        $cnt = count($name);
        for ($i = 0; $i < $cnt - 1; $i ++) {
            if (!isset($pos[$name[$i]])) return;
            $pos = & $pos[$name[$i]];
        }
        unset($pos);
    }


    /**
     *
     * @return mixed
     */
    public function keys()
    {
        return array_keys($this->_data);
    }

    /**
     *
     * @param array $config
     * @return Rex_Config
     */
    public function merge($config)
    {
        $this->_data = $this->_merge($this->_data, $config);
        return $this;
    }

    /**
     *
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    protected function _merge($arr1, $arr2)
    {
        foreach($arr2 as $key => $value) {
            if(isset($arr1[$key]) && is_array($value)) {
                $arr1[$key] = $this->_merge($arr1[$key], $arr2[$key]);
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }

    /**
     *
     * @param string $offset
     * @param mixed $value
     * @return Rex_Config
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return null !== $this->get($offset);
    }

    /**
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetUnset($offset)
    {
        return $this->set($offset, null);
    }
}