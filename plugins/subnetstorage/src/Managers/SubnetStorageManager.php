<?php
/**
 * Manager class for subnet storage.
 *
 * PHP version 7.4+
 *
 * @category SubnetStorageManager
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
namespace FOG\Plugins\SubnetStorage\Managers;

/**
 * Manager class for subnet storage.
 *
 * @category SubnetStorageManager
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class SubnetStorageManager extends \FOG\Base\FOGManagerController
{
    /**
     * The base table name.
     *
     * @var string
     */
    public $tablename = 'subnetstorage';
    /**
     * The table as a fresh install creates it.
     *
     * ssStorageNodeID is NULL when the group picks the node, and
     * ssStorageNodeProto is NULL when the protocol follows the request.
     *
     * @return string
     */
    public function createSql()
    {
        return $this->createTableSql(
            $this->tablename,
            true,
            [
                'ssID',
                'ssName',
                'ssSubnets',
                'ssStorageGroupID',
                'ssStorageNodeID',
                'ssTftpEnabled',
                'ssStorageNodeProto'
            ],
            [
                'INTEGER',
                'VARCHAR(255)',
                'TEXT',
                'INTEGER',
                'INTEGER',
                'TINYINT(1)',
                'VARCHAR(5)'
            ],
            [
                false,
                false,
                false,
                false,
                true,
                false,
                true
            ],
            [
                false,
                false,
                false,
                false,
                false,
                '0',
                false
            ],
            [
                'ssID',
                'ssName'
            ],
            'InnoDB',
            'utf8',
            'ssID',
            'ssID'
        );
    }
    /**
     * The plugin's ordered, append-only schema migration list.
     *
     * Steps 1 to 3 bring a table created by the 1.5 version of this plugin
     * to the shape createSql() builds; on a fresh install they are no-ops.
     *
     * @return array
     */
    public function schema()
    {
        return [
            // 0
            $this->createSql(),
            // 1 - ssTftpEnabled was ENUM('0','1') on 1.5. The helper converts
            // by label; a plain MODIFY would convert by index.
            function () {
                return \FOG\Items\Schema::enumToTinyint(
                    [$this->tablename => ['ssTftpEnabled']]
                );
            },
            // 2 - "no specific node" becomes NULL instead of 0.
            function () {
                $sqls = [
                    sprintf(
                        'ALTER TABLE `%s` MODIFY COLUMN `ssStorageNodeID`'
                        . ' INTEGER NULL DEFAULT NULL',
                        $this->tablename
                    ),
                    sprintf(
                        'UPDATE `%s` SET `ssStorageNodeID` = NULL'
                        . ' WHERE `ssStorageNodeID` = 0',
                        $this->tablename
                    )
                ];
                foreach ($sqls as $sql) {
                    if (false !== self::$DB->query($sql)->error) {
                        return self::$DB->error;
                    }
                }
                return true;
            },
            // 3 - ssStorageNodeProto was a NOT NULL ENUM on 1.5, which strict
            // mode refuses to store "follow the request" ('') in.
            function () {
                $sqls = [
                    sprintf(
                        'ALTER TABLE `%s` MODIFY COLUMN `ssStorageNodeProto`'
                        . ' VARCHAR(5) NULL DEFAULT NULL',
                        $this->tablename
                    ),
                    sprintf(
                        "UPDATE `%s` SET `ssStorageNodeProto` = NULL"
                        . " WHERE `ssStorageNodeProto` = ''",
                        $this->tablename
                    )
                ];
                foreach ($sqls as $sql) {
                    if (false !== self::$DB->query($sql)->error) {
                        return self::$DB->error;
                    }
                }
                return true;
            },
        ];
    }
    /**
     * Install the database non-destructively.
     *
     * @return bool
     */
    public function install()
    {
        $res = \FOG\Items\Schema::applyUpdates($this->schema(), 0);
        return $res['error'] === null;
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
        $protocols = [
            'http' => 'HTTP',
            'https' => 'HTTPS'
        ];
        ob_start();
        foreach ($protocols as $short => $long) {
            printf(
                '<option value="%s"%s>%s</option>',
                \Initiator::e($short),
                ($preselection === $short ? ' selected' : ''),
                \Initiator::e($long)
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
