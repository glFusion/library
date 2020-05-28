<?php
/**
 * Class to read and manipulate Library configuration values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     TBD
 * @since       TBD
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;


/**
 * Class to get plugin configuration data.
 * @package library
 */
final class Config
{
    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;

    private static $instance = NULL;

    /**
     * Get the Library configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self;
        }
        return $instance;
    }


    /**
     * Create an instance of the Library configuration object.
     */
    private function __construct()
    {
        global $_CONF_LIB;

        if (
            $this->properties === NULL
            || empty($_CONF_LIB)
        ) {
            $this->properties = \config::get_instance()
                ->get_config('library');
            $_CONF_LIB = $this->properties;
        }
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key    Name of item to retrieve
     * @return  mixed       Value of config item
     */
    public function get($key=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } else {
            return array_key_exists($key, $this->properties) ? $this->properties[$key] : NULL;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     * @return  object  $this
     */
    public function set($key, $val)
    {
        global $_CONF_LIB;
        $_CONF_LIB[$key] = $val;
        $this->properties[$key] = $val;
        return $this;
    }


    /**
     * Remove a configuration value.
     * Unlike the root glFusion config class, this does not change
     * the database. It only removes config vars in memory.
     *
     * @param   string  $key    Configuration item name
     * @return  object  $this
     */
    public function del($key)
    {
        unset($this->properties[$key]);
        return $this;
    }

}

?>
