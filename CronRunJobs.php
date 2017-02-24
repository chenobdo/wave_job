<?php
/**
 * Created by PhpStorm.
 * User: Gabriel
 * Date: 2017/2/9
 * Time: 17:58
 */

define('ROOT_PATH', dirname(__FILE__) . '/..');

require ROOT_PATH . '/vendor/autoload.php';
require ROOT_PATH . '/../common/environment.php';
$envConfig = 'product';
if ($environment === 2) {
    $envConfig = 'local_dev';
} elseif ($environment === 3) {
    $envConfig = 'test_dev';
}
$envMain = 'kugou';
$configfile = ROOT_PATH . '/../common/config/' . $envMain . '/' . $envConfig . '/main.php';
$wave = new Wave($configfile);

$dirname = ROOT_PATH . '/crontab/';
$basename = 'JobsShell.php';
include_once $dirname . $basename;
$jobsShell = new JobsShell();
$jobsShell->main();
