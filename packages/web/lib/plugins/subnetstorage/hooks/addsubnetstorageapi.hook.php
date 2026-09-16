<?php
/**
 * Injects subnet storage stuff into the api system.
 *
 * PHP version 7.4+
 *
 * @category AddSubnetStorageAPI
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Injects subnet storage stuff into the api system.
 *
 * @category AddSubnetStorageAPI
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddSubnetStorageAPI extends Hook
{
    /**
     * The name of the hook.
     *
     * @var string
     */
    public $name = 'AddSubnetStorageAPI';
    /**
     * The hook description.
     *
     * @var string
     */
    public $description = 'Add SubnetStorage stuff into the api system.';
    /**
     * For posterity.
     *
     * @var bool
     */
    public $active = true;
    /**
     * The node this plugin works from.
     *
     * @var string
     */
    public $node = 'subnetstorage';
    /**
     * Initialize object.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        self::$HookManager
            ->register(
                'API_VALID_CLASSES',
                array(
                    $this,
                    'injectAPIElements'
                )
            );
    }
    /**
     * This function injects subnetstorage elements for api access.
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function injectAPIElements($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        $arguments['validClasses'] = self::fastmerge(
            $arguments['validClasses'],
            array(
                'subnetstorage'
            )
        );
    }
}
