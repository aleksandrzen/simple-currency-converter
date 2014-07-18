<?php
/**
 * Class Application_Model_SccRedis
 * Wrapper for Rediska
 * @author aleksandrzen@gmail.com
 */
//define default values
define('DB_HOST',		    '127.0.0.1');
define('DB_PORT',		         '6379');
define('DB_RATES',		              7);
class Application_Model_SccRedis extends Rediska{
    private $confing; // информация из application.ini
    private $db = Array ('rates' => DB_RATES);
    public $error_connect = false;

    /**
     * If you have configuration for Redis server in aplication.ini, it will be used, otherwise, default connection will established.
     * @param boolean $isDebug if true profiler will init
     * @throw Exception if where`re some connection problems
     */
    public function __construct($isDebug = false){
        $this->confing = Zend_Registry::getInstance()->config->redis;
        $this->db['rates'] = isset($this->confing->db->rates) ? $this->confing->db->rates : $this->db['rates'];
        $options = array('servers' => array(array('host' => isset($this->confing->host) ? $this->confing->host : DB_HOST, 'port' => isset($this->confing->port) ? $this->confing->port : DB_PORT, 'db' => $this->db['rates'])));
        if ($isDebug){
            $options['profiler'] = array(
                'name'   => 'stream',
                'stream' => 'profiler.log',
                'mode' => 'a',
                'format' => '[%timestamp%] %profile% => %elapsedTime%'
            );
        }
        parent::__construct($options);
        $this->_specifiedConnection = new Rediska_Connection_Specified($this);
    }
    public  function __call($name, $args) {
        parent::__call($name, $args);
        if($this->error_connect){
            return false;
        }
    }
    public function setAndExpire($key, $value, $seconds){
        if (!$this->error_connect)
            parent::setAndExpire($key, $value, $seconds);
    }
    /**
     * Get value of key or array of values by array of keys
     *
     * @param string|array $keyOrKeys Key name or array of names
     * @return mixed false if can`t connect to server or value of $keyOrKeys if everything alright
     */
    public function get($keyOrKeys) {
        $args = func_get_args();
        try{
            $result = @$this->_executeCommand('get', $args);
        }catch (Exception $e){
            $this->error_connect = true;
            return false;
        }
        return $result;
    }

}