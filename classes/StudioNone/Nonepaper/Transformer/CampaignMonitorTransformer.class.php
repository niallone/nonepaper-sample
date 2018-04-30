<?php
/**
 * Nonepaper Campaign Monitor Class file
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

namespace StudioNone\Nonepaper\Transformer;

use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Interfaces\Service\CampaignMonitorServiceInterface;
use StudioNone\Nonepaper\Service\CampaignMonitorService;
use StudioNone\Nonepaper\Model\DataModel as Data;

/**
 * Campaign Monitor Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class CampaignMonitorTransformer
{
    protected $markAsSentItems = [];
    protected $config;
    protected $campaignMonitor;
    protected $wordpress;
    protected $articleCount;
    protected $confirmationEmail;
    protected $emailSourceName;
    protected $emailSource;
    
    /**
     * Construct
     *
     * @return void
     */
    public function __construct(array $config, CampaignMonitorService $campaignMonitor, WordpressTransformer $wordpress)
    {
        $this->config = $config;
        $this->campaignMonitor = $campaignMonitor;
        $this->wordpress = $wordpress;
        Data::setConnection($this->wordpress->getWpdb());
        $this->articleCount = Data::getPluginValue('article_count_per_category');
        $this->confirmationEmail = Data::getPluginValue('newsletter_confirmation_email');
        $this->emailSourceName = Data::getPluginValue('newsletter_source_name');
        $this->emailSource = Data::getPluginValue('newsletter_source_email');
    }

    /**
     * Send Campaign
     *
     * @param string $html Html content to send
     *
     * @return string
     */
    public function sendCampaign(string $html, $title = null) : bool
    {
        date_default_timezone_set("Australia/Brisbane");
        $createCampaign = $this->createNewCampaign($html, $title);
        if ($createCampaign->was_successful()) {
            $this->markAsSent();
            return $this->campaignMonitor->sendCampaign($createCampaign, $this->confirmationEmail, $this->getCmAuth());
        }
    }
    
    /**
     * Subscribe email to list
     *
     * @return void
     */
    public function subscribeEmail($email, $list = null) : \CS_REST_Wrapper_Result
    {
        if (is_null($list)) {
            $list = $this->getCmSubscriberListId();
        }
        return $this->campaignMonitor->subscribeEmail($email, $list, $this->getCmAuth());
    }

    /**
     * Get Unsent Articles
     *
     * @return array
     */
    public function getUnsentArticles() : array
    {
        $articles = [];
        $a=0;
        foreach (Data::getFeedlyBoardIds(true) as $board) {
            $boardArticles = Data::getArticlesByBoard($board->id);
            $articles[$a]['board_id'] = $board->id;
            $articles[$a]['board_name'] = $board->board_name;
            $i=0;
            foreach ($boardArticles as $article) {
                $articles[$a]['items'][$i]['article_id'] = $article->id;
                $articles[$a]['items'][$i]['post_id'] = $article->post_id;
                $articles[$a]['items'][$i]['title'] = $article->title;
                $articles[$a]['items'][$i]['timestamp'] = $article->crawled;
                $articles[$a]['items'][$i]['summary_content'] = $article->summary_content;
                $articles[$a]['items'][$i]['engagement'] = $article->engagement;
                $articles[$a]['items'][$i]['origin_title'] = $article->origin_title;
                $i++;
            }
            $a++;
        }
        return $articles;
    }

    /**
     * Build Email Content Html
     *
     * @param array $articles Builds the email html from an array of articles
     *
     * @return string
     */
    public function buildEmailContentHtml(array $articles) : string
    {
        $html = '';
        foreach ($articles as $article) {
            $i=0;
            if (isset($article['items']) && count($article['items']) > 0) {
                $html .= '<tr><td class="container" style="border-bottom:2px solid #666666;"><table cellpadding="0" cellspacing="0" width="100%" class="container"><tr><td class="main-title" style="font-family:Georgia, Times, serif; font-weight:700; font-style:italic; color:#000; font-size:34px; padding:25px 0 45px 0;">';
                $html .= $article['board_name'].'</td></tr><tr><td><div style="width: 100%;border-bottom: 2px solid #dfdfdf;box-shadow: 0 3px 0 #dfdfdf;"</td></tr>';
                
                foreach ($article['items'] as $articleContent) {
                    if ($i < $this->articleCount) {
                        $articleContentDate;
                        $articleContentTitle;
                        $articleContentSummary;
                        $articleUrl;
                        $articleContentEngagement;
                        $engagementHtml = '';
                        $articleContentTitle = $articleContent['title'] ?? null;
                        $articleContentSummary = $articleContent['summary_content'] ?? null;
                        $articleContentEngagement = $articleContent['engagement'] ?? null;
                        $articleContentDate = date("F j, Y", $articleContent['timestamp'] / 1000);
                        if (!empty($articleContent['post_id'])) {
                            $articleUrl = $this->wordpress->getPermalink($articleContent['post_id']);
                        }
                        if ($articleContentEngagement < 1000) {
                            $colour = 'green';
                        }
                        if ($articleContentEngagement > 999 && $articleContentEngagement < 10000) {
                            $colour = 'yellow';
                        }
                        if ($articleContentEngagement > 9999) {
                            $colour = 'red';
                        }
                        if ($articleContentEngagement > 99) {
                            if ($articleContentEngagement > 999) {
                                $articleContentEngagement = $articleContentEngagement / 1000;
                                $articleContentEngagement = number_format($articleContentEngagement, 2) . 'k';
                            }
                            $engagementHtml = '<span style="padding:0 5px;">|</span><span><img src="'.$this->wordpress->getTemplateDirUri().'/nonepaper/img/icon-fire-'.$colour.'.png" style="width:18px;margin-bottom:-3px;" />&nbsp;<span>'.$articleContentEngagement.'</span></span>';
                        }
                        $sourceHtml = '<span style="padding:0 5px;">|</span><span>'.$articleContent['origin_title'].'</span>';
                        $postId = $articleContent['post_id'];
                        $c=0;
                        $categoryHtml = '';
                        foreach ($this->wordpress->getPostCategories($postId) as $categoryId) {
                            $category = $this->wordpress->getCategory($categoryId);
                            if ($c > 0) {
                                $categoryHtml .= ', ';
                            }
                            $categoryHtml .= $category->name;

                            $c++;
                        }
                        $utmSource = urlencode('Daily Cypher');
                        $utmMedium = urlencode('email');
                        $utmCampaign = urlencode($articleContentDate);
                        $utmTerm = urlencode($article['board_name']);
                        $utmContent = urlencode($articleContentTitle);
                        $utmTrackingString = '?utm_source='.$utmSource.'&utm_medium='.$utmMedium.'&utm_campaign='.$utmCampaign.'&utm_term='.$utmTerm.'&utm_content='.$utmContent;
                        $html .= '<tr><td style="padding:30px 0; border-bottom:1px solid #e9e9e9;"><table cellpadding="0" cellspacing="0" width="100%"><tr><td class="dates" style="font-family:Arial, Helvetica, sans-serif; font-size:11px; color:#737373; padding-bottom:13px; text-transform:uppercase;">'.$articleContentDate.' <span style="padding:0 5px;">|</span> <span style="color:#000;">'.$categoryHtml.'</span> ' . $sourceHtml . $engagementHtml . '</td></tr>';
                        $html .= '<tr><td class="article-title" style="font-family:Georgia, Times, serif; font-weight:700; color:#000; font-size:26px; padding:0 0 13px 0;"><a href="'.$articleUrl.$utmTrackingString.'" style="color:#000; text-decoration:none;">'.$articleContentTitle.'</a></td></tr>';
                        $html .= '<tr><td class="body-font" style="font-family:Georgia, Times, serif; color:#333333; font-size:16px; line-height:21px;">'.$articleContentSummary.'</td></tr>';
                        $html .= '<tr><td class="dates" style="font-family:Arial, Helvetica, sans-serif; font-weight:700; font-size:11px; padding-top:15px; text-transform:uppercase;"><a href="'.$articleUrl.$utmTrackingString.'" style="color:#000; text-decoration:none;">READ MORE</a></td></tr>';
                        $html .= '</table></td></tr>';
                        $this->markAsSentItems[] = $articleContent['article_id'];
                    }
                    $i++;
                }
                $html .= '</table></td></tr>';
            }
        }
        return $html;
    }

    /**
     * Mark Articles as Sent
     *
     * @return void
     */
    protected function markAsSent() : void
    {
        if (count($this->markAsSentItems) > 0) {
            foreach ($this->markAsSentItems as $storyId) {
                Data::markArticleAsSent($storyId);
            }
        }
        return;
    }

    /**
     * Get Campaign Monitor Template Id
     *
     * @return string
     */
    protected function getCmNpTemplateId() : string
    {
        return $this->config['campaignMonitor']['campaignTemplateId'];
    }

    /**
     * Get Campaign Monitor Key
     *
     * @return string
     */
    protected function getCmKey() : string
    {
        return $this->config['campaignMonitor']['apiKey'];
    }

    /**
     * Get Campaign Monitor Subscriber List Id
     *
     * @return string
     */
    protected function getCmSubscriberListId() : string
    {
        return $this->config['campaignMonitor']['subscriberListId'];
    }

    /**
     * Get Campaign Monitor Client Id
     *
     * @return string
     */
    protected function getCmClientId() : string
    {
        return $this->config['campaignMonitor']['clientId'];
    }

    /**
     * Get Campaign Monitor Auth Array
     *
     * @return array
     */
    protected function getCmAuth() : array
    {
        return array('api_key' => $this->getCmKey());
    }

    /**
     * Get Campaign Monitor Nonepaper Subscriber Lists
     *
     * @return array
     */
    protected function getCmLists() : array
    {
        try {
            $listArray = [];
            $listResults = $this->campaignMonitor->getCmLists($this->getCmClientId(), $this->getCmAuth());
            foreach ($listResults as $list) {
                if (substr($list->Name, 0, 9) === Data::getPluginValue('subscriber_list_prefix') && strpos($list->Name, 'Tests') === false) {
                    $listArray[] = $list->ListID;
                }
            }
            if (ENVIRONMENT === 'dev') {
                $listArray = array('8aa8b97c3595ddf8cf194d0a8a704dc4');
            }
            return $listArray;
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
    protected function createNewCampaign(string $html, ?string $emailTitle = null) : \CS_REST_Wrapper_Result
    {
        $templateContent = array('Multilines' => array(array('Content' => $html)));
        if (is_null($emailTitle)) {
            $emailTitle = 'Highlights, '.date("l F j, Y");
            if (date('w') == 1) {
                $emailTitle = 'Weekend Catchup, ' . date('j', strtotime("-2 days")) . ' & ' . date('j', strtotime("-1 days")) . ' ' . date("F, Y");
            }
            if (ENVIRONMENT === 'dev') {
                $emailTitle = $emailTitle . ' - test('.date("j/n/Y g:i").')';
            }
        }
        return $this->campaignMonitor->createNewCampaign($emailTitle, Data::getPluginValue('newsletter_source_name'), Data::getPluginValue('newsletter_source_email'), $this->getCmLists(), $this->getCmNpTemplateId(), $templateContent, $this->getCmClientId(), $this->getCmAuth());
    }
}
