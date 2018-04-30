<?php
/**
 * Nonepaper Newsletter Service Interface Class file
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

namespace StudioNone\Nonepaper\Interfaces\Service;

/**
 * Newsletter Service Interface Class
 *
 * @category Nonepaper
 * @package  Email
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
interface CampaignMonitorServiceInterface
{
    public function sendCampaign($createCampaign, $confirmationEmail, $auth);
    public function subscribeEmail($email, $subscriberListId, $auth);
    public function getCmLists($clientId, $auth);
    public function createNewCampaign($emailTitle, $emailSourceName, $emailSourceEmail, $lists, $templateId, $templateContent, $clientId, $auth);
}
