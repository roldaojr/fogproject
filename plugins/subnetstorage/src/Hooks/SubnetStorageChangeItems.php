<?php
/**
 * Changes the storage items based on the subnet of the host.
 *
 * PHP version 7.4+
 *
 * @category SubnetStorageChangeItems
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Hooks;

/**
 * Changes the storage items based on the subnet of the host.
 *
 * @category SubnetStorageChangeItems
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetStorageChangeItems extends \FOG\Base\Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'SubnetStorageChangeItems';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Set storage group and node from the host subnet';
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
        $this->registerInstalled([
            ['SNAPIN_NODE', 'storageNodeSetting'],
            ['SNAPIN_GROUP', 'storageGroupSetting'],
            ['BOOT_ITEM_NEW_SETTINGS', 'bootItemSettings'],
            ['BOOT_TASK_NEW_SETTINGS', 'storageNodeSetting'],
            ['BOOT_TASK_NEW_SETTINGS', 'storageGroupSetting'],
            ['HOST_NEW_SETTINGS', 'storageNodeSetting'],
            ['HOST_NEW_SETTINGS', 'storageGroupSetting'],
            ['CHECK_NODE_MASTERS', 'alterMasters'],
            ['CHECK_NODE_MASTER', 'makeMaster'],
        ]);
    }
    /**
     * Sets up the storage node.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function storageNodeSetting($arguments)
    {
        $SubnetStorage = $this->_getSubnetStorage($arguments);
        if (!$SubnetStorage) {
            return;
        }
        $StorageGroup = $SubnetStorage->getStorageGroup();
        if (!$StorageGroup->isValid()) {
            return;
        }
        $Task = $arguments['Host']->get('task');
        $TaskType = $arguments['TaskType'] ?? null;
        if (($Task->isValid()
            && ($Task->isCapture() || $Task->isMulticast()))
            || ($TaskType instanceof \FOG\Items\TaskType
            && $TaskType->isValid()
            && ($TaskType->isCapture() || $TaskType->isMulticast()))
        ) {
            $arguments['StorageNode'] = $StorageGroup->getMasterStorageNode();
            return;
        }
        $StorageNode = $SubnetStorage->getStorageNode();
        if (!$StorageNode->isValid()) {
            return;
        }
        $StorageNode->{"location_url"} = sprintf(
            '%s://%s/%s',
            $SubnetStorage->get('protocol') ?: self::$httpproto,
            $StorageNode->get('ip'),
            $StorageNode->get('webroot')
        );
        $arguments['StorageNode'] = $StorageNode;
    }
    /**
     * Sets up the storage group.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function storageGroupSetting($arguments)
    {
        $SubnetStorage = $this->_getSubnetStorage($arguments);
        if (!$SubnetStorage) {
            return;
        }
        $StorageGroup = $SubnetStorage->getStorageGroup();
        if (!$StorageGroup->isValid()) {
            return;
        }
        $arguments['StorageGroup'] = $StorageGroup;
    }
    /**
     * Sets up the boot item information.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function bootItemSettings($arguments)
    {
        $SubnetStorage = $this->_getSubnetStorage($arguments);
        if (!$SubnetStorage || !$SubnetStorage->isTFTP()) {
            return;
        }
        $StorageNode = $SubnetStorage->getStorageNode();
        if (!$StorageNode->isValid()) {
            return;
        }
        $ip = $StorageNode->get('ip');
        $memtest = $arguments['memtest'];
        $memdisk = $arguments['memdisk'];
        $bzImage = $arguments['bzImage'];
        $initrd = $arguments['initrd'];
        $arguments['webserver'] = $ip;
        $arguments['memdisk'] = "http://{$ip}/fog/service/ipxe/{$memdisk}";
        $arguments['memtest'] = "http://{$ip}/fog/service/ipxe/{$memtest}";
        $arguments['bzImage'] = "http://{$ip}/fog/service/ipxe/{$bzImage}";
        $arguments['imagefile'] = "http://{$ip}/fog/service/ipxe/{$initrd}";
    }
    /**
     * Makes the local nodes used by subnets masters for multicasting.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function alterMasters($arguments)
    {
        if (!$arguments['FOGServiceClass'] instanceof \FOG\Service\MulticastManager) {
            return;
        }
        $storagenodeIDs = array_unique(
            array_filter(
                self::fastmerge(
                    $this->_storageNodeIDs(),
                    (array)$arguments['MasterIDs']
                )
            )
        );
        $StorageNodes = \FOG\Router\Route::getList(
            'storagenode',
            ['id' => $storagenodeIDs]
        );
        $arguments['StorageNodes'] = [];
        foreach ($StorageNodes as $StorageNode) {
            $StorageNode = \FOG\Router\Route::getItem(
                'storagenode',
                $StorageNode->id
            );
            if (!$StorageNode || !$StorageNode->online) {
                continue;
            }
            if (!self::_isLocalNode($StorageNode)) {
                continue;
            }
            if (!$StorageNode->isMaster) {
                $StorageNode->isMaster = 1;
            }
            $arguments['StorageNodes'][] = $StorageNode;
        }
    }
    /**
     * Lets a local node used by a subnet run multicast tasks.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function makeMaster($arguments)
    {
        if ('MulticastTask' !== $arguments['FOGServiceClass']) {
            return;
        }
        if (!in_array(
            (int)$arguments['StorageNode']->id,
            $this->_storageNodeIDs(),
            true
        )) {
            return;
        }
        if (!self::_isLocalNode($arguments['StorageNode'])) {
            return;
        }
        $arguments['StorageNode']->isMaster = 1;
    }
    /**
     * The storage nodes the subnets point at.
     *
     * @return int[]
     */
    private function _storageNodeIDs()
    {
        return array_values(
            array_filter(
                array_map(
                    'intval',
                    (array)\FOG\Router\Route::getIds(
                        $this->node,
                        [],
                        'storagenodeID'
                    )
                )
            )
        );
    }
    /**
     * Tells if a node is this server.
     *
     * @param object $StorageNode the node to test
     *
     * @return bool
     */
    private static function _isLocalNode($StorageNode)
    {
        self::getIPAddress();
        return in_array(
            self::resolveHostname($StorageNode->ip),
            (array)self::$ips
        );
    }
    /**
     * Gets the subnet storage item matching the host's address.
     *
     * @param mixed $arguments The arguments to evaluate.
     *
     * @return \FOG\Plugins\SubnetStorage\Items\SubnetStorage|false
     */
    private function _getSubnetStorage($arguments)
    {
        if (!isset($arguments['Host'])
            || !$arguments['Host'] instanceof \FOG\Items\Host
            || !$arguments['Host']->isValid()
        ) {
            return false;
        }
        $ip = $this->_getHostIPAddress($arguments['Host']);
        if (!$ip) {
            return false;
        }
        $match = false;
        $matchlength = -1;
        $items = \FOG\Router\Route::getList($this->node);
        foreach ($items as $item) {
            $length = \FOG\Plugins\SubnetStorage\Items\SubnetStorage::matchLength(
                $item->subnets,
                $ip
            );
            if ($length > $matchlength) {
                $match = (int)$item->id;
                $matchlength = $length;
            }
        }
        if (!$match) {
            return false;
        }
        $SubnetStorage = new \FOG\Plugins\SubnetStorage\Items\SubnetStorage($match);
        return $SubnetStorage->isValid() ? $SubnetStorage : false;
    }
    /**
     * Gets the address the host is currently reachable at.
     *
     * The hooks this plugin works with are mostly fired by requests the host
     * itself makes, so the address of the caller tells which subnet the host
     * boots from right now. The recorded address is a fallback for the cases
     * where the caller is not the host, as in the CLI services.
     *
     * @param \FOG\Items\Host $Host the host to evaluate
     *
     * @return string|false
     */
    private function _getHostIPAddress($Host)
    {
        $candidates = [];
        if (PHP_SAPI !== 'cli') {
            $candidates[] = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
        }
        $candidates[] = $Host->get('ip');
        $candidates[] = self::resolveHostname($Host->get('name'));
        foreach ($candidates as $candidate) {
            $ip = filter_var(
                (string)$candidate,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            );
            if (false !== $ip) {
                return $ip;
            }
        }
        return false;
    }
}
