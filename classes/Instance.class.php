<?php
/**
 * Class to manage individual instances of library items.
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
 * Class for specific item instances.
 * @package library
 */
class Instance
{
    /** Property fields.  Accessed via __set() and __get()
    *   @var array */
    var $properties;

    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   integer $id Optional instance ID
     */
    public function __construct($id=0)
    {
        $this->properties = array();

        if (is_array($id)) {
            // Got a DB record, just load the values
            $this->setVars($id);
        } else {
            // Got an instance ID, read the item and load
            $id = (int)$id;
            $this->instance_id = $id;
            if (!$this->Read()) {
                $this->instance_id = 0;
            }
        }
    }


    /**
     * Set a property's value.
     *
     * @param   string  $var    Name of property to set.
     * @param   mixed   $value  New value for property.
     */
    public function __set($var, $value='')
    {
        switch ($var) {
        case 'instance_id':
        case 'uid':
        case 'due':
           // Integer values
            $this->properties[$var] = (int)$value;
            break;

        case 'item_id':
            // String values
            $this->properties[$var] = trim($value);
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
     * Get the value of a property.
     *
     * @param   string  $var    Name of property to retrieve.
     * @return  mixed           Value of property, NULL if undefined.
     */
    public function __get($var)
    {
        if (array_key_exists($var, $this->properties)) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
    }


    /**
     * Sets all variables to the matching values from $rows
     *
     * @param   array $row Array of values, from DB or $_POST
     */
    public function setVars($row)
    {
        if (!is_array($row)) return;

        $this->instance_id = $row['instance_id'];
        $this->item_id = $row['item_id'];
        $this->uid = $row['uid'];
        $this->due = $row['due'];
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $id Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure
     */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) $id = $this->instance_id;
        if ($id == 0) {
            $this->error = 'Invalid ID in Instance::Read()';
            return;
        }

        $result = DB_query("SELECT *
                    FROM {$_TABLES['library.instances']}
                    WHERE instance_id='$id'");
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row);
            return true;
        }
    }


    /**
     * Delete the current instance record from the database
     */
    public function Delete()
    {
        global $_TABLES, $_CONF_LIB;

        // Can't delete new or currently checked-out instances
        if ($this->instance_id <= 0 || $this->uid > 0)
            return false;

        DB_delete($_TABLES['library.instances'],
                'instance_id', $this->instance_id);

        $this->instance_id = 0;
        return true;
    }


    /**
     * Get all item instances that match a given availability.
     *
     * @param   string  $item_id    Item ID
     * @param   integer $avail      Availability (0=all, 1=available, 2=out
     */
    public static function getAll($item_id, $avail = 0)
    {
        global $_TABLES;

        $avail = (int)$avail;
        $key = md5($item_id . '_' . $avail);
        $retval = Cache::get($key);
        if ($retval !== NULL) {
            return $retval;
        }

        $retval = array();
        $item_id = DB_escapeString($item_id);
        $sql = "SELECT * FROM {$_TABLES['library.instances']}
                WHERE item_id = '$item_id'";
        switch ($avail) {
        case LIB_STATUS_AVAIL:
            $sql .= ' AND uid = 0';
            break;
        case LIB_STATUS_OUT:
            $sql .= ' AND uid > 0';
            break;
        }
        //echo $sql;die;
        $res = DB_query($sql);
        if ($res) {
            while ($A = DB_fetchArray($res, false)) {
                $retval[] = new self($A);
            }
        }
        Cache::set($key, $retval, array('instance', $item_id));
        return $retval;
    }


    /**
     * Check out a specific instance.
     *
     * @param   object  $instance   Item instance
     * @param   integer $to         User ID
     * @param   integer $due        Due date (timestamp)
     */
    public static function checkOut($instance, $to, $due)
    {
        global $_TABLES, $_USER;

        $me = (int)$_USER['uid'];
        $to = (int)$to;
        $due = (int)$due;
        $sql = "UPDATE {$_TABLES['library.instances']} SET
                uid = '$to',
                checkout = UNIX_TIMESTAMP(),
                due = '$due'
                WHERE instance_id = {$instance->instance_id}";
        Cache::clear(array('instance', $instance->item_id));
        DB_query($sql);

        // Insert the trasaction record
        $sql = "INSERT INTO {$_TABLES['library.log']} SET
                    item_id = '{$instance->item_id}',
                    instance_id = '{$instance->instance_id}',
                    dt = UNIX_TIMESTAMP(),
                    doneby = $me,
                    uid = $to,
                    trans_type = 'checkout'";
        DB_query($sql);
    }


    /**
     * Check in the current instance of an item.
     */
    public function checkIn()
    {
        global $_TABLES, $_USER;

        if ($this->instance_id < 1) {
            COM_errorLog('Invalid instance being checked in');
            return;
        }

        DB_query("UPDATE {$_TABLES['library.instances']} SET
                    uid = 0,
                    checkout = 0,
                    due = 0
                WHERE instance_id='{$this->instance_id}'");
        Cache::clear(array('instance', $this->item_id));

        // Insert the trasaction record, only if it's checked out.
        $me = isset($_USER['uid']) ? (int)$_USER['uid'] : 0;
        if ($me > 1) {
            DB_query("INSERT INTO {$_TABLES['library.log']} SET
                    item_id = '{$this->item_id}',
                    instance_id = $this->instance_id,
                    dt = UNIX_TIMESTAMP(),
                    doneby = $me,
                    uid = {$this->uid},
                    trans_type = 'checkin'");
        }
    }


    /**
     * Get all instance records for a given user.
     *
     * @param   string  $uid    User ID
     * @return  array       Array of waitlist records
     */
    public static function checkedoutByUser($uid)
    {
        global $_TABLES;
        static $items = array();

        if (!isset($items[$uid])) {
            $sql = "SELECT * FROM {$_TABLES['library.instances']}
                    WHERE uid = '" . (int)$uid . "'";
            $res = DB_query($sql);
            $items[$uid] = DB_fetchAll($res, false);
        }
        return $items[$uid];
    }


    /**
     * Get a count of all items that a given user has checked out.
     *
     * @uses    self::checkedoutByUser()
     * @param   integer $uid    User ID
     * @return  integer         Count of items
     */
    public static function countByUser($uid)
    {
        $items = self::checkedoutByUser($uid);
        return count($items);
    }


    /**
     * Check if a given user has an item checked out.
     *
     * @uses    self::checkedoutByUser()
     * @param   string  $item_id    Item ID
     * @param   integer $uid        User ID
     * @return  boolean     True if the user has checked out the item
     */
    public static function UserHasItem($item_id, $uid)
    {
        $items = self::checkedoutByUser($uid);
        $key = array_search($item_id, array_column($items, 'item_id'));
        return $key === false ? false : true;
    }

}   // class Instance

?>
