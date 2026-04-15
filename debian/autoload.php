<?php
/**
 * Debian autoloader for abraflexi-ipex
 */

// Load dependency autoloaders
require_once '/usr/share/php/EaseHtml/autoload.php';
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/IPEXB2B/autoload.php';
require_once '/usr/share/php/mpdf/vendor/autoload.php';

// PSR-4 autoloader for SpojeNet\AbraFlexiIpex namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'SpojeNet\\AbraFlexiIpex\\';
    $baseDir = '/usr/lib/abraflexi-ipex/AbraFlexiIpex/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
