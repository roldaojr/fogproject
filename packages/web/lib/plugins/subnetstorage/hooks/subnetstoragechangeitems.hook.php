<?php
/**
 * Changes the storage items based on the subnet of the host.
 *
 * PHP version 7.4+
 *
 * @category SubnetstorageChangeItems
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Changes the storage items based on the subnet of the host.
 *
 * @category SubnetstorageChangeItems
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetstorageChangeItems extends Hook
{
    /**
     * The name of this hook.
     *
     * @var string
     */
    public $name = 'SubnetstorageChangeItems';
    /**
     * The description of this hook.
     *
     * @var string
     */
    public $description = 'Set storage group and node from the host subnet';
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
                'SNAPIN_NODE',
                array(
                    $this,
                    'storageNodeSetting'
                )
            )
            ->register(
                'SNAPIN_GROUP',
                array(
                    $this,
                    'storageGroupSetting'
                )
            )
            ->register(
                'BOOT_ITEM_NEW_SETTINGS',
                array(
                    $this,
                    'bootItemSettings'
                )
            )
            ->register(
                'BOOT_TASK_NEW_SETTINGS',
                array(
                    $this,
                    'storageNodeSetting'
                )
            )
            ->register(
                'BOOT_TASK_NEW_SETTINGS',
                array(
                    $this,
                    'storageGroupSetting'
                )
            )
            ->register(
                'HOST_NEW_SETTINGS',
                array(
                    $this,
                    'storageNodeSetting'
                )
            )
            ->register(
                'HOST_NEW_SETTINGS',
                array(
                    $this,
                    'storageGroupSetting'
                )
            )
            ->register(
                'CHECK_NODE_MASTERS',
                array(
                    $this,
                    'alterMasters'
                )
            );
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
        $Subnetstorage = $this->_getSubnetstorage($arguments);
        if (!$Subnetstorage) {
            return;
        }
        $StorageGroup = $Subnetstorage->getStorageGroup();
        if (!$StorageGroup->isValid()) {
            return;
        }
        $Task = $arguments['Host']->get('task');
        $TaskType = isset($arguments['TaskType']) ? $arguments['TaskType'] : null;
        $method = false;
        if ($Task->isValid()
            && ($Task->isCapture() || $Task->isMulticast())
        ) {
            $method = 'getMasterStorageNode';
        } elseif ($TaskType instanceof TaskType
            && $TaskType->isValid()
            && ($TaskType->isCapture() || $TaskType->isMulticast())
        ) {
            $method = 'getMasterStorageNode';
        }
        if ($method) {
            $arguments['StorageNode'] = $StorageGroup->{$method}();
            return;
        }
        $arguments['StorageNode'] = $Subnetstorage->getStorageNode();
        if (!$arguments['StorageNode']->isValid()) {
            return;
        }
        $arguments['StorageNode']->{"location_url"} = sprintf(
            '%s://%s/%s',
            $this->_getProtocol($Subnetstorage),
            $arguments['StorageNode']->get('ip'),
            $arguments['StorageNode']->get('webroot')
        );
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
        $Subnetstorage = $this->_getSubnetstorage($arguments);
        if (!$Subnetstorage) {
            return;
        }
        $StorageGroup = $Subnetstorage->getStorageGroup();
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
        $Subnetstorage = $this->_getSubnetstorage($arguments);
        if (!$Subnetstorage || !$Subnetstorage->isTFTP()) {
            return;
        }
        $StorageNode = $Subnetstorage->getStorageNode();
        if (!$StorageNode->isValid()) {
            return;
        }
        $ip = $StorageNode->get('ip');
        $memtest = $arguments['memtest'];
        $memdisk = $arguments['memdisk'];
        $bzImage = $arguments['bzImage'];
        $initrd = $arguments['initrd'];
        $arguments['webserver'] = $ip;
        $arguments['memdisk'] = "http://{$ip}/fog/service/ipxe/$memdisk";
        $arguments['memtest'] = "http://{$ip}/fog/service/ipxe/$memtest";
        $arguments['bzImage'] = "http://{$ip}/fog/service/ipxe/$bzImage";
        $arguments['imagefile'] = "http://{$ip}/fog/service/ipxe/$initrd";
    }
    /**
     * Makes the nodes used by subnets masters for multicasting.
     *
     * @param mixed $arguments The items to change.
     *
     * @return void
     */
    public function alterMasters($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        if (!$arguments['FOGServiceClass'] instanceof MulticastManager) {
            return;
        }
        $storagenodeIDs = self::getSubObjectIDs(
            'Subnetstorage',
            '',
            'storagenodeID'
        );
        $storagenodeIDs = self::fastmerge(
            (array)$storagenodeIDs,
            (array)$arguments['MasterIDs']
        );
        $storagenodeIDs = array_filter($storagenodeIDs);
        $storagenodeIDs = array_unique($storagenodeIDs);
        $arguments['StorageNodes'] = self::getClass('StorageNodeManager')
            ->find(
                array(
                    'id' => $storagenodeIDs
                )
            );
        foreach ($arguments['StorageNodes'] as &$StorageNode) {
            if (!$StorageNode->isValid()) {
                continue;
            }
            if (!$StorageNode->get('isMaster')) {
                $StorageNode->set('isMaster', 1);
            }
            unset($StorageNode);
        }
    }
    /**
     * Returns the protocol to reach the node of a subnet.
     *
     * @param object $Subnetstorage the subnet storage item
     *
     * @return string
     */
    private function _getProtocol($Subnetstorage)
    {
        $protocol = $Subnetstorage->get('protocol');
        if (!empty($protocol)) {
            return $protocol;
        }
        $https = filter_input(INPUT_SERVER, 'HTTPS');
        $forwarded = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO');
        if ($https === 'on' || $https === '1' || $forwarded === 'https') {
            return 'https';
        }
        return 'http';
    }
    /**
     * Gets the subnet storage item matching the host's address.
     *
     * @param mixed $arguments The arguments to evaluate.
     *
     * @return object|false
     */
    private function _getSubnetstorage($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return false;
        }
        if (!isset($arguments['Host'])
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
        foreach ((array)self::getClass('SubnetstorageManager')
            ->find() as &$Subnetstorage
        ) {
            $length = $Subnetstorage->getMatchLength($ip);
            if ($length > $matchlength) {
                $match = $Subnetstorage;
                $matchlength = $length;
            }
            unset($Subnetstorage);
        }
        return $match;
    }
    /**
     * Gets the address the host is currently reachable at.
     *
     * The hooks this plugin works with are only fired by requests the host
     * itself makes, so the address of the caller tells which subnet the host
     * boots from right now. The recorded address is only a fallback for the
     * cases where the caller cannot be determined, as in the CLI services.
     *
     * @param object $Host the host to evaluate
     *
     * @return string|false
     */
    private function _getHostIPAddress($Host)
    {
        $candidates = array(
            filter_input(INPUT_SERVER, 'REMOTE_ADDR'),
            $Host->get('ip'),
            self::resolveHostname($Host->get('name'))
        );
        foreach ((array)$candidates as $candidate) {
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
