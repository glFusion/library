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
    /** Instance DB record ID.
     * @var integer */
    private $instance_id = 0;

    /** ID of user who has the instance checked out.
     * @var integer */
    private $uid = 0;

    /** Due date object.
     * @var integer */
    private $due = NULL;

    /** Parent item ID.
     * @var string */
    private $item_id = '';


    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   integer $id Optional instance ID
     */
    public function __construct($id=0)
    {
        if (is_array($id)) {
            // Got a DB record, just load the values
            $this->setVars($id);
        } else {
            // Got an instance ID, read the item and load
            $id = (int)$id;
            $this->instance_id = $id;
            if (!$this->Read()) {
                $this->instance_id = 0;
                $this->setDueDate(0);
            }
        }
    }


    /**
     * Get the user ID who has the item checked out.
     *
     * @return  integer     User ID
     */
    public function getUid()
    {
        return (int)$this->uid;
    }


    /**
     * Set the due date for this instance.
     *
     * @param   string|integer  $ts     Timestamp or date string
     * @return  object  $this
     */
    private function setDueDate($ts=0)
    {
        global $_CONF;

        $this->due = new \Date($ts, $_CONF['timezone']);
        return $this;
    }


    /**
     * Get the due date for this instance.
     *
     * @return  string|object   Formatted date or date object
     */
    public function getDueDate($format=NULL)
    {
        if ($format === NULL) {
            return $this->due;
        } else {
            return $this->due->format($format,true);
        }
    }


    /**
     * Get the DB record ID for this instance.
     *
     * @return  integer     DB record ID
     */
    public function getID()
    {
        return (int)$this->instance_id;
    }


    /**
     * Get the parent item record ID.
     *
     * @return  string      Parent item id
     */
    public function getItemID()
    {
        return $this->item_id;
    }


    /**
     * Sets all variables to the matching values from $rows
     *
     * @param   array $row Array of values, from DB or $_POST
     */
    public function setVars($row)
    {
        global $_CONF;

        if (!is_array($row)) return;

        $this->instance_id = (int)$row['instance_id'];
        $this->item_id = $row['item_id'];
        $this->uid = (int)$row['uid'];
        $this->setDueDate((int)$row['due']);
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
        global $_TABLES;

        // Can't delete new or currently checked-out instances
        if ($this->instance_id <= 0 || $this->uid > 0)
            return false;

        DB_delete(
            $_TABLES['library.instances'],
            'instance_id',
            $this->instance_id
        );

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
                WHERE instance_id = {$instance->getID()}";
        Cache::clear(array('instance', $instance->getItemID()));
        DB_query($sql);

        // Insert the trasaction record
        $sql = "INSERT INTO {$_TABLES['library.log']} SET
                    item_id = '{$instance->getID()}',
                    instance_id = '{$instance->getItemID()}',
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

        $sql = "UPDATE {$_TABLES['library.instances']} SET
                    uid = 0,
                    checkout = 0,
                    due = 0
                    WHERE instance_id='{$this->instance_id}'";
        DB_query($sql);
        Cache::clear(array('instance', $this->item_id));

        // Insert the trasaction record, only if it's checked out.
        $me = isset($_USER['uid']) ? (int)$_USER['uid'] : 0;
        if ($me > 1) {
            $sql = "INSERT INTO {$_TABLES['library.log']} SET
                    item_id = '{$this->item_id}',
                    instance_id = $this->instance_id,
                    dt = UNIX_TIMESTAMP(),
                    doneby = $me,
                    uid = {$this->uid},
                    trans_type = 'checkin'";
            DB_query($sql);
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
     * Get the admin list of item instances.
     *
     * @param   string  $item_id    Item ID
     * @param   integer $status     Optional item status, to limit view
     * @return  string      HTML for admin list
     */
    public static function adminlist($item_id=0, $status=0)
    {
        global $_CONF, $_TABLES, $_USER;

        $display = '';

        $sql = "SELECT inst.*, item.title
            FROM {$_TABLES['library.instances']} inst
            LEFT JOIN {$_TABLES['library.items']} item
                ON item.id = inst.item_id ";
        $stat_join = '';
        switch ($status) {
        case 0:     // All
            $stat_sql = ' WHERE 1=1 ';
            break;
        case 1:     // Available
            $stat_sql = ' WHERE inst.uid = 0 ';
            break;
        case 2:     // Checked Out
            $stat_sql = ' WHERE inst.uid > 0 ';
            break;
        case 3:     // Pending Actions, include available only
            $stat_sql = ' GROUP BY w.item_id HAVING count(w.id) > 0 ';
            $stat_join = "LEFT JOIN {$_TABLES['library.waitlist']} w
                ON item.id = w.item_id";
            break;
        case 4:     // Overdue
            $stat_sql = ' WHERE inst.due > 0 AND inst.due < UNIX_TIMESTAMP() ';
            break;
        }
        $sql .= $stat_join;
        $sql .= $stat_sql;
        if (!empty($item_id)) {
            $sql .= " AND inst.item_id = '" . DB_escapeString($item_id) . "'";
        }

        $header_arr = array(
            array(
                'text'  => 'ID',
                'field' => 'instance_id',
                'sort'  => true,
            ),
            array(
                'text'  => _('Item ID'),
                'field' => 'item_id',
                'sort'  => true,
            ),
            array(
                'text'  => _('Check out to user'),
                'field' => 'uid',
                'sort'  => true,
            ),
            array(
                'text'  => _('Checked Out'),
                'field' => 'checkout',
                'sort'  => true,
            ),
            array(
                'text'  => _('Due Date'),
                'field' => 'due',
                'sort'  => true,
            ),
            array(
                'text'  => _('Check In'),
                'field' => 'checkin',
                'sort'  => false,
            ),
            array(
                'text'  => _('Delete'),
                'field' => 'delete',
                'sort'  => true,
            ),
        );

        $defsort_arr = array(
            'field' => 'inst.due',
            'direction' => 'desc',
        );

        $display .= COM_startBlock(
            '', '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $query_arr = array(
            'table' => 'library.instances',
            'sql' => $sql,
            'query_fields' => array(),
            'default_filter' => '',
        );
        $filter = '';
        $text_arr = array(
            //'has_extras' => true,
            'form_url' => Config::getInstance()->get('admin_url') . '/index.php?status=' . $status,
        );
        $form_arr = LIBRARY_itemStatusForm($status, $item_id);
        $extras = array();
        $display .= ADMIN_list(
            'library',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, $extras, '', $form_arr
        );
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $display;
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


    /**
     * Get an individual field for the Instance Admin screen.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @return  string              HTML for field display in the table
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_TABLES;

        $retval = '';
        static $usernames = array();
        switch($fieldname) {
        case 'uid':
            if ($fieldvalue > 0) {
                if (!isset($usernames[$fieldvalue])) {
                    $usernames[$fieldvalue] = COM_getDisplayName($fieldvalue);
                }
                $retval .= $usernames[$fieldvalue];
            }
            break;
        case 'checkout':
        case 'due':
            if ($fieldvalue > 0) {
                $dt = new \Date($fieldvalue, $_CONF['timezone']);
                $retval .= $dt->format('Y-m-d', true);
            }
            break;
        case 'checkin':
            if ($A['uid'] > 0) {
                $retval .= COM_createLink(
                    _('Check In'),
                    Config::getInstance()->get('admin_url') . '/index.php?checkinform=x&id=' . $A['item_id']
                );
            }
            break;
        case 'delete':
            if ($A['uid'] == 0) {
                $retval .= COM_createLink(
                    Icon::getHTML('delete'),
                    Config::getInstance()->get('admin_url') . '/index.php?deleteinstance=x&amp;id=' . $A['instance_id'],
                    array(
                        'onclick'=>'return confirm(\''.
                        _('Are you sure you want to delete this item?').
                        '\');',
                        'title' => _('Delete Item'),
                        'class' => 'tooltip',
                    )
                );
            }
            break;
        case 'item_id':
            $retval .= '<span title="' . htmlspecialchars($A['title']) . '" class="tooltip">' . $fieldvalue . '</span>';
            break;
        default:
            $retval .= $fieldvalue;
            break;
        }
        return $retval;
    }

}   // class Instance

?>
