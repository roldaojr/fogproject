<?php
/**
 * Loads the plugin through FOG's own autoloader and checks the subnet match.
 *
 * Usage: php plugins/subnetstorage/tests/plugin.test.php [path/to/fogproject]
 * Exit status 0 = pass.
 */
$plugin = dirname(__DIR__);
$fog = $argv[1] ?? dirname($plugin, 2);
$init = $fog . '/packages/web/commons/init.php';
if (!is_readable($init)) {
    fwrite(STDERR, "FAIL: cannot read $init\n");
    exit(1);
}

$tmp = sys_get_temp_dir() . '/fog-subnetstorage-test-' . getmypid();
@mkdir($tmp . '/cache', 0700, true);
@mkdir($tmp . '/log', 0700, true);
@mkdir($tmp . '/plugins', 0700, true);
symlink($plugin, $tmp . '/plugins/subnetstorage');
register_shutdown_function(
    function () use ($tmp) {
        @unlink($tmp . '/plugins/subnetstorage');
        exec('rm -rf ' . escapeshellarg($tmp));
    }
);
define('FOG_CACHE_DIR', $tmp . '/cache');
define('FOG_LOG_DIR', $tmp . '/log');
define('FOG_PLUGIN_DIR', $tmp . '/plugins');

require_once $init;
new Initiator();

$fails = [];
$check = function ($what, $ok) use (&$fails) {
    if (!$ok) {
        $fails[] = $what;
    }
};

$base = 'FOG\\Plugins\\SubnetStorage\\';
$classes = [
    'Items\\SubnetStorage' => 'FOG\\Base\\FOGController',
    'Managers\\SubnetStorageManager' => 'FOG\\Base\\FOGManagerController',
    'Pages\\SubnetStorageManagement' => 'FOG\\Base\\FOGPage',
    'Hooks\\AddSubnetStorageMenuItem' => 'FOG\\Base\\Hook',
    'Hooks\\AddSubnetStorageJS' => 'FOG\\Base\\Hook',
    'Hooks\\AddSubnetStorageAPI' => 'FOG\\Base\\Hook',
    'Hooks\\RemoveSubnetStorageStorage' => 'FOG\\Base\\Hook',
    'Hooks\\SubnetStorageChangeItems' => 'FOG\\Base\\Hook',
];
foreach ($classes as $class => $parent) {
    $check("$class loads", class_exists($base . $class));
    $check("$class extends $parent", is_subclass_of($base . $class, $parent));
}

$check(
    'short name SubnetStorage resolves through qualify()',
    \FOG\Base\FOGBase::qualify('SubnetStorage') === $base . 'Items\\SubnetStorage'
);
$check(
    'short name SubnetStorageManager resolves through qualify()',
    \FOG\Base\FOGBase::qualify('SubnetStorageManager')
        === $base . 'Managers\\SubnetStorageManager'
);

$fog_plugin = [];
include $plugin . '/config/plugin.config.php';
$check('manifest name is the directory name', $fog_plugin['name'] === 'subnetstorage');
$check('manifest declares fog_min 1.6.0', $fog_plugin['fog_min'] === '1.6.0');

$m = [$base . 'Items\\SubnetStorage', 'matchLength'];
$cases = [
    ['192.168.1.0/24', '192.168.1.10', 24],
    ['192.168.1.0/24', '192.168.2.10', -1],
    ['10.0.0.0/8, 10.1.0.0/16', '10.1.2.3', 16],
    ['10.0.0.0/8,10.1.0.0/16', '10.2.2.3', 8],
    ['0.0.0.0/0', '172.16.5.4', 0],
    ['172.16.5.4/32', '172.16.5.4', 32],
    ['200.0.0.0/1', '250.1.1.1', 1],
    ['200.0.0.0/1', '100.1.1.1', -1],
    ['192.168.1.0/33', '192.168.1.1', -1],
    ['192.168.1.0', '192.168.1.1', -1],
    ['192.168.1.0/24', 'not-an-ip', -1],
    ['192.168.1.0/24', 'fe80::1', -1],
    ['', '192.168.1.1', -1],
];
foreach ($cases as list($subnets, $ip, $want)) {
    $got = call_user_func($m, $subnets, $ip);
    $check("matchLength('$subnets', '$ip') = $want, got $got", $got === $want);
}

$steps = (new \FOG\Plugins\SubnetStorage\Managers\SubnetStorageManager())->schema();
$check('schema() has 4 steps', count($steps) === 4);
$create = $steps[0];
$check('step 0 creates the table if absent', is_string($create)
    && stripos($create, 'CREATE TABLE IF NOT EXISTS `subnetstorage`') !== false);
$check('ssTftpEnabled is TINYINT(1)', (bool)preg_match('/`ssTftpEnabled` TINYINT\(1\)/i', $create));
$check('ssStorageNodeID is nullable', (bool)preg_match('/`ssStorageNodeID` INTEGER(,| NULL)/i', $create));
$check('ssStorageNodeProto is nullable', (bool)preg_match('/`ssStorageNodeProto` VARCHAR\(5\)(,| NULL)/i', $create));

$re = constant($base . 'Pages\\SubnetStorageManagement::SUBNETS_MATCH');
foreach (['10.0.0.0/8', '10.0.0.0/8, 192.168.0.0/16', '10.0.0.0/8 ,192.168.0.0/16'] as $ok) {
    $check("accepts '$ok'", preg_match($re, $ok) === 1);
}
foreach (['10.0.0.0', '10.0.0.0/33', '256.0.0.0/8', '10.0.0.0/8,', 'abc'] as $bad) {
    $check("refuses '$bad'", preg_match($re, $bad) === 0);
}

if ($fails) {
    echo "FAIL subnetstorage plugin:\n  " . implode("\n  ", $fails) . "\n";
    if (!empty($create)) {
        echo $create . "\n";
    }
    exit(1);
}
echo "ok subnetstorage plugin\n";
