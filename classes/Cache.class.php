<?php
/**
*   Class to cache DB and web lookup results
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Library;

/**
*   Class for Meetup events
*   @package library
*/
class Cache
{
    private static $tag = 'library'; // fallback tag

    /**
    *   Update the cache
    *
    *   @param  string  $key    Item key
    *   @param  mixed   $data   Data, typically an array
    *   @param  mixed   $tag    Single or array of tags
    */
    public static function set($key, $data, $tag='')
    {
        global $_CONF_LIB;

        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        if ($tag == '')
            $tag = array(self::$tag);
        elseif (is_array($tag))
            $tag[] = self::$tag;
        else
            $tag = array($tag, self::$tag);
        $key = self::_makeKey($key, $tag);
        \glFusion\Cache::getInstance()->set($key, $data, $tag);
    }


    /**
    *   Completely clear the cache.
    *   Called after upgrade.
    *   Entries matching all tags, including default tag, are removed.
    *
    *   @param  mixed   $tag    Single or array of tags
    */
    public static function clear($tag = '')
    {
        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        $tags = array(self::$tag);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        \glFusion\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
    *   Create a unique cache key.
    *
    *   @return string          Encoded key string to use as a cache ID
    */
    private static function _makeKey($key)
    {
        return self::$tag . '_' . $key;
    }


    /**
    *   Get an item from cache, if it exists.
    *
    *   @param  string  $key    Cache key
    *   @param  string  $tag    Optional tag to include in key
    *   @return mixed       Item from cache, NULL if not found
    */
    public static function get($key, $tag='')
    {
        global $_EV_CONF;

        if (version_compare(GVERSION, '1.8.0', '<')) return NULL;

        $key = self::_makeKey($key, $tag);
        if (\glFusion\Cache::getInstance()->has($key)) {
            return \glFusion\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}   // class Library\Cache

?>
