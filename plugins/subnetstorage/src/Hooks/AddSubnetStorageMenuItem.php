<?php
/**
 * Adds the subnet storage menu item.
 *
 * PHP version 7.4+
 *
 * @category AddSubnetStorageMenuItem
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Hooks;

/**
 * Adds the subnet storage menu item.
 *
 * @category AddSubnetStorageMenuItem
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddSubnetStorageMenuItem extends \FOG\Base\Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'AddSubnetStorageMenuItem';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Adds the subnet storage menu item.';
    /**
     * The node this hook enacts with.
     *
     * @var string
     */
    public $node = 'subnetstorage';
    /**
     * Initializes object.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->registerInstalled([
            ['MAIN_MENU_DATA', 'menuData'],
            ['SEARCH_PAGES', 'addSearch'],
            ['PAGES_WITH_OBJECTS', 'addPageWithObject'],
            ['PERMISSION_REGISTRY_DATA', 'permData'],
        ]);
    }
    /**
     * Adds the sidebar entry.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function menuData($arguments)
    {
        $arguments['hook_main'][$this->node] = [
            _('Subnet Storage'),
            'fas fa-sitemap'
        ];
    }
    /**
     * Adds the page to the searchable pages.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function addSearch($arguments)
    {
        $arguments['searchPages'][] = $this->node;
    }
    /**
     * Adds the page to the pages with objects.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function addPageWithObject($arguments)
    {
        $arguments['PagesWithObjects'][] = $this->node;
    }
    /**
     * Registers the permission node.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function permData($arguments)
    {
        $arguments['registry'][$this->node] = [
            'view', 'create', 'edit', 'delete'
        ];
    }
}
