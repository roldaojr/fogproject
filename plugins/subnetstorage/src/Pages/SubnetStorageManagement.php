<?php
/**
 * Subnet storage management page.
 *
 * PHP version 7.4+
 *
 * @category SubnetStorageManagement
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Pages;

/**
 * Subnet storage management page.
 *
 * @category SubnetStorageManagement
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetStorageManagement extends \FOG\Base\FOGPage
{
    /**
     * A comma separated list of IPv4 CIDR subnets.
     */
    const SUBNETS_MATCH = '#^((25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.){3}'
        . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])/([0-9]|[1-2][0-9]|3[0-2])'
        . '(\s*,\s*((25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.){3}'
        . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])/([0-9]|[1-2][0-9]|3[0-2]))*$#';
    /**
     * The node this page displays from.
     *
     * @var string
     */
    public $node = 'subnetstorage';
    /**
     * Initializes the subnet storage page.
     *
     * @param string $name the name to construct with
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = _('Subnet Storage Management');
        parent::__construct($this->name);
        $this->headerData = [
            _('Name'),
            _('Subnets'),
            _('Storage Group'),
            _('Storage Node'),
            _('Storage Node Protocol'),
            _('Kernels/Inits from node')
        ];
        $this->attributes = [
            [],
            [],
            [],
            [],
            [],
            []
        ];
    }
    /**
     * The fields of the create form and of the general tab.
     *
     * @param array $values the values to fill the fields with
     *
     * @return array
     */
    private function _fields(array $values)
    {
        $labelClass = 'col-sm-3 col-form-label';
        return [
            self::makeLabel(
                $labelClass,
                'subnetstorage',
                _('Subnet Storage Name')
            ) => self::makeInput(
                'form-control subnetstoragename-input',
                'subnetstorage',
                _('Subnet Storage Name'),
                'text',
                'subnetstorage',
                $values['name'],
                true
            ),
            self::makeLabel(
                $labelClass,
                'subnets',
                _('Subnets')
            ) => self::makeInput(
                'form-control subnetstoragesubnets-input',
                'subnets',
                _('192.168.1.0/24, 10.1.0.0/16'),
                'text',
                'subnets',
                $values['subnets'],
                true
            ),
            self::makeLabel(
                $labelClass,
                'storagegroup',
                _('Storage Group')
            ) => (new \FOG\Managers\StorageGroupManager())
                ->buildSelectBox($values['storagegroup']),
            self::makeLabel(
                $labelClass,
                'storagenode',
                _('Storage Node')
            ) => (new \FOG\Managers\StorageNodeManager())
                ->buildSelectBox($values['storagenode']),
            self::makeLabel(
                $labelClass,
                'storagenodeprotocol',
                _('Storage Node Protocol')
            ) => \FOG\Plugins\SubnetStorage\Managers\SubnetStorageManager::
                buildProtocolSelectBox($values['protocol']),
            self::makeLabel(
                $labelClass,
                'tftp',
                _('Use inits and kernels from this node')
            ) => self::makeInput(
                '',
                'tftp',
                '',
                'checkbox',
                'tftp',
                '',
                false,
                false,
                -1,
                -1,
                $values['tftp'] ? 'checked' : ''
            )
        ];
    }
    /**
     * The create form fields.
     *
     * @return array
     */
    protected function _addFields()
    {
        return $this->_fields(
            [
                'name' => filter_input(INPUT_POST, 'subnetstorage'),
                'subnets' => filter_input(INPUT_POST, 'subnets'),
                'storagegroup' => filter_input(INPUT_POST, 'storagegroup'),
                'storagenode' => filter_input(INPUT_POST, 'storagenode'),
                'protocol' => filter_input(INPUT_POST, 'storagenodeprotocol'),
                'tftp' => isset($_POST['tftp'])
            ]
        );
    }
    /**
     * Reads and validates the posted form.
     *
     * @param string $currentName the name the item has now, if any
     *
     * @throws \Exception when a value is not valid
     *
     * @return array the values to store
     */
    private function _posted($currentName = '')
    {
        $name = trim((string)filter_input(INPUT_POST, 'subnetstorage'));
        $subnets = trim((string)filter_input(INPUT_POST, 'subnets'));
        $storagegroup = trim((string)filter_input(INPUT_POST, 'storagegroup'));
        $storagenode = trim((string)filter_input(INPUT_POST, 'storagenode'));
        $protocol = trim(
            (string)filter_input(INPUT_POST, 'storagenodeprotocol')
        );
        if (!$name) {
            throw new \Exception(
                _('Please enter a name for this subnet storage.')
            );
        }
        if ($name != $currentName
            && (new \FOG\Plugins\SubnetStorage\Managers\SubnetStorageManager())
                ->exists($name)
        ) {
            throw new \Exception(
                _('A subnet storage already exists with this name!')
            );
        }
        if (!preg_match(self::SUBNETS_MATCH, $subnets)) {
            throw new \Exception(
                _('Please enter a valid CIDR subnet.')
                . ' '
                . _('Can be a comma separated list.')
            );
        }
        $subnets = str_replace(',', ', ', preg_replace('/\s+/', '', $subnets));
        if ($storagenode) {
            $StorageNode = new \FOG\Items\StorageNode($storagenode);
            if (!$StorageNode->isValid()) {
                throw new \Exception(_('The selected storage node is invalid.'));
            }
            $storagegroup = $StorageNode->get('storagegroupID');
        }
        if (!$storagegroup) {
            throw new \Exception(
                _('Please select the storage group of this subnet.')
            );
        }
        if (!in_array($protocol, ['', 'http', 'https'], true)) {
            throw new \Exception(_('Invalid storage node protocol.'));
        }
        return [
            'name' => $name,
            'subnets' => $subnets,
            'storagegroupID' => $storagegroup,
            'storagenodeID' => $storagenode ?: null,
            'protocol' => $protocol ?: null,
            'tftp' => (int)isset($_POST['tftp'])
        ];
    }
    /**
     * Sets the posted values on an item.
     *
     * @param \FOG\Plugins\SubnetStorage\Items\SubnetStorage $SubnetStorage the item
     * @param array                                          $values        the values
     *
     * @return \FOG\Plugins\SubnetStorage\Items\SubnetStorage
     */
    private static function _apply($SubnetStorage, array $values)
    {
        foreach ($values as $key => $value) {
            $SubnetStorage->set($key, $value);
        }
        return $SubnetStorage;
    }
    /**
     * Create new subnet storage.
     *
     * @return void
     */
    public function add()
    {
        $this->renderAddForm(
            'subnetstorage',
            _('Create New Subnet Storage'),
            'SUBNETSTORAGE_ADD_FIELDS',
            'SubnetStorage'
        );
    }
    /**
     * Create new subnet storage in a modal.
     *
     * @return void
     */
    public function addModal()
    {
        $this->renderAddModalForm(
            'subnetstorage',
            'SUBNETSTORAGE_ADD_FIELDS',
            'SubnetStorage'
        );
    }
    /**
     * Actually create the subnet storage.
     *
     * @return void
     */
    public function addPost()
    {
        $this->handleAddPost(
            'SubnetStorage',
            'SUBNETSTORAGE_ADD',
            _('Subnet storage added!'),
            _('Subnet Storage Create Success'),
            _('Subnet Storage Create Fail'),
            function (&$serverFault) {
                $SubnetStorage = self::_apply(
                    new \FOG\Plugins\SubnetStorage\Items\SubnetStorage(),
                    $this->_posted()
                );
                if (!$SubnetStorage->save()) {
                    $serverFault = true;
                    throw new \Exception(_('Add subnet storage failed!'));
                }
                return $SubnetStorage;
            }
        );
    }
    /**
     * The general tab.
     *
     * @return void
     */
    public function subnetstorageGeneral()
    {
        $fields = $this->_fields(
            [
                'name' => filter_input(INPUT_POST, 'subnetstorage')
                    ?: $this->obj->get('name'),
                'subnets' => filter_input(INPUT_POST, 'subnets')
                    ?: $this->obj->get('subnets'),
                'storagegroup' => filter_input(INPUT_POST, 'storagegroup')
                    ?: $this->obj->get('storagegroupID'),
                'storagenode' => filter_input(INPUT_POST, 'storagenode')
                    ?: $this->obj->get('storagenodeID'),
                'protocol' => filter_input(INPUT_POST, 'storagenodeprotocol')
                    ?: $this->obj->get('protocol'),
                'tftp' => isset($_POST['tftp']) || $this->obj->get('tftp')
            ]
        );

        $buttons = self::makeButton(
            'general-send',
            _('Update'),
            'btn btn-primary float-end'
        );
        $buttons .= self::makeButton(
            'general-delete',
            _('Delete'),
            'btn btn-danger float-start'
        );

        self::$HookManager->processEvent(
            'SUBNETSTORAGE_GENERAL_FIELDS',
            [
                'fields' => &$fields,
                'buttons' => &$buttons,
                'SubnetStorage' => $this->obj
            ]
        );
        $rendered = self::formFields($fields);
        unset($fields);

        echo self::makeFormTag(
            '',
            'subnetstorage-general-form',
            self::makeTabUpdateURL(
                'subnetstorage-general',
                $this->obj->get('id')
            ),
            'post',
            'application/x-www-form-urlencoded',
            true
        );
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="card-footer">';
        echo $buttons;
        echo $this->deleteModal();
        echo '</div>';
        echo '</div>';
        echo '</form>';
    }
    /**
     * Applies the general tab.
     *
     * @return void
     */
    public function subnetstorageGeneralPost()
    {
        self::_apply(
            $this->obj,
            $this->_posted((string)$this->obj->get('name'))
        );
    }
    /**
     * Edit the subnet storage.
     *
     * @return void
     */
    public function edit()
    {
        $tabData = [];
        $tabData[] = [
            'name' => _('General'),
            'id' => 'subnetstorage-general',
            'generator' => function () {
                $this->subnetstorageGeneral();
            }
        ];
        $this->renderEditTabs($tabData, $this->obj);
    }
    /**
     * Actually update the subnet storage.
     *
     * @return void
     */
    public function editPost()
    {
        $this->handleEditPost(
            'SubnetStorage',
            'SUBNETSTORAGE_EDIT',
            _('Subnet storage updated!'),
            _('Subnet Storage Update Success'),
            _('Subnet Storage Update Fail'),
            function (&$serverFault) {
                global $tab;
                switch ($tab) {
                    case 'subnetstorage-general':
                        $this->subnetstorageGeneralPost();
                        break;
                }
                if (!$this->obj->save()) {
                    $serverFault = true;
                    throw new \Exception(_('Subnet storage update failed!'));
                }
            }
        );
    }
}
