<?php

/**
 * @author     Guillermo Vargas <guille@vargas.co.cr>
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

class XmapDisplayer
{
    /**
     * @var int  Counter for the number of links on the sitemap
     */
    protected $count;

    /**
     * @var JViewLegacy
     */
    protected $jview;

    /**
     * @var mixed state from params
     * @todo get type of var
     */
    public $config;

    /**
     * @var mixed
     * @todo get type of var
     */
    public $sitemap;

    /**
     * @var int   Current timestamp
     */
    public $now;

    /**
     * @var array
     */
    public $userLevels;

    /**
     * @var string  The current value for the request var "view" (eg. html, xml)
     */
    public $view;

    /**
     * @var bool
     */
    public $canEdit;

    public function __construct($config, $sitemap)
    {
        $this->userLevels = JFactory::getUser()->getAuthorisedViewLevels();
        $this->now = JFactory::getDate()->toUnix();
        $this->config = $config;
        $this->sitemap = $sitemap;
        $this->isNews = false;
        $this->isImages = false;
        $this->count = 0;
        $this->canEdit = false;
    }

    public function printNode(&$node)
    {
        return false;
    }

    public function printSitemap()
    {
        foreach ($this->jview->items as $menutype => &$items) {

            $node = new stdclass();

            $node->uid = "menu-" . $menutype;
            $node->menutype = $menutype;
            $node->priority = null;
            $node->changefreq = null;
            // $node->priority = $menu->priority;
            // $node->changefreq = $menu->changefreq;
            $node->browserNav = 3;
            $node->type = 'separator';
            /**
             * @todo allow the user to provide the module used to display that menu, or some other
             * workaround
             */
            $node->name = $this->getMenuTitle($menutype, 'mod_menu'); // Get the name of this menu

            $this->startMenu($node);
            $this->printMenuTree($node, $items);
            $this->endMenu($node);
        }
    }

    public function setJView($view)
    {
        $this->jview = $view;
    }

    public function getMenuTitle($menutype, $module = 'mod_menu')
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $title = $extra = '';

        // Filter by language
        if ($app->getLanguageFilter()) {
            $extra = ' AND language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
        }

        // TODO JDatabaseQuery usage
        $db->setQuery(
            "SELECT * FROM #__modules WHERE module='{$module}' AND params "
            . "LIKE '%\"menutype\":\"{$menutype}\"%' AND access IN (" . implode(',', $this->userLevels) . ") "
            . "AND published=1 AND client_id=0 "
            . $extra
            . "LIMIT 1"
        );
        $module = $db->loadObject();
        if ($module) {
            $title = $module->title;
        }
        return $title;
    }

    protected function startMenu(&$node)
    {
        return true;
    }

    protected function endMenu(&$node)
    {
        return true;
    }

    protected function printMenuTree($menu, &$items)
    {
        $this->changeLevel(1);

        $router = JSite::getRouter();

        foreach ($items as $i => $item) {                   // Add each menu entry to the root tree.
            $excludeExternal = false;

            $node = new stdclass;

            $node->id = $item->id;
            $node->uid = $item->uid;
            $node->name = $item->title;               // displayed name of node
            // $node->parent    = $item->parent;              // id of parent node
            $node->browserNav = $item->browserNav;          // how to open link
            $node->priority = $item->priority;
            $node->changefreq = $item->changefreq;
            $node->type = $item->type;                // menuentry-type
            $node->menutype = $menu->menutype;            // menuentry-type
            $node->home = $item->home;                // If it's a home menu entry
            // $node->link      = isset( $item->link ) ? htmlspecialchars( $item->link ) : '';
            $node->link = $item->link;
            $node->option = $item->option;
            $node->modified = @$item->modified;
            $node->secure = $item->params->get('secure');

            // New on Xmap 2.0: send the menu params
            $node->params =& $item->params;

            if ($node->home == 1) {
                // Correct the URL for the home page.
                $node->link = JURI::base();
            }
            switch ($item->type) {
                case 'separator':
                case 'heading':
                    $node->browserNav = 3;
                    break;
                case 'url':
                    if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $node->link = $node->link . '&Itemid=' . $node->id;
                    } else {
                        $excludeExternal = ($this->view == 'xml');
                    }
                    break;
                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $node->link = 'index.php?Itemid=' . $item->params->get('aliasoptions');
                    break;
                default:
                    if ($router->getMode() == JROUTER_MODE_SEF) {
                        $node->link = 'index.php?Itemid=' . $node->id;
                    } elseif (!$node->home) {
                        $node->link .= '&Itemid=' . $node->id;
                    }
                    break;
            }

            if ($excludeExternal || $this->printNode($node)) {

                //Restore the original link
                $node->link = $item->link;
                $this->printMenuTree($node, $item->items);
                $matches = array();
                //if ( preg_match('#^/?index.php.*option=(com_[^&]+)#',$node->link,$matches) ) {
                if ($node->option) {
                    if (!empty($this->jview->extensions[$node->option])) {
                        $node->uid = $node->option;
                        $className = 'xmap_' . $node->option;
                        $result = call_user_func_array(array($className, 'getTree'), array(&$this, &$node, &$this->jview->extensions[$node->option]->params));
                    }
                }
                //XmapPlugins::printTree( $this, $node, $this->jview->extensions );    // Determine the menu entry's type and call it's handler
            }
        }
        $this->changeLevel(-1);
    }

    public function changeLevel($step)
    {
        return true;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getExcludedItems()
    {
        static $_excluded_items;
        if (!isset($_excluded_items)) {
            $_excluded_items = array();
            $registry = new JRegistry('_default');
            $registry->loadString($this->sitemap->excluded_items);
            $_excluded_items = $registry->toArray();
        }
        return $_excluded_items;
    }

    public function isExcluded($itemid, $uid)
    {
        $excludedItems = $this->getExcludedItems();
        $items = NULL;
        if (!empty($excludedItems[$itemid])) {
            if (is_object($excludedItems[$itemid])) {
                $excludedItems[$itemid] = (array)$excludedItems[$itemid];
            }
            $items =& $excludedItems[$itemid];
        }
        if (!$items) {
            return false;
        }
        return (in_array($uid, $items));
    }
}
