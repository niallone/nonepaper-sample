<?php
/**
 * Nonepaper Feedly Service Interface Class file
 *
 * PHP version 7
 *
 * @category  NonepaperFeedly
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Interfaces\Service;

/**
 * Sync Service Interface Class
 *
 * @category Nonepaper
 * @package  Feedly
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
interface FeedlyServiceInterface
{
    public function getFeedlyArticles(string $apiUrl, $auth);
}
