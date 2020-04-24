<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     v1.0.0
 * @since       v0.7.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;

/**
 * Class to provide admin and user-facing menus.
 * @package library
 */
class Menu
{
    /**
     * Create the user menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function User($view='')
    {
        global $_CONF, $LANG_SHOP, $_SHOP_CONF;

        USES_lib_admin();

        $hdr_txt = SHOP_getVar($LANG_SHOP, 'user_hdr_' . $view);
        $menu_arr = array(
            array(
                'url'  => SHOP_URL . '/index.php',
                'text' => $LANG_SHOP['back_to_catalog'],
            ),
        );

        $active = $view == 'orderhist' ? true : false;
        $menu_arr[] = array(
            'url'  => COM_buildUrl(SHOP_URL . '/account.php'),
            'text' => $LANG_SHOP['purchase_history'],
            'active' => $active,
        );

        // Show the Gift Cards menu item only if enabled.
        if ($_SHOP_CONF['gc_enabled']) {
            $active = $view == 'couponlog' ? true : false;
            $menu_arr[] = array(
                'url'  => COM_buildUrl(SHOP_URL . '/account.php?mode=couponlog'),
                'text' => $LANG_SHOP['gc_activity'],
                'active' => $active,
                'link_admin' => plugin_ismoderator_library(),
            );
        }
        return \ADMIN_createMenu($menu_arr, $hdr_txt);
    }


    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $_CONF_LIB;
        USES_lib_admin();

        $menu_arr = array(
            array(
                'url'   => $_CONF_LIB['admin_url'] . '/index.php',
                'text'  => _('Item List'),
                'active' => $view == 'itemlist' ? true : false,
            ),
            array(
                'url'  => $_CONF_LIB['admin_url'] . '/index.php?mode=catlist',
                'text' => _('Categories'),
                'active' => $view == 'catlist' ? true : false,
            ),
            array(
                'url'   => $_CONF_LIB['admin_url'] . '/index.php?medialist=x',
                'text'  => _('Media Types'),
                'active' => $view == 'medialist' ? true : false,
            ),
            array(
                'url'   => $_CONF_LIB['admin_url'] . '/index.php?status=4',
                'text'  => _('Overdue'),
                'active' => $view == 'overdue' ? true : false,
            ),
            array(
                'url'   => $_CONF['site_admin_url'],
                'text'  => _('Admin Home'),
            ),
        );

        $admin_hdr = 'admin_item_hdr';
        $T = new \Template($_CONF_LIB['pi_path'] . '/templates');
        $T->set_file('title', 'library_title.thtml');
        $T->set_var('title', _('Library Administration'));
        $retval = $T->parse('', 'title');
        $retval .= ADMIN_createMenu($menu_arr, '', plugin_geticon_library());
        return $retval;
    }


    /**
     * Create the administrator sub-menu for the Catalog option.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function adminCatalog($view='')
    {
        global $LANG_SHOP;

        $menu_arr = array(
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?products=x',
                'text' => $LANG_SHOP['products'],
                'active' => $view == 'products' ? true : false,
            ),
            array(
                'url' => SHOP_ADMIN_URL . '/index.php?categories=x',
                'text' => $LANG_SHOP['categories'],
                'active' => $view == 'categories' ? true : false,
            ),
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?opt_grp=x',
                'text' => $LANG_SHOP['opt_grps'],
                'active' => $view == 'opt_grp' ? true : false,
            ),
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?options=x',
                'text' => $LANG_SHOP['options'],
                'active' => $view == 'options' ? true : false,
            ),
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?sales=x',
                'text' => $LANG_SHOP['sale_prices'],
                'active' => $view == 'sales' ? true : false,
            ),
        );
        return self::_makeSubMenu($menu_arr);
    }


    /**
     * Create the administrator sub-menu for the Shipping option.
     * Includes shipper setup and shipment listing.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function adminShipping($view='')
    {
        global $LANG_SHOP;

        $menu_arr = array(
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?shipping=x',
                'text' => $LANG_SHOP['shippers'],
                'active' => $view == 'shipping' ? true : false,
            ),
            array(
                'url' => SHOP_ADMIN_URL . '/index.php?shipments=x',
                'text' => $LANG_SHOP['shipments'],
                'active' => $view == 'shipments' ? true : false,
            ),
        );
        return self::_makeSubMenu($menu_arr);
    }


    /**
     * Create the administrator sub-menu for the Orders option.
     * Includes orders and shipment listing.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function adminOrders($view='')
    {
        global $LANG_SHOP;

        $menu_arr = array(
            array(
                'url'  => SHOP_ADMIN_URL . '/index.php?orders',
                'text' => $LANG_SHOP['orders'],
                'active' => $view == 'orders' ? true : false,
            ),
            array(
                'url' => SHOP_ADMIN_URL . '/index.php?shipments=x',
                'text' => $LANG_SHOP['shipments'],
                'active' => $view == 'shipments' ? true : false,
            ),
        );
        return self::_makeSubMenu($menu_arr);
    }


    /**
     * Create a submenu using a standard template.
     *
     * @param   array   $menu_arr   Array of menu items
     * @return  string      HTML for the submenu
     */
    private static function _makeSubMenu($menu_arr)
    {
        $T = new \Template(__DIR__ . '/../templates');
        $T->set_file('menu', 'submenu.thtml');
        $T->set_block('menu', 'menuItems', 'items');
        foreach ($menu_arr as $mnu) {
            $url = COM_createLink($mnu['text'], $mnu['url']);
            $T->set_var(array(
                'active'    => $mnu['active'],
                'url'       => $url,
            ) );
            $T->parse('items', 'menuItems', true);
        }
        $T->parse('output', 'menu');
        $retval = $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Get the to-do list to display at the top of the admin screen.
     * There's probably a less sql-expensive way to do this.
     *
     * @return  array   Array of strings (the to-do list)
     */
    public static function AdminTodo()
    {
        global $_TABLES, $LANG_SHOP, $_PLUGIN_INFO;

        $todo = array();
        if (DB_count($_TABLES['library.products']) == 0) {
            $todo[] = $LANG_SHOP['todo_noproducts'];
        }

        if (DB_count($_TABLES['library.gateways'], 'enabled', 1) == 0) {
            $todo[] = $LANG_SHOP['todo_nogateways'];
        }
        if (!empty($todo) && array_key_exists('paypal', $_PLUGIN_INFO)) {
            $todo[] = $LANG_SHOP['todo_migrate_pp'];
        }
        return $todo;
    }


    /**
     * Display only the page title.
     * Used for pages that do not feature a menu, such as the catalog.
     *
     * @param   string  $page_title     Page title text
     * @param   string  $page           Page name being displayed
     * @return  string      HTML for page title section
     */
    public static function pageTitle($page_title = '', $page='')
    {
        global $_USER;

        $T = new \Template(__DIR__ . '/../templates');
        $T->set_file('title', 'library_title.thtml');
        $T->set_var(array(
            'title' => $page_title,
            'is_admin' => plugin_ismoderator_library(),
            'link_admin' => plugin_ismoderator_library(),
            'link_account' => ($page != 'account' && $_USER['uid'] > 1),
        ) );
        if ($page != 'cart' && Cart::getCart()) {
            $item_count = Cart::getInstance()->hasItems();
            if ($item_count) {
                $T->set_var('link_cart', $item_count);
            }
        }
        return $T->parse('', 'title');
    }


    /**
     * Display the site header, with or without blocks according to configuration.
     *
     * @param   string  $title  Title to put in header
     * @param   string  $meta   Optional header code
     * @return  string          HTML for site header, from COM_siteHeader()
     */
    public static function siteHeader($title='', $meta='')
    {
        global $_SHOP_CONF, $LANG_SHOP;

        $retval = '';

        switch($_SHOP_CONF['displayblocks']) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $title, $meta);
            break;

        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $title, $meta);
            break;
        }

        if (!$_SHOP_CONF['library_enabled']) {
            $retval .= '<div class="uk-alert uk-alert-danger">' . $LANG_SHOP['library_closed'] . '</div>';
        }
        return $retval;
    }


    /**
     * Display the site footer, with or without blocks as configured.
     *
     * @return  string      HTML for site footer, from COM_siteFooter()
     */
    public static function siteFooter()
    {
        global $_SHOP_CONF;

        $retval = '';

        switch($_SHOP_CONF['displayblocks']) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter();
            break;

        case 0: // none
        case 1: // left only
        default :
            $retval .= COM_siteFooter();
            break;
        }
        return $retval;
    }

}

?>


