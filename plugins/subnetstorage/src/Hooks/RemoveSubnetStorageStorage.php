<?php
/**
 * Removes the subnet storage entries of deleted storage items.
 *
 * PHP version 7.4+
 *
 * @category RemoveSubnetStorageStorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Hooks;

/**
 * Removes the subnet storage entries of deleted storage items.
 *
 * @category RemoveSubnetStorageStorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class RemoveSubnetStorageStorage extends \FOG\Base\Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'RemoveSubnetStorageStorage';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Removes the subnet storage entries of deleted storage items.';
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
            ['STORAGEGROUP_DELETE_SUCCESS', 'removeStorageGroup'],
            ['STORAGENODE_DELETE_SUCCESS', 'removeStorageNode'],
            ['DELETEMASS_API', 'deleteMassItems'],
        ]);
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
        $this->_removeGroups([$arguments['StorageGroup']->get('id')]);
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
        $this->_unsetNodes([$arguments['StorageNode']->get('id')]);
    }
    /**
     * Follows the storage groups and nodes deleted in bulk.
     *
     * @param mixed $arguments The arguments to evaluate.
     *
     * @return void
     */
    public function deleteMassItems($arguments)
    {
        switch ($arguments['classname']) {
            case 'storagegroup':
                $arguments['removeItems'][$this->node] = [
                    'storagegroupID' => $arguments['itemIDs']
                ];
                break;
            case 'storagenode':
                $this->_unsetNodes((array)$arguments['itemIDs']);
                break;
        }
    }
    /**
     * Deletes the entries pointing at storage groups.
     *
     * @param array $ids the storage group ids
     *
     * @return void
     */
    private function _removeGroups(array $ids)
    {
        \FOG\Router\Route::deletemass(
            $this->node,
            ['storagegroupID' => $ids]
        );
    }
    /**
     * Lets the group choose the node of entries pointing at storage nodes.
     *
     * @param array $ids the storage node ids
     *
     * @return void
     */
    private function _unsetNodes(array $ids)
    {
        (new \FOG\Plugins\SubnetStorage\Managers\SubnetStorageManager())
            ->update(
                ['storagenodeID' => $ids],
                '',
                ['storagenodeID' => null]
            );
    }
}
