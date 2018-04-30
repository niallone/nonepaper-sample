<?php
/**
 * Nonepaper Config Loader Class File
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperConfig
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Container;

/**
 * Nonepaper Config Loader Class
 *
 * @category Nonepaper
 * @package  NonepaperConfig
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */

class ConfigLoader
{
    /**
     * Initialise
     *
     * @return void
     */
    public static function initialise()
    {
        return include NONEPAPER_ROOT_DIR.'/config/config.php';
    }
}
