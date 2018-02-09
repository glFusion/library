<?php
/**
*   Class to manage library item categories
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Library;

/**
*   Class for categories
*   @package library
*/
class Category
{
    /** Property fields.  Accessed via Set() and Get()
    *   @var array */
    var $properties;

    /** Indicate whether the current user is an administrator
    *   @var boolean */
    var $isAdmin;

    var $isNew;

    //var $button_types = array();

    /** Array of error messages, to be accessible by the calling routines.
    *   @var array */
    public $Errors = array();


    /**
    *   Constructor.
    *   Reads in the specified class, if $id is set.  If $id is zero, 
    *   then a new entry is being created.
    *
    *   @param integer $id Optional type ID
    */
    public function __construct($id=0, $data=NULL)
    {
        $this->properties = array();
        //$this->button_types = array('buy_now', 'add_cart'); // TODO

        $this->isNew = true;

        $id = (int)$id;
        if ($id < 1) {
            $this->cat_id = 0;
            $this->parent_id = 0;
            $this->cat_name = '';
            $this->description = '';
            $this->group_id = '';
            $this->owner_id = 0;
            $this->perm_owner = 3;
            $this->perm_group = 3;
            $this->perm_members = 2;
            $this->perm_anon = 2;
            //$this->image = '';
            $this->enabled = 1;
        } else {
            $this->cat_id = $id;
            if ($data !== NULL) {
                $this->SetVars($data, true);
                $this->isNew = false;
            } elseif ($this->Read()) {
                $this->isNew = false;
            } else {
                $this->cat_id = 0;
                $this->isNew = true;
            }
        }
        $this->isAdmin = SEC_hasRights('library.admin') ? 1 : 0;
    }


    /**
    *   Set a property's value.
    *   Emulates the __set() magic function in PHP 5.
    *
    *   @param  string  $var    Name of property to set.
    *   @param  mixed   $value  New value for property.
    */
    public function __set($var, $value='')
    {
        switch ($var) {
        case 'cat_id':
        case 'parent_id':
        case 'perm_owner':
        case 'perm_group':
        case 'perm_members':
        case 'perm_anon':
        case 'group_id':
        case 'owner_id':
            // Integer values
            $this->properties[$var] = (int)$value;
            break;

        case 'cat_name':
        case 'disp_name':
        case 'description':
        //case 'image':
            // String values
            $this->properties[$var] = trim($value);
            break;

        case 'enabled':
            // Boolean values
            $this->properties[$var] = $value == 1 ? 1 : 0;
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
    *   Get the value of a property.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @return mixed           Value of property, NULL if undefined.
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
    *   Sets all variables to the matching values from $rows
    *
    *   @param array $row Array of values, from DB or $_POST
    */
    public function setVars($row)
    {
        if (!is_array($row)) return;

        $this->cat_id = $row['cat_id'];
        $this->parent_id = $row['parent_id'];
        $this->description = $row['description'];
        $this->enabled = $row['enabled'];
        $this->cat_name = $row['cat_name'];
        $this->disp_name = isset($row['disp_name']) ? $row['disp_name'] : $row['cat_name'];
        //$this->perm_owner = $row['perm_owner'];
        //$this->perm_group = $row['perm_group'];
        //$this->perm_members = $row['perm_members'];
        //$this->perm_anon = $row['perm_anon'];
        //$this->keywords = $row['keywords'];
        //$this->image = $row['image'];
        //$this->group_id = $row['group_id'];
        //$this->owner_id = $row['owner_id'];
    }


    /**
    *   Read a specific record and populate the local values.
    *
    *   @param  integer $id Optional ID.  Current ID is used if zero.
    *   @return boolean     True if a record was read, False on failure
    */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) $id = $this->cat_id;
        if ($id == 0) {
            $this->error = 'Invalid ID in Read()';
            return;
        }

        $result = DB_query("SELECT * 
                    FROM {$_TABLES['library.categories']} 
                    WHERE cat_id='$id'");
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->setVars($row);
            $this->isNew = false;
            return true;
        }
    }


    /**
    *   Save the current values to the database.
    *
    *   @param  array   $A      Optional array of values from $_POST
    *   @return boolean         True if no errors, False otherwise
    */
    public function Save($A = array())
    {
        global $_TABLES, $_CONF_LIB;

        if (is_array($A)) {
            $this->setVars($A);
        }

        // Handle image uploads.
        // We don't want to delete the existing image if one isn't 
        // uploaded, we should leave it unchanged.  So we'll first 
        // retrieve the existing image filename, if any.
        /*if (!$this->isNew) {
            $img_filename = DB_getItem($_TABLES['library.categories'], 
                        'image', "cat_id={$this->cat_id}");
        } else {
            // New entry, assume no image
            $img_filename = '';
        }
        if (is_uploaded_file($_FILES['imagefile']['tmp_name'])) {
            $img_filename =  rand(100,999) .  "_" . 
                     COM_sanitizeFilename($_FILES['imagefile']['name'], true);
            if (!@move_uploaded_file($_FILES['imagefile']['tmp_name'],
                            $_CONF_LIB['catimgpath']."/$img_filename")) {
                $this->AddError('Error Moving Image');
            } else {
                // If a new image was uploaded, and this is an existing category,
                // then delete the old image, if any.  The DB still has the old 
                // filename at this point.
                if (!$this->isNew) {
                    $this->DeleteImage();
                }
            }
        }
        $this->image = $img_filename;
        */

        // Insert or update the record, as appropriate, as long as a
        // previous error didn't occur.
        if (empty($this->Errors)) {
            if ($this->isNew) {
                $sql1 = "INSERT INTO {$_TABLES['library.categories']} SET ";
                $sql3 = '';
            } else {
                $sql1 = "UPDATE {$_TABLES['library.categories']} SET ";
                $sql3 = " WHERE cat_id = {$this->cat_id}";
            }
            $sql2 = "parent_id='{$this->parent_id}',
                cat_name='" . DB_escapeString($this->cat_name) . "',
                description='" . DB_escapeString($this->description) . "',
                enabled='{$this->enabled}',
                owner_id='{$this->owner_id}',
                group_id='{$this->group_id}',
                perm_owner='{$this->perm_owner}',
                perm_group='{$this->pern_group}',
                perm_members='{$this->perm_members}',
                perm_anon='{$this->perm_anon}'";
//                image='" . DB_escapeString($this->image) . "'";
            DB_query($sql1 . $sql2 . $sql3, 1);
            if (DB_error()) {
                $this->AddError('Failed to insert or update record');
            }
        }

        if (empty($this->Errors)) {
            self::rebuildTree(1, 1);
            PLG_itemSaved($this->cat_id, 'classifieds_category');
            return true;
        } else {
            return false;
        }
    }


    /**
    *   Delete the current category record from the database
    */
    public function Delete()
    {
        global $_TABLES;

        // Can't delete root category
        if ($this->cat_id <= 1)
            return false;

        $this->DeleteImage();
        DB_delete($_TABLES['library.categories'],
                'cat_id', $this->cat_id);
        $this->cat_id = 0;
        return true;
    }


    /**
    *   Deletes a single image from disk.
    *   Only needs the $img_id value, so this function may be called as a
    *   standalone function.
    *
    *   @param  integer $img_id     DB ID of image to delete
    */
    public function DeleteImage()
    {
        global $_TABLES, $_CONF_LIB;

        $filename = $this->image;
        if (file_exists("{$_CONF_LIB['catimgpath']}/{$filename}"))
                unlink( "{$_CONF_LIB['catimgpath']}/{$filename}" );

        DB_query("UPDATE {$_TABLES['library.categories']}
                SET image=''
                WHERE cat_id='{$this->cat_id}'");
        $this->image = '';
    }


    /**
    *   Determines if the current record is valid.
    *
    *   @return boolean     True if ok, False when first test fails.
    */
    public function isValidRecord()
    {
        // Check that basic required fields are filled in
        if ($this->cat_name == '') {
            return false;
        }

        return true;
    }


    /**
    *   Creates the edit form.
    *
    *   @param  integer $id Optional ID, current record used if zero
    *   @return string      HTML for edit form
    */
    public function showForm()
    {
        global $_TABLES, $_CONF, $_CONF_LIB, $LANG_LIB;

        if ($this->cat_id > 0) {
            $retval = COM_startBlock($LANG_LIB['edit'] . ': ' . $this->cat_name);
        } else {
            $retval = COM_startBlock($LANG_LIB['create_category']);
        }

        $T = new \Template(LIBRARY_PI_PATH . '/templates');
        $T->set_file(array('category' => 'category_form.thtml'));
        $T->set_var(array(
            'cat_id'        => $this->cat_id,
            'action_url'    => LIBRARY_ADMIN_URL,
            'cat_name'      => $this->cat_name,
            'description'   => $this->description,
            'ena_chk'       => $this->enabled == 1 ? 'checked="checked"' : '',
            'parent_sel' => self::buildSelection(self::getParent($this->cat_id), $this->cat_id),
        ) );

        if ($this->image != '') {
            $T->set_var(array(
                'img_url', LIBRARY_PI_URL . '/images/categories/' . 
                    $this->image,
            ) );
        }

        /*
        // Might want this later to set default buttons per category
        $T->set_block('product', 'BtnRow', 'BRow');
        foreach ($LANG_LIB['buttons'] as $key=>$value) {
            $T->set_var(array(
                'btn_type'  => $key,
                'btn_chk'   => isset($this->buttons[$key]) ? 
                                'checked="checked"' : '',
                'btn_name'  => $value,
            ));
            $T->parse('BRow', 'BtnRow', true);
        }*/

        if ($this->image != '') {
            $T->set_var('img_url', 
                    LIBRARY_URL . "/images/categories/{$this->image}");
            $T->set_var('txt_delete', $LANG_ADVT['delete']);
            $T->set_var('del_img_url', LIBRARY_ADMIN_URL . '/index.php' .
                        '?mode=delete_img' .
                        "&img_id={$prow['img_id']}".
                        "&id={$this->id}" );
        }

        $retval .= $T->parse('output', 'category');

        @setcookie($_CONF['cookie_name'].'fckeditor', 
                SEC_createTokenGeneral('advancededitor'),
                time() + 1200, $_CONF['cookie_path'],
                $_CONF['cookiedomain'], $_CONF['cookiesecure']);

        $retval .= COM_endBlock();
        return $retval;
    }


    /**
    *   Sets the "enabled" field to the specified value.
    *
    *   @param  integer $id ID number of element to modify
    *   @param  integer $value New value to set
    *   @return         New value, or old value upon failure
    */
    private static function _toggle($oldvalue, $varname, $id=0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) {
            if (is_object($this))
                $id = $this->id;
            else
                return;
        }

        // If it's still an invalid ID, return the old value
        if ($id < 1)
            return $oldvalue;

        // Determing the new value (opposite the old)
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $sql = "UPDATE {$_TABLES['library.categories']}
                SET $varname=$newvalue
                WHERE cat_id=$id";
        //echo $sql;die;
        DB_query($sql);

        return $newvalue;
    }


    /**
    *   Sets the "enabled" field to the specified value.
    *
    *   @param  integer $id ID number of element to modify
    *   @param  integer $value New value to set
    *   @return         New value, or old value upon failure
    */
    public static function toggleEnabled($oldvalue, $id=0)
    {
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $id = (int)$id;
        if ($id == 0) {
            return $oldvalue;
        }
        return self::_toggle($oldvalue, 'enabled', $id);
    }


    /**
    *   Determine if this product is mentioned in any purchase records.
    *   Typically used to prevent deletion of product records that have
    *   dependencies.
    *
    *   @return boolean True if used, False if not
    */
    public static function isUsed($cat_id=0)
    {
        global $_TABLES;

        // Always treat root category as in_use
        if ($cat_id == 1) return true;

        // Check if any products are under this category
        //if (DB_count($_TABLES['library.prodXcat'], 'cat_id', $cat_id) > 0) {
        if (DB_count($_TABLES['library.items'], 'cat_id', $cat_id) > 0) {
            return true;
        }
        return false;
    }


    /**
    *   Add an error message to the Errors array.  Also could be used to
    *   log certain errors or perform other actions.
    *
    *   @param  string  $msg    Error message to append
    */
    public function AddError($msg)
    {
        $this->Errors[] = $msg;
    }


    /**
    *   Create a formatted display-ready version of the error messages.
    *
    *   @return string      Formatted error messages.
    */
    public function PrintErrors()
    {
        $retval = '';

        foreach($this->Errors as $key=>$msg) {
            $retval .= "<li>$msg</li>\n";
        }
        return $retval;
    }


    /**
    *   Get the ID of the immediate parent for a given category.
    *
    *   @param  integer $cat_id     Current category ID
    *   @return integer     ID of parent category.
    */
    public static function getParent($cat_id)
    {
        global $_TABLES;

        $cat_id = (int)$cat_id;
        $parent_id = 0;
        $res = DB_query("SELECT parent.cat_id, parent.cat_name
                FROM {$_TABLES['library.categories']} AS node,
                    {$_TABLES['library.categories']} AS parent
                WHERE node.lft BETWEEN parent.lft AND parent.rgt
                AND node.cat_id = $cat_id
                ORDER BY parent.lft DESC LIMIT 2");
        while ($A = DB_fetchArray($res, false)) {
            $parent_id = $A['cat_id'];
        }
        return ($parent_id == $cat_id) ? NULL : $parent_id;
    }
 

    /**
    *   Recurse through the category table building an option list
    *   sorted by id.
    *
    *   @param integer  $sel        Category ID to be selected in list
    *   @param integer  $root       Root category ID
    *   @param string   $char       Indenting characters
    *   @param string   $not        'NOT' to exclude $items, '' to include
    *   @param string   $items      Optional comma-separated list of items to include or exclude
    *   @return string              HTML option list, without <select> tags
    */
    public static function buildSelection($sel=0, $self=0)
    {
        global $_TABLES;

        $str = '';
        $root = 1;
        $Cats = self::getTree($root);
        foreach ($Cats as $Cat) {
            if ($Cat->cat_id == $root) {
                continue;       // Don't include the root category
            } elseif ($self == $Cat->cat_id) {
                // Exclude self when building parent list
                $disabled = 'disabled="disabled"';
            } elseif (SEC_hasAccess($Cat->owner_id, $Cat->group_id,
                    $Cat->perm_owner, $Cat->perm_group,
                    $Cat->perm_members, $Cat->perm_anon) < 3) {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }
            $selected = $Cat->cat_id == $sel ? 'selected="selected"' : '';
            $str .= "<option value=\"{$Cat->cat_id}\" $selected $disabled>";
            $str .= $Cat->disp_name;
            $str .= "</option>\n";
        }
        return $str;
    }


    /**
    *   Read all the categories into a static array.
    *
    *   @param  integer $root   Root category ID
    *   @return array           Array of category objects
    */
    public static function getTree($root=0, $prefix='&nbsp;')
    {
        global $_TABLES;

        $All = array();

        if (!empty($root)) {
            $result = DB_query("SELECT lft, rgt FROM {$_TABLES['library.categories']}
                        WHERE cat_id = $root");
            $row = DB_fetchArray($result, false);
            $between = ' AND parent.lft BETWEEN ' . (int)$row['lft'] .
                        ' AND ' . (int)$row['rgt'];
        } else {
            $between = '';
        }

        $prefix = DB_escapeString($prefix);
        $sql = "SELECT node.*, CONCAT( REPEAT( '$prefix', (COUNT(parent.cat_name) - 1) ), node.cat_name) AS disp_name
            FROM {$_TABLES['library.categories']} AS node,
                {$_TABLES['library.categories']} AS parent
            WHERE node.lft BETWEEN parent.lft AND parent.rgt
            $between
            GROUP BY node.cat_name
            ORDER BY node.lft";
        //echo $sql;die;
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $All[$A['cat_id']] = new self($A['cat_id'], $A);
        }
        return $All;
    }


    /**
    *   Rebuild the MPT tree starting at a given parent and "left" value
    *
    *   @param  integer $parent     Starting category ID
    *   @param  integer $left       Left value of the given category
    *   @return integer         New Right value (only when called recursively)
    */
    public static function rebuildTree($parent, $left)
    {
        global $_TABLES;

        // the right value of this node is the left value + 1
        $right = $left + 1;

        // get all children of this node
        $sql = "SELECT cat_id FROM {$_TABLES['library.categories']}
                WHERE parent_id ='$parent'";
        $result = DB_query($sql);
        while ($row = DB_fetchArray($result, false)) {
            // recursive execution of this function for each
            // child of this node
            // $right is the current right value, which is
            // incremented by the rebuild_tree function
            $right = self::rebuildTree($row['cat_id'], $right);
        }

        // we've got the left value, and now that we've processed
        // the children of this node we also know the right value
        $sql1 = "UPDATE {$_TABLES['library.categories']}
                SET lft = '$left', rgt = '$right'
                WHERE cat_id = '$parent'";
        DB_query($sql1);

        // return the right value of this node + 1
        return $right + 1;
    }

}   // class Category

?>
