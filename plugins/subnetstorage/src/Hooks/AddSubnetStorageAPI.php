<?php
/**
 * Adds subnet storage into the API system.
 *
 * PHP version 7.4+
 *
 * @category AddSubnetStorageAPI
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Hooks;

/**
 * Adds subnet storage into the API system.
 *
 * @category AddSubnetStorageAPI
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddSubnetStorageAPI extends \FOG\Base\Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'AddSubnetStorageAPI';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Adds subnet storage into the API system.';
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
            ['API_VALID_CLASSES', 'injectAPIElements'],
            ['API_GETTER', 'adjustGetter'],
        ]);
    }
    /**
     * Exposes the node over the API.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function injectAPIElements($arguments)
    {
        $arguments['validClasses'][] = $this->node;
    }
    /**
     * Expands the storage group and node of an item.
     *
     * @param mixed $arguments The arguments to change.
     *
     * @return void
     */
    public function adjustGetter($arguments)
    {
        if ($arguments['classname'] != $this->node) {
            return;
        }
        $arguments['data'] = \FOG\Base\FOGCore::fastmerge(
            $arguments['class']->get(),
            [
                'storagenode' => \FOG\Router\Route::getter(
                    'storagenode',
                    $arguments['class']->get('storagenode')
                ),
                'storagegroup' => \FOG\Router\Route::getter(
                    'storagegroup',
                    $arguments['class']->get('storagegroup')
                )
            ]
        );
    }
}
