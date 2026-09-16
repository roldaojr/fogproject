<?php
/**
 * The subnet storage page.
 *
 * PHP version 7.4+
 *
 * @category SubnetstorageManagementPage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * The subnet storage page.
 *
 * @category SubnetstorageManagementPage
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetstorageManagementPage extends FOGPage
{
    /**
     * The node this page displays with.
     *
     * @var string
     */
    public $node = 'subnetstorage';
    /**
     * The subnets format expected from the user.
     *
     * @var string
     */
    const SUBNETS_MATCH = '#^([0-9]{1,3}\.){3}[0-9]{1,3}/([0-9]|[1-2][0-9]|3[0-2])'
        . '(\s*,\s*([0-9]{1,3}\.){3}[0-9]{1,3}/([0-9]|[1-2][0-9]|3[0-2]))*$#';
    /**
     * Initializes the subnet storage page.
     *
     * @param string $name The name to pass with.
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = _('Subnet Storage Management');
        self::$foglang['ExportSubnetstorage'] = _('Export Subnet Storages');
        self::$foglang['ImportSubnetstorage'] = _('Import Subnet Storages');
        self::$HookManager->processEvent(
            'PAGES_WITH_OBJECTS',
            array('PagesWithObjects' => &$this->PagesWithObjects)
        );
        parent::__construct($this->name);
        global $id;
        if ($id) {
            $this->subMenu = array(
                "$this->linkformat#subnetstorage-general" => self::$foglang['General'],
                $this->delformat => self::$foglang['Delete'],
            );
            $this->notes = array(
                _('Name') => $this->obj->get('name'),
                _('Subnets') => $this->obj->get('subnets'),
                sprintf(
                    '%s %s',
                    self::$foglang['Storage'],
                    self::$foglang['Group']
                ) => $this->obj->getStorageGroup()->get('name'),
            );
            if ($this->obj->getStorageNode()->isValid()) {
                $this->notes[
                    sprintf(
                        '%s %s',
                        self::$foglang['Storage'],
                        self::$foglang['Node']
                    )
                ] = $this->obj->getStorageNode()->get('name');
            }
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class='
            . '"toggle-checkboxAction"/>',
            _('Name'),
            _('Subnets'),
            _('Storage Group'),
            _('Storage Node'),
            _('Storage Node Protocol'),
            _('Kernels/Inits from node'),
        );
        $this->templates = array(
            '<input type="checkbox" name="subnetstorage[]" value='
            . '"${id}" class="toggle-action" checked/>',
            '<a href="?node=subnetstorage&sub=edit&id=${id}" title="'
            . _('Edit')
            . '">(${id}) - ${name}</a>',
            '${subnets}',
            '${storageGroup}',
            '${storageNode}',
            '${storageNodeProtocol}',
            '${tftp}',
        );
        $this->attributes = array(
            array(
                'class' => 'parser-false filter-false',
                'width' => 16
            ),
            array(),
            array(),
            array(),
            array(),
            array(),
            array()
        );
        /**
         * Lambda function to return data either by list or search.
         *
         * @param object $Subnetstorage the object to use
         *
         * @return void
         */
        self::$returnData = function (&$Subnetstorage) {
            $sn = empty($Subnetstorage->storagenode->name) ?
                '*' :
                $Subnetstorage->storagenode->name;
            $this->data[] = array(
                'id' => Initiator::e($Subnetstorage->id),
                'name' => Initiator::e($Subnetstorage->name),
                'subnets' => Initiator::e($Subnetstorage->subnets),
                'storageGroup' => Initiator::e($Subnetstorage->storagegroup->name),
                'storageNode' => Initiator::e($sn),
                'storageNodeProtocol' => Initiator::e($Subnetstorage->protocol),
                'tftp' => $Subnetstorage->tftp ? _('Yes') : _('No'),
            );
            unset($Subnetstorage);
        };
    }
    /**
     * Present page to create a new subnet storage entry.
     *
     * @return void
     */
    public function add()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
        $this->title = _('New Subnet Storage');
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $name = filter_input(INPUT_POST, 'name');
        $subnets = filter_input(INPUT_POST, 'subnets');
        $storagegroup = filter_input(INPUT_POST, 'storagegroup');
        $storagenode = filter_input(INPUT_POST, 'storagenode');
        $protocol = filter_input(INPUT_POST, 'storagenodeprotocol');
        $fields = $this->_commonFields(
            $name,
            $subnets,
            $storagegroup,
            $storagenode,
            $protocol,
            isset($_POST['tftp']),
            '<label for="add">'
            . _('Create New Subnet Storage')
            . '</label>',
            '<button type="submit" name="add" id="add" '
            . 'class="btn btn-info btn-block">'
            . _('Add')
            . '</button>'
        );
        array_walk($fields, $this->fieldsToData);
        self::$HookManager
            ->processEvent(
                'SUBNETSTORAGE_ADD',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<div class="col-xs-9">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '">';
        $this->indexDivDisplay();
        echo '</form>';
        echo '</div>';
        unset(
            $fields,
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
    }
    /**
     * Actually create the subnet storage entry.
     *
     * @return void
     */
    public function addPost()
    {
        self::$HookManager->processEvent('SUBNETSTORAGE_ADD_POST');
        $name = filter_input(INPUT_POST, 'name');
        $subnets = filter_input(INPUT_POST, 'subnets');
        $storagegroup = filter_input(INPUT_POST, 'storagegroup');
        $storagenode = filter_input(INPUT_POST, 'storagenode');
        $protocol = filter_input(INPUT_POST, 'storagenodeprotocol');
        $tftp = isset($_POST['tftp']);
        try {
            if (!$name) {
                throw new Exception(
                    _('Please enter a name for this subnet storage.')
                );
            }
            if (self::getClass('SubnetstorageManager')->exists($name)) {
                throw new Exception(
                    _('A subnet storage already exists with this name!')
                );
            }
            $subnets = $this->_validateSubnets($subnets);
            if (!$storagegroup) {
                throw new Exception(
                    _('Please select the storage group of this subnet.')
                );
            }
            $Subnetstorage = self::getClass('Subnetstorage')
                ->set('name', $name)
                ->set('subnets', $subnets)
                ->set('storagegroupID', $this->_groupOf($storagegroup, $storagenode))
                ->set('storagenodeID', $storagenode)
                ->set('protocol', $protocol)
                ->set('tftp', (int)$tftp);
            if (!$Subnetstorage->save()) {
                throw new Exception(_('Add subnet storage failed!'));
            }
            $hook = 'SUBNETSTORAGE_ADD_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Subnet storage added!'),
                    'title' => _('Subnet Storage Create Success')
                )
            );
        } catch (Exception $e) {
            $hook = 'SUBNETSTORAGE_ADD_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Subnet Storage Create Fail')
                )
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                array('Subnetstorage' => &$Subnetstorage)
            );
        unset($Subnetstorage);
        echo $msg;
        exit;
    }
    /**
     * Present the subnet storage to edit.
     *
     * @return void
     */
    public function edit()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
        $this->title = _('Subnet Storage General');
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $name = filter_input(
            INPUT_POST,
            'name'
        ) ?: $this->obj->get('name');
        $subnets = filter_input(
            INPUT_POST,
            'subnets'
        ) ?: $this->obj->get('subnets');
        $storagegroup = filter_input(
            INPUT_POST,
            'storagegroup'
        ) ?: $this->obj->get('storagegroupID');
        $storagenode = filter_input(
            INPUT_POST,
            'storagenode'
        ) ?: $this->obj->get('storagenodeID');
        $protocol = filter_input(
            INPUT_POST,
            'storagenodeprotocol'
        ) ?: $this->obj->get('protocol');
        $tftp = isset($_POST['tftp']) ? true : (bool)$this->obj->get('tftp');
        $fields = $this->_commonFields(
            $name,
            $subnets,
            $storagegroup,
            $storagenode,
            $protocol,
            $tftp,
            '<label for="update">'
            . _('Make Changes?')
            . '</label>',
            '<button type="submit" name="update" id="update" '
            . 'class="btn btn-info btn-block">'
            . _('Update')
            . '</button>'
        );
        array_walk($fields, $this->fieldsToData);
        self::$HookManager
            ->processEvent(
                'SUBNETSTORAGE_EDIT',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<div class="col-xs-9 tab-content">';
        echo '<!-- General -->';
        echo '<div class="tab-pane fade in active" id="subnetstorage-general">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo $this->title;
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=subnetstorage-general">';
        $this->render(12);
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $fields,
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
    }
    /**
     * Actually update the subnet storage entry.
     *
     * @return void
     */
    public function editPost()
    {
        self::$HookManager
            ->processEvent(
                'SUBNETSTORAGE_EDIT_POST',
                array('Subnetstorage' => &$this->obj)
            );
        $name = filter_input(INPUT_POST, 'name');
        $subnets = filter_input(INPUT_POST, 'subnets');
        $storagegroup = filter_input(INPUT_POST, 'storagegroup');
        $storagenode = filter_input(INPUT_POST, 'storagenode');
        $protocol = filter_input(INPUT_POST, 'storagenodeprotocol');
        $tftp = isset($_POST['tftp']);
        try {
            if ($name != $this->obj->get('name')
                && $this->obj->getManager()->exists($name)
            ) {
                throw new Exception(
                    _('A subnet storage already exists with this name!')
                );
            }
            $subnets = $this->_validateSubnets($subnets);
            if (!$storagegroup) {
                throw new Exception(
                    _('A storage group is required for a subnet storage.')
                );
            }
            $this->obj
                ->set('name', $name)
                ->set('subnets', $subnets)
                ->set('storagegroupID', $this->_groupOf($storagegroup, $storagenode))
                ->set('storagenodeID', $storagenode)
                ->set('protocol', $protocol)
                ->set('tftp', (int)$tftp);
            if (!$this->obj->save()) {
                throw new Exception(_('Subnet storage update failed!'));
            }
            $hook = 'SUBNETSTORAGE_UPDATE_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Subnet storage updated!'),
                    'title' => _('Subnet Storage Update Success')
                )
            );
        } catch (Exception $e) {
            $hook = 'SUBNETSTORAGE_UPDATE_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Subnet Storage Update Fail')
                )
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                array('Subnetstorage' => &$this->obj)
            );
        echo $msg;
        exit;
    }
    /**
     * The fields shared by the add and edit forms.
     *
     * @param string $name         the name of the entry
     * @param string $subnets      the subnets of the entry
     * @param int    $storagegroup the selected storage group
     * @param int    $storagenode  the selected storage node
     * @param string $protocol     the selected protocol
     * @param bool   $tftp         whether inits come from the node
     * @param string $submitlabel  the label of the submit button
     * @param string $submit       the submit button
     *
     * @return array
     */
    private function _commonFields(
        $name,
        $subnets,
        $storagegroup,
        $storagenode,
        $protocol,
        $tftp,
        $submitlabel,
        $submit
    ) {
        $sgbuild = self::getClass('StorageGroupManager')->buildSelectBox(
            $storagegroup
        );
        $snbuild = self::getClass('StorageNodeManager')->buildSelectBox(
            $storagenode
        );
        $snprotocol = self::getClass('SubnetstorageManager')
            ->buildProtocolSelectBox($protocol);
        return array(
            '<label for="name">'
            . _('Name')
            . '</label>' => '<div class="input-group">'
            . '<input type="text" class="'
            . 'form-control subnetstorage-input" name='
            . '"name" value="'
            . Initiator::e($name)
            . '" autocomplete="off" id="name" required/>'
            . '</div>',
            '<label for="subnets">'
            . _('Subnets')
            . '</label>' => '<div class="input-group">'
            . '<input type="text" class="'
            . 'form-control subnetstorage-input" name='
            . '"subnets" value="'
            . Initiator::e($subnets)
            . '" autocomplete="off" id="subnets" required'
            . ' placeholder="192.168.1.0/24, 10.1.0.0/16"/>'
            . '</div>',
            '<label for="storagegroup">'
            . _('Storage Group')
            . '</label>' => $sgbuild,
            '<label for="storagenode">'
            . _('Storage Node')
            . '</label>' => $snbuild,
            '<label for="storagenodeprotocol">'
            . _('Storage Node Protocol')
            . '</label>' => $snprotocol,
            '<label for="isen">'
            . _('Use inits and kernels from this node')
            . '</label>' => '<input type="checkbox" name="tftp" '
            . 'class="tftpenabled" id="isen"'
            . ($tftp ? ' checked' : '')
            . '/>',
            $submitlabel => $submit
        );
    }
    /**
     * Returns the group of the selected node, or the selected group.
     *
     * @param int $storagegroup the selected storage group
     * @param int $storagenode  the selected storage node
     *
     * @return int
     */
    private function _groupOf($storagegroup, $storagenode)
    {
        $StorageNode = new StorageNode($storagenode);
        if ($StorageNode->isValid()) {
            return $StorageNode->getStorageGroup()->get('id');
        }
        return $storagegroup;
    }
    /**
     * Normalizes the subnets list and fails on invalid entries.
     *
     * @param string $subnets the list to validate
     *
     * @return string
     * @throws Exception
     */
    private function _validateSubnets($subnets)
    {
        $subnets = trim((string)$subnets);
        if (!preg_match(self::SUBNETS_MATCH, $subnets)) {
            throw new Exception(
                _('Please enter a valid CIDR subnet.')
                . ' '
                . _('Can be a comma separated list.')
            );
        }
        $subnets = preg_replace('/\s+/', '', $subnets);
        return str_replace(',', ', ', $subnets);
    }
}
