<?php
/**
 * Manager class for subnet storage.
 *
 * PHP version 7.4+
 *
 * @category SubnetstorageManager
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Manager class for subnet storage.
 *
 * @category SubnetstorageManager
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetstorageManager extends FOGManagerController
{
    /**
     * The base table name.
     *
     * @var string
     */
    public $tablename = 'subnetstorage';
    /**
     * Perform the database installation.
     *
     * @return bool
     */
    public function install()
    {
        $this->uninstall();
        $sql = $this->createTableSql(
            $this->tablename,
            true,
            array(
                'ssID',
                'ssName',
                'ssSubnets',
                'ssStorageGroupID',
                'ssStorageNodeID',
                'ssTftpEnabled',
                'ssStorageNodeProto'
            ),
            array(
                'INTEGER',
                'VARCHAR(255)',
                'TEXT',
                'INTEGER',
                'INTEGER',
                "ENUM('0', '1')",
                "ENUM('https', 'http')"
            ),
            array(
                false,
                false,
                false,
                false,
                false,
                false,
                false
            ),
            array(
                false,
                false,
                false,
                false,
                false,
                false,
                false
            ),
            array(
                'ssID',
                'ssName'
            ),
            'InnoDB',
            'utf8',
            'ssID',
            'ssID'
        );
        return self::$DB->query($sql);
    }
    /**
     * Builds the protocol selection box.
     *
     * @param string $preselection the protocol already selected
     *
     * @return string
     */
    public static function buildProtocolSelectBox($preselection)
    {
        $protocols = array(
            'https' => 'HTTPS',
            'http' => 'HTTP'
        );
        ob_start();
        foreach ((array)$protocols as $short => $long) {
            printf(
                '<option value="%s"%s>%s</option>',
                Initiator::e($short),
                (
                    $preselection === $short ?
                    ' selected' :
                    ''
                ),
                Initiator::e($long)
            );
        }
        return '<select class="form-control" name="storagenodeprotocol" '
            . 'id="storagenodeprotocol">'
            . '<option value="">- '
            . self::$foglang['PleaseSelect']
            . ' -</option>'
            . ob_get_clean()
            . '</select>';
    }
}
