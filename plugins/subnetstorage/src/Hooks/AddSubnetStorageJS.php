<?php
/**
 * Adds the subnet storage JS files.
 *
 * PHP version 7.4+
 *
 * @category AddSubnetStorageJS
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Hooks;

/**
 * Adds the subnet storage JS files.
 *
 * @category AddSubnetStorageJS
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddSubnetStorageJS extends \FOG\Base\Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'AddSubnetStorageJS';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Adds the subnet storage JS files.';
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
            ['PAGE_JS_FILES', 'injectJSFiles'],
        ]);
    }
    /**
     * Injects the JS file of the current page.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function injectJSFiles($arguments)
    {
        $this->injectPluginJS($arguments, [
            'subnetstorage' => [],
        ]);
    }
}
