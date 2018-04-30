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

namespace StudioNone\Nonepaper\Transformer;

use StudioNone\Nonepaper\Service\FeedlyService;
use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Interfaces\Service\FeedlyServiceInterface;
use StudioNone\Nonepaper\Model\DataModel as Data;

/**
 * Feedly Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class FeedlyTransformer
{
    protected const REQUESTED_STORIES_COUNT = 100;
    protected $config;
    protected $feedly;
    protected $wordpress;
    protected $feedlyTeamName;
    
    /**
     * Construct
     *
     * @return void
     */
    public function __construct(array $config, FeedlyService $feedly, WordpressTransformer $wordpress)
    {
        $this->config = $config;
        $this->feedly = $feedly;
        $this->wordpress = $wordpress;
        $this->wordpress->loadWordpress();
        Data::setConnection($this->wordpress->getWpdb());
        $this->feedlyTeamName = Data::getPluginValue('feedly_team_name');
    }
 
    /**
     * Get Feedly Articles By Board Id
     *
     * @param string $boardId Board id
     *
     * @return \stdClass
     */
    public function getFeedlyArticles(string $boardId) : \stdClass
    {
        return $this->feedly->getFeedlyArticles($this->getFeedlyApiUrl($boardId), $this->getFeedlyAccessToken());
    }

    /**
     * Build Keywords
     *
     * @param object $story Feedly api response for single article
     *
     * @return array
     */
    public function buildKeywords(?array $storyKeywords) : array
    {
        $keywordsArray = [];
        if (!empty($storyKeywords) && is_array($storyKeywords)) {
            foreach ($storyKeywords as $keyword) {
                if (strpos($keyword, ',') !== false) {
                    $keywordStringArray = explode(',', $keyword);
                    if (count($keywordStringArray) > 0) {
                        foreach ($keywordStringArray as $keywordStringArrayItem) {
                            $keywordsArray[] = trim(ucwords($keywordStringArrayItem));
                        }
                    }
                } else {
                    $keywordsArray[] = trim(ucwords($keyword));
                }
            }
        }
        return $keywordsArray;
    }

    /**
     * Create Insert Array
     *
     * @param \stdClass $thisBoard Category (Feedly team board) object
     * @param \stdClass $story     Feedly api response for single article
     *
     * @return array
     */
    public function createInsertArray(int $boardId, \stdClass $story) : array
    {
        $insertArray = [];
        $insertArray['sent'] = 0;
        $insertArray['feedly_team_boards_id'] = $boardId;
        $insertArray['feedly_id'] = $story->id ?? null;
        $insertArray['origin_id'] = $story->originId ?? null;
        $insertArray['fingerprint'] = $story->fingerprint ?? null;
        $insertArray['feedly_id'] = $story->id ?? null;
        $insertArray['content'] = $story->fullContent ?? $story->content->content ?? $story->summary->content ?? '';
        $insertArray['title'] = $story->title ?? null;
        $insertArray['published'] = $story->published ?? null;
        $insertArray['crawled'] = $story->crawled ?? null;
        $insertArray['origin_title'] = $story->origin->title ?? null;
        $insertArray['origin_url'] = $story->origin->htmlUrl ?? null;
        $insertArray['author'] = $story->author ?? null;
        $insertArray['summary_content'] = $story->summary->content ?? $story->content->content ?? $story->fullContent ?? null;
        $insertArray['summary_content'] = $this->trimText($insertArray['summary_content'], 325);
        $insertArray['visual_processor'] = $story->visual->processor ?? null;
        $insertArray['visual_url'] = $story->visual->url ?? null;
        $insertArray['visual_width'] = $story->visual->width ?? null;
        $insertArray['visual_height'] = $story->visual->height ?? null;
        $insertArray['visual_content_type'] = $story->visual->contentType ?? null;
        $insertArray['canonical_url'] = $story->canonicalUrl ?? null;
        $insertArray['amp_url'] = $story->ampUrl ?? null;
        $insertArray['cdn_amp_url'] = $story->cdnAmpUrl ?? null;
        $insertArray['engagement'] = $story->engagement ?? null;
        $insertArray['engagement_rate'] = $story->engagementRate ?? null;
        if (!empty($story->thumbnail) && is_array($story->thumbnail)) {
            foreach ($story->thumbnail as $thumbnail) {
                if (isset($thumbnail->url)) {
                    $thumbnailUrl = $thumbnail->url;
                }
            }
            $insertArray['thumbnail_url'] = $thumbnailUrl;
        }
        if (!empty($story->enclosure) && is_array($story->enclosure)) {
            $enclosureArray = array();
            $i=0;
            foreach ($story->enclosure as $enclosure) {
                if (isset($enclosure->href)) {
                    $enclosureArray[$i]['href'] = $enclosure->href;
                }
                if (isset($enclosure->width)) {
                    $enclosureArray[$i]['width'] = $enclosure->width;
                }
                if (isset($enclosure->height)) {
                    $enclosureArray[$i]['height'] = $enclosure->height;
                }
                if (isset($enclosure->type)) {
                    $enclosureArray[$i]['type'] = $enclosure->type;
                }
                $i++;
            }
            $insertArray['enclosure'] = json_encode($enclosureArray);
        }
        
        if (isset($insertArray['canonical_url'])) {
            $websiteUrl = $insertArray['canonical_url'];
        } elseif (isset($insertArray['origin_id'])) {
            if (substr($insertArray['origin_id'], 0, 4) === "http") {
                $websiteUrl = $insertArray['origin_id'];
            } else {
                if (isset($insertArray['origin_url'])) {
                    $websiteUrl = $insertArray['origin_url'];
                }
            }
        } elseif (isset($insertArray['origin_url'])) {
            $websiteUrl = $insertArray['origin_url'];
        } else {
            $websiteUrl = false;
        }
        if ($websiteUrl) {
            $insertArray['content'] .= '<div class="visit-website-button"><a href="'.$websiteUrl.'" class="cmsmasters_button" target="_blank"><span>Visit Website</span></a></div>';
        }
        return $insertArray;
    }

    /**
     * Create Post Array
     *
     * @param array     $insertArray Article array (from base table)
     * @param \stdClass $thisBoard   Category (Feedly team board) object
     * @param \stdClass $storyTags   Feedly api 'tags' response for single article
     * @param int       $postId      If update action include this value - Wordpress post id
     *
     * @return array
     */
    public function createPostArray(array $insertArray, string $boardName, array $storyTags, int $postId = null) : array
    {
        $postArray = [];
        if (!is_null($postId)) {
            $postArray['ID'] = $postId;
        }
        $postArray['post_author'] = 1;
        $postDateSeconds = $insertArray['crawled'] / 1000;
        $postArray['post_date'] = date("Y-m-d H:i:s", $postDateSeconds);
        if (isset($insertArray['content'])) {
            $postContent = $insertArray['content'];
        } else {
            $postContent = '';
        }
        $postArray['post_content'] = $postContent;
        $postArray['post_title'] = $insertArray['title'];
        if (isset($insertArray['summary_content'])) {
            $postArray['post_excerpt'] = $insertArray['summary_content'];
        }
        $postArray['post_status'] = 'publish';
        $postArray['post_type'] = 'post';
        $postArray['post_category'] = $this->buildCategories($boardName, $storyTags);
        $postArray['meta_input'] = array('feedly_id' => $insertArray['feedly_id']);
        return $postArray;
    }

    /**
     * Build Categories
     *
     * @param string $boardName Title of board
     * @param string $storyTags Array of Feedly tags
     *
     * @return array
     */
    protected function buildCategories(string $boardName, array $storyTags) : array 
    {
        $postCategories = [];
        foreach ($storyTags as $category) {
            if (strpos($category->id, 'user/') === false && $category->label != 'unknown') {
                $categoryName= substr($category->label, 4);
                $postCategories[] = $this->wordpress->getWordpressCategoryId($categoryName);
            }
        }
        return $postCategories;
    }

    /**
     * Trim Text
     *
     * @param string $input     String to chop
     * @param int    $length    Length to chop at
     * @param bool   $ellipses  Include ellipses?
     * @param bool   $stripHtml Strip any html?
     *
     * @return string
     */
    protected function trimText(string $input, int $length, bool $ellipses = true, bool $stripHtml = true) : string
    {
        if ($stripHtml) {
            $input = strip_tags($input);
        }
        if (strlen($input) <= $length) {
            return $input;
        }
        $lastSpace = strrpos(substr($input, 0, $length), ' ');
        $trimmedText = substr($input, 0, $lastSpace);
        if ($ellipses) {
            $trimmedText .= '...';
        }
        if (strlen($trimmedText) > 0) {
            return $trimmedText;
        }
    }

    /**
     * Get Feedly Api Url
     *
     * @param string $boardId Board id
     *
     * @return string
     */
    protected function getFeedlyApiUrl(string $boardId) : string
    {
        return 'http://cloud.feedly.com/v3/streams/contents?count=' . self::REQUESTED_STORIES_COUNT . '&streamId=enterprise/' . $this->feedlyTeamName . '/tag/' . $boardId;
    }

    /**
     * Get Feedly Access Token
     *
     * @return string
     */
    protected function getFeedlyAccessToken() : string
    {
        return Data::getPluginValue('feedly_access_token');
    }

    /**
     * Get Feedly Refresh Token
     *
     * @return string
     */
    protected function getFeedlyRefreshToken() : string
    {
        return Data::getPluginValue('feedly_refresh_token');
    }

}
