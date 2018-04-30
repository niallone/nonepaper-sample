<?php
/**
 * Nonepaper Sync Interface Class file
 *
 * PHP version 7
 *
 * @category  NonepaperEmail
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Interfaces\Controller;

/**
 * Feedly Interface Class
 *
 * @category Nonepaper
 * @package  Email
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
interface SyncControllerInterface
{
    public function runCron();
}
