<?php
/**
 * Class to manage library item waitlist entries
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
 * Class for Waitlist entries.
 * @package library
 */
class Waitlist
{

    /**
     * Calculate the expiration date of a reservation.
     *
     * @param   integer $days   Max days on hold, from the Item object
     * @return  object          Date object
     */
    private static function _calcExp($days)
    {
        return LIBRARY_now()->add(new \DateInterval("P{$days}D"))->toUnix();
    }


    /**
     * Add a reservation to the waitlist table.
     *
     * @param   object  $Item   Item being waitlisted
     * @param   integer $uid    User requesting reservation
     * @return  integer     Record ID, zero on error
     */
    public static function Add($Item, $uid=0)
    {
        global $_TABLES, $_USER;

        if ($uid == 0) $uid = $_USER['uid'];
        $uid = (int)$uid;

        // If there are existing reservations, this one will get queued behind
        // the current one.
        if (DB_count($_TABLES['library.waitlist'],
                array('item_id', 'uid'),
                array($Item->getID(), $uid)) > 0) {
            $exp_dt = 0;
        } else {
            $exp_dt = self::_calcExp($Item->getDaysOnHold());
        }

        $sql = "INSERT IGNORE INTO {$_TABLES['library.waitlist']} SET
            dt = UNIX_TIMESTAMP(),
            expire = '" . $exp_dt . "',
            item_id = '{$Item->getID()}',
            uid = '$uid'";
        DB_query($sql,1);
        if (!DB_error()) {
            USES_library_functions();
            LIBRARY_notifyLibrarian($Item->getID(), $uid);
            return DB_insertID();
        } else {
            return 0;
        }
    }


    /**
     * Delete a waitlist record from the DB.
     *
     * @param   string  $item_id    ID of library item
     * @param   integer $uid        User ID, current user by default
     */
    public static function Remove($item_id, $uid=0)
    {
        global $_TABLES, $_USER;

        if ($uid == 0) $uid = $_USER['uid'];
        $uid = (int)$uid;
        DB_delete($_TABLES['library.waitlist'],
            array('item_id', 'uid'),
            array($item_id, $uid));
    }


    /**
     * Reset the expiration dates of all item reservations.
     * Used if an item is checked out to a borrower that is not the next
     * in line, which would cause the actual next borrower's reservation to
     * expire.
     *
     * @param   string  $item_id    Item ID
     */
    public static function resetExpirations($item_id)
    {
        global $_TABLES;

        DB_query("UPDATE {$_TABLES['library.waitlist']}
                SET expire = 0
                WHERE item_id = '" . DB_escapeString($item_id) . "'");
    }


    /**
     * Expire waitlist records.
     * - Expires the current reservation that has not been claimed.
     * - Notifies the next reservation, if any.
     */
    public static function Expire()
    {
        global $_TABLES, $_CONF;

        // Save so it's the same in both SQL queries
        $time = time();

        // Delete expired waitlist entries.  This could be done as one
        // sql statement, but we want to log each deletion.
        $sql = "SELECT w.id, w.expire, u.username, i.id as item_id
                FROM {$_TABLES['library.waitlist']} w
                LEFT JOIN {$_TABLES['library.items']} i
                    ON i.id = w.item_id
                LEFT JOIN {$_TABLES['users']} u
                    ON u.uid = w.uid
                WHERE i.daysonhold > 0
                AND w.expire > 0 AND w.expire < $time
                AND i.status=" . LIB_STATUS_AVAIL . "
                GROUP BY i.id
                ORDER BY w.id ASC";
        $result = DB_query($sql);
        while ($A = DB_fetchArray($result, false)) {
            DB_delete($_TABLES['library.waitlist'], 'id', $A['id']);
            self::notifyNext($A['item_id']);
            COM_errorLog('LIBRARY: delete waitlist, ' .
                "user {$A['username']}, item {$A['item_id']} dated {$A['expire']}");
        }
        // Now delete any records that didn't come up before.
        // Usually this is if there's an invalid item ID in the list.
        DB_query("DELETE FROM {$_TABLES['library.waitlist']}
            WHERE expire > 0 AND expire < $time");
    }


    /**
     * Notify the next user on the waiting list that an item has become available.
     *
     * @param  string   $item_id    Library item id
     */
    public static function notifyNext($item_id)
    {
        global $_TABLES,  $_CONF;

        // retrieve the first waitlisted user info.
        $sql = "SELECT w.id, w.uid, w.item_id, u.email, u.language,
                    i.id as item_id, i.title, i.daysonhold
            FROM {$_TABLES['library.waitlist']} w
            LEFT JOIN {$_TABLES['library.items']} i
                ON i.id = w.item_id
            LEFT JOIN {$_TABLES['users']} u
                ON u.uid = w.uid
            WHERE w.item_id='" . DB_escapeString($item_id) . "'
            ORDER BY w.id ASC
            LIMIT 1";
        //echo $sql;die;
        $result = DB_query($sql);
        if (!$result || DB_numrows($result) < 1) {
            return;
        }

        USES_library_functions();

        $A = DB_fetchArray($result, false);
        $username = COM_getDisplayName($A['uid']);
        $daysonhold = (isset($A['daysonhold ']) && $A['daysonhold']> 0) ? $A['daysonhold'] : '';

        // Update the waitlist record with the expiration
        DB_query("UPDATE {$_TABLES['library.waitlist']}
                SET expire = " . self::_calcExp($A['daysonhold']) .
                " WHERE id = {$A['id']}");

        // Select the template for the message
        $template_dir = Config::get('pi_path') .
            '/templates/notify/' . $A['language'];
        if (!file_exists($template_dir . '/item_avail.thtml')) {
            $template_dir = Config::get('pi_path') . '/templates/notify/english';
        }

        // Load the recipient's language.
        MO::init($A['language']);

        $T = new \Template($template_dir);
        $T->set_file('message', 'item_avail.thtml');
        $T->set_var(array(
            'username'      => $username,
            'pi_url'        => Config::get('url'),
            'item_id'       => $A['item_id'],
            'item_descrip'  => $A['title'],
            'daysonhold'    => $daysonhold,
        ) );
        $T->parse('output','message');
        $message = $T->finish($T->get_var('output'));

        COM_mail(
            $A['email'],
            _('Your requested library item is available'),
            $message,
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
        );

        // Reset the locale back to default
        MO::init();
    }


    /**
     * Get all waitlist records for a given item.
     *
     * @param   string  $item_id    Item ID
     * @return  array       Array of waitlist records
     */
    public static function getByItem($item_id)
    {
        global $_TABLES;
        static $waitlist = array();

        if (!isset($waitlist[$item_id])) {
            $sql = "SELECT * FROM {$_TABLES['library.waitlist']}
                    WHERE item_id = '" . DB_escapeString($item_id) . "'
                    ORDER BY id ASC";
            $res = DB_query($sql);
            $waitlist[$item_id] = DB_fetchAll($res, false);
        }
        return $waitlist[$item_id];
    }


    /**
     * Delete all waitlist entries by item ID.
     * Used when an item is deleted.
     *
     * @param   string  $item_id    Item ID
     */
    public static function deleteByItem($item_id)
    {
        global $_TABLES;

        DB_delete($_TABLES['library.waitlist'], 'item_id', DB_escapeString($item_id));
    }


    /**
     * Get all waitlist records for a given user.
     *
     * @param   string  $uid    User ID
     * @return  array       Array of waitlist records
     */
    public static function getByUser($uid)
    {
        global $_TABLES;
        static $waitlist = array();

        if (!isset($waitlist[$uid])) {
            $sql = "SELECT * FROM {$_TABLES['library.waitlist']}
                    WHERE uid = '" . (int)$uid . "'
                    ORDER BY id ASC";
            $res = DB_query($sql);
            $waitlist[$uid] = DB_fetchAll($res, false);
        }
        return $waitlist[$uid];
    }


    /**
     * Get a specific user's position in the waiting list
     *
     * @uses    self::getByUser()
     * @param   string  $item_id    Item ID
     * @param   integer $uid        User ID
     * @return  integer     User's position in the list, 0 if not found
     */
    public static function getUserPosition($item_id, $uid)
    {
        $wl = self::getByItem($item_id);
        $c = count($wl);
        for ($i = 0; $i < $c; $i++) {
            if ($wl[$i]['uid'] == $uid) {
                return $i + 1;
            }
        }
        return 0;
    }


    /**
     * Get a count of all items that a given user has reserved
     *
     * @uses    self::getByUser()
     * @param   integer $uid    User ID
     * @return  integer         Count of items
     */
    public static function countByUser($uid)
    {
        $wl = self::getByUser($uid);
        return count($wl);
    }


    /**
     * Get a count of all reservations for a given item
     *
     * @uses    self::getByItem()
     * @param   integer $item_id    Item ID
     * @return  integer         Count of items
     */
    public static function countByItem($item_id)
    {
        $wl = self::getByItem($item_id);
        return count($wl);
    }

}

?>
