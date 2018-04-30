<?php
/**
 * Nonepaper Newsletter Controller Interface Class file
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
 * Newsletter Controller Interface Class
 *
 * @category Nonepaper
 * @package  Email
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
interface NewsletterControllerInterface
{
    public function runCron();
    public function previewNewsletter();
    public function subscribeNewsletter();
}
