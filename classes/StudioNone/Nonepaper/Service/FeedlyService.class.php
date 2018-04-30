<?php
/**
 * Nonepaper Feedly Service Class file
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

namespace StudioNone\Nonepaper\Service;

use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Interfaces\Service\FeedlyServiceInterface;
use StudioNone\Nonepaper\Service\FeedlyService;

/**
 * Feedly Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class FeedlyService implements FeedlyServiceInterface
{
    /**
     * Get Feedly Articles By Board
     *
     * @param string $apiUrl Api url
     *
     * @return \stdClass
     */
    public function getFeedlyArticles(string $apiUrl, $auth) : \stdClass
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth '.$auth));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output);
        } catch (NonepaperException $e) {
            throw $e;
        }
    }
}
