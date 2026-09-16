<?php
/**
 * Plugin configuration file.
 *
 * PHP version 7.4+
 *
 * @category Config
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Plugin configuration file.
 *
 * @category Config
 * @package  FOGProject
 * @author   Roldao Junior <roldaogjr@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
$fog_plugin = array();
$fog_plugin['name'] = 'subnetstorage';
$fog_plugin['description'] = sprintf(
    '%s %s. %s.',
    _('Associates storage groups and storage nodes with IP subnets'),
    _('in order to serve each host from the storage of its own subnet'),
    _('It works like the Location plugin but the match is made by'
    . ' the address of the host instead of a manual association')
);
$fog_plugin['menuicon'] = 'fa fa-sitemap fa-fw';
$fog_plugin['menuicon_hover'] = null;
$fog_plugin['entrypoint'] = 'html/run.php';
