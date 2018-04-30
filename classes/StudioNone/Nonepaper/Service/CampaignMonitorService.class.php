<?php
/**
 * Nonepaper Campaign Monitor Class file
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperCampaignMonitor
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Service;

use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Interfaces\Service\CampaignMonitorServiceInterface;

/**
 * Campaign Monitor Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class CampaignMonitorService implements CampaignMonitorServiceInterface
{
    /**
     * Send Campaign
     *
     * @param string $html Html content to send
     *
     * @return string
     */
    public function sendCampaign($createCampaign, $confirmationEmail, $auth) : bool
    {
        try {
            $campaign = new \CS_REST_Campaigns($createCampaign->response, $auth);
            $response = $campaign->send(array('ConfirmationEmail' => $confirmationEmail,'SendDate' => 'immediately'));
            return $response->was_successful();
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Subscribe email to list
     *
     * @return void
     */
    public function subscribeEmail($email, $subscriberListId, $auth) : \CS_REST_Wrapper_Result
    {
        try {
            $subscribe = new \CS_REST_Subscribers($subscriberListId, $auth);
            return $subscribe->add(array('EmailAddress' => $email));
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Campaign Monitor Nonepaper Subscriber Lists
     *
     * @return array
     */
    public function getCmLists($clientId, $auth) : array
    {
        try {
            $subscriber = new \CS_REST_Clients($clientId, $auth);
            return $subscriber->get_lists()->response;
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Create New Campaign
     *
     * @param string $html Html of main content of email
     *
     * @return \CS_REST_Wrapper_Result
     */
    public function createNewCampaign($emailTitle, $emailSourceName, $emailSourceEmail, $lists, $templateId, $templateContent, $clientId, $auth) : \CS_REST_Wrapper_Result
    {
        try {
            $template = array(
                'Subject' => $emailTitle,
                'Name' => $emailTitle,
                'FromName' => $emailSourceName,
                'FromEmail' => $emailSourceEmail,
                'ReplyTo' => $emailSourceEmail,
                'ListIDs' => $lists,
                'SegmentIDs' => array(),
                'TemplateID' => $templateId,
                'TemplateContent' => $templateContent
            );
            $campaign = new \CS_REST_Campaigns(null, $auth);
            return $campaign->create_from_template($clientId, $template);
        } catch (NonepaperException $e) {
            throw $e;
        }
    }
}
