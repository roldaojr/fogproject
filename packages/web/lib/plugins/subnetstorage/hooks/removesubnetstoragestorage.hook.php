<?php
/**
 * Removes the subnet storage entries of deleted storage items.
 *
 * PHP version 7.4+
 *
 * @category RemoveSubnetstorageStorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Removes the subnet storage entries of deleted storage items.
 *
 * @category RemoveSubnetstorageStorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class RemoveSubnetstorageStorage extends Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'RemoveSubnetstorageStorage';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Remove Subnet Storage of deleted storage items';
    /**
     * The active flag.
     *
     * @var bool
     */
    public $active = true;
    /**
     * The node this hook enacts with.
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
                'STORAGE_GROUP_DELETE_POST_SUCCESS',
                array(
                    $this,
                    'removeStorageGroup'
                )
            )
            ->register(
                'STORAGE_NODE_DELETE_SUCCESS',
                array(
                    $this,
                    'removeStorageNode'
                )
            );
    }
    /**
     * Removes the entries of a deleted storage group.
     *
     * @param mixed $arguments The arguments to evaluate.
     *
     * @return void
     */
    public function removeStorageGroup($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        self::getClass('SubnetstorageManager')
            ->destroy(
                array(
                    'storagegroupID' => $arguments['StorageGroup']->get('id')
                )
            );
    }
    /**
     * Unsets the node of the entries of a deleted storage node.
     *
     * @param mixed $arguments The arguments to evaluate.
     *
     * @return void
     */
    public function removeStorageNode($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        $find = array(
            'storagenodeID' => $arguments['StorageNode']->get('id')
        );
        foreach ((array)self::getClass('SubnetstorageManager')
            ->find($find) as &$Subnetstorage
        ) {
            $Subnetstorage
                ->set('storagenodeID', 0)
                ->save();
            unset($Subnetstorage);
        }
    }
}
