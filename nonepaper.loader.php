<?php
/**
 * Nonepaper loader file
 *
 * PHP version 7
 *
 * @category  NoneCore
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

// Load dependencies..
require_once HTML_ROOT_DIR.'/vendor/autoload.php';

// Load classes..
spl_autoload_register(
    function ($class) {
        $filename = __DIR__ . '/classes' . '/' . str_replace('\\', '/', $class) . '.class.php';
        if (!file_exists($filename)) {
            return false;
        }
        include $filename;
        return true;
    }
);
