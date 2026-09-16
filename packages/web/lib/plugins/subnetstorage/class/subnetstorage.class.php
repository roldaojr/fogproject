<?php
/**
 * The subnet storage class.
 *
 * PHP version 7.4+
 *
 * @category Subnetstorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * The subnet storage class.
 *
 * @category Subnetstorage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class Subnetstorage extends FOGController
{
    /**
     * The subnetstorage table.
     *
     * @var string
     */
    protected $databaseTable = 'subnetstorage';
    /**
     * The subnetstorage fields and common names.
     *
     * @var array
     */
    protected $databaseFields = array(
        'id' => 'ssID',
        'name' => 'ssName',
        'subnets' => 'ssSubnets',
        'storagegroupID' => 'ssStorageGroupID',
        'storagenodeID' => 'ssStorageNodeID',
        'tftp' => 'ssTftpEnabled',
        'protocol' => 'ssStorageNodeProto',
    );
    /**
     * The required fields.
     *
     * @var array
     */
    protected $databaseFieldsRequired = array(
        'name',
        'subnets',
        'storagegroupID',
    );
    /**
     * Additional fields.
     *
     * @var array
     */
    protected $additionalFields = array(
        'storagegroup',
        'storagenode',
    );
    /**
     * Database -> Class field relationships.
     *
     * @var array
     */
    protected $databaseFieldClassRelationships = array(
        'StorageGroup' => array(
            'id',
            'storagegroupID',
            'storagegroup'
        ),
        'StorageNode' => array(
            'id',
            'storagenodeID',
            'storagenode'
        )
    );
    /**
     * Get the storage group of this subnet.
     *
     * @return object
     */
    public function getStorageGroup()
    {
        return $this->get('storagegroup');
    }
    /**
     * Get the storage node of this subnet.
     *
     * @return object
     */
    public function getStorageNode()
    {
        if ($this->get('storagenodeID')) {
            return $this->get('storagenode');
        }
        return $this->getStorageGroup()->getOptimalStorageNode();
    }
    /**
     * Tells if the inits and kernels come from this subnet's node.
     *
     * @return bool
     */
    public function isTFTP()
    {
        return (bool)$this->get('tftp');
    }
    /**
     * Tells if an address belongs to one of this item's subnets.
     *
     * @param string $ip the address to test
     *
     * @return bool
     */
    public function isInSubnet($ip)
    {
        return $this->getMatchLength($ip) > -1;
    }
    /**
     * Returns the prefix length of the narrowest subnet holding an address.
     *
     * The narrowest match wins when an address falls in the subnets of more
     * than one item, so that a small subnet can point somewhere else than
     * the wider subnet it sits in.
     *
     * @param string $ip the address to test
     *
     * @return int the prefix length, or -1 when no subnet holds the address
     */
    public function getMatchLength($ip)
    {
        $match = -1;
        $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if (false === $ip) {
            return $match;
        }
        $subnets = preg_split(
            '/[\s,]+/',
            (string)$this->get('subnets'),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        foreach ((array)$subnets as $subnet) {
            if (strpos($subnet, '/') === false) {
                continue;
            }
            list($net, $mask) = explode('/', $subnet, 2);
            $net = ip2long($net);
            if (false === $net) {
                continue;
            }
            $mask = (int)$mask;
            if ($mask < 0 || $mask > 32) {
                continue;
            }
            $bits = $mask === 0 ? 0 : ~((1 << (32 - $mask)) - 1);
            if ((ip2long($ip) & $bits) === ($net & $bits)
                && $mask > $match
            ) {
                $match = $mask;
            }
        }
        return $match;
    }
}
