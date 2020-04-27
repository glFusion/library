<?php
/**
 * Class to cache DB and web lookup results
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;

/**
 * Class for Library cache.
 * @package library
 */
class Cache
{
    const TAG = 'library';
    const MIN_GVERSION = '2.0.0';

    /**
     * Update the cache.
     *
     * @param   string  $key    Item key
     * @param   mixed   $data   Data, typically an array
     * @param   mixed   $tag    Single or array of tags
     * @return  boolean     True on success, False on error
     */
    public static function set($key, $data, $tag='')
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            return true;    // pretend to succeed
        }

        if ($tag == '')
            $tag = array(self::TAG);
        elseif (is_array($tag))
            $tag[] = self::TAG;
        else
            $tag = array($tag, self::TAG);
        $key = self::_makeKey($key, $tag);
        return \glFusion\Cache\Cache::getInstance()->set($key, $data, $tag);
    }


    /**
     * Completely clear the cache.
     * Called after upgrade.
     * Entries matching all tags, including default tag, are removed.
     *
     * @param   mixed   $tag    Single or array of tags
     * @return  boolean     True on success, False on error
     */
    public static function clear($tag = '')
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            return true;    // pretend to succeed
        }

        $tags = array(self::TAG);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Create a cache key.
     * Prepends the tag to every key.
     *
     * @param   string  $key    Unique part of key
     * @return  string          Encoded key string to use as a cache ID
     */
    private static function _makeKey($key)
    {
        return self::TAG . '_' . $key;
    }


    /**
     * Get an item from cache, if it exists.
     *
     * @param   string  $key    Cache key
     * @param   string  $tag    Optional tag to include in key
     * @return  mixed       Item from cache, NULL if not found
     */
    public static function get($key, $tag='')
    {
        global $_EV_CONF;

        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            return NULL;    // fail for early versions of glFusion
        }

        $key = self::_makeKey($key, $tag);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            return \glFusion\Cache\Cache::getInstance()->get($key);
        } else {
            return NULL;
        }
    }

}   // class Library\Cache

?>
