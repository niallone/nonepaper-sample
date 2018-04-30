<?php
/**
 * Nonepaper Wordpress Service Class file
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
use StudioNone\Nonepaper\Interfaces\Service\WordpressServiceInterface;
use StudioNone\Nonepaper\Service\WordpressService;
use StudioNone\Nonepaper\Model\DataModel as Data;

/**
 * Wordpress Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class WordpressTransformer
{
    protected $config;
    protected $wordpress;
    
    /**
     * Construct
     *
     * @return void
     */
    public function __construct(array $config, WordpressService $wordpress)
    {
        $this->config = $config;
        $this->wordpress = $wordpress;
        $this->wordpress->loadWordpress();
        Data::setConnection($this->wordpress->getWpdb());
    }

    /**
     * Load Wordpress Environment
     *
     * @return void
     */
    public function loadWordpress() : void
    {
        $this->includeWordpress();
        return;
    }

    /**
     * Get Wordpress db object
     *
     * @return \wpdb
     */
    public function getWpdb() : \wpdb
    {
        return $this->setWpdb();
    }

    /**
     * Does the Wordpress post exist from the Feedly id
     *
     * @param string $feedlyId Feedly id of article
     *
     * @return bool
     */
    public function doesPostExist(string $feedlyId) : bool
    {
        $args = array('meta_query' => array(array('key' => 'feedly_id','value' => $feedlyId)),'post_type' => 'post','posts_per_page' => -1);
        return $this->wordpress->doesPostExist($args);
    }

    /**
     * Does the Wordpress post exist from the Feedly id
     *
     * @param string $feedlyId Feedly id of article
     *
     * @return bool
     */
    public function insertPost($postArray) : int
    {
        return $this->wordpress->insertPost($postArray);
    }
    
    /**
     * Set Post Tags
     *
     * @param int   $postId        Post id
     * @param array $keywordsArray Array of keywords to set as post tags
     *
     * @return void
     */
    public function setPostTags(int $postId, ?array $keywordsArray)
    {
        return $this->wordpress->setPostTags($postId, $keywordsArray);
    }

    /**
     * Update Post
     *
     * @param array $postArray Array of data to insert
     *
     * @return void
     */
    public function updatePost(array $postArray)
    {
        // VV Causing memory leak..
        return $this->wordpress->updatePost($postArray);
    }

    /**
     * Update Post Categories
     *
     * @param int   $postId              Id of post
     * @param array $postCategoriesArray Array of categories to insert
     *
     * @return void
     */
    public function updatePostCategories(int $postId, array $postCategoriesArray)
    {
        return $this->wordpress->updatePostCategories($postId, $postCategoriesArray);
    }

    /* Get Wordpress Category Id
     * 
     * @param string $title Category title
     *
     * @return string
     */
    public function getWordpressCategoryId(string $title)
    {
        return $this->wordpress->getWordpressCategoryId($title);
    }

    /* Get Wordpress Permalink
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getPermalink(int $postId)
    {
        return $this->wordpress->getPermalink($postId);
    }

    /* Get Wordpress Template Dir Uri
     *
     * @return string
     */
    public function getTemplateDirUri()
    {
        return $this->wordpress->getTemplateDirUri();
    }

    /* Get Wordpress Post Categories
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getPostCategories(int $postId)
    {
        return $this->wordpress->getPostCategories($postId);
    }

    /* Get Wordpress Category Id
     * 
     * @param int $categoryId Category Id
     *
     * @return string
     */
    public function getCategory(int $categoryId)
    {
        return $this->wordpress->getCategory($categoryId);
    }

    /**
     * Load Wordpress Environment
     *
     * @return void
     */
    protected function includeWordpress() : void
    {
        $this->wordpress->includeWordpress();
        return;
    }

    /**
     * Get Wordpress db object
     *
     * @return \wpdb
     */
    protected function setWpdb() : \wpdb
    {
        return $this->wordpress->setWpdb();
    }

    public function getPluginData()
    {
        $dbo = $this->getWpdb();
        $results = $dbo->get_results('select * from np_options');
        $return = [];
        foreach ($results as $result) {
            $return[$result->key] = $result->value;
        }
        $return['wordpress_categories'] = $this->getWordpressCategories();
        $return['feedly_boards'] = Data::getFeedlyBoards();
        return $return;

    }

    public function setPluginData($data)
    {
        $dbo = $this->getWpdb();
        $npOptions = [];
        $npOptions['feedly_cron_status'] = $data->feedlyCronStatus;
        $npOptions['feedly_cron_schedule'] = $data->feedlyCronSchedule;
        $npOptions['feedly_team_name'] = $data->feedlyTeamName;
        $npOptions['feedly_access_token'] = $data->feedlyAccessToken;
        $npOptions['feedly_refresh_token'] = $data->feedlyRefreshToken;
        $npOptions['newsletter_cron_status'] = $data->newsletterCronStatus;
        $npOptions['newsletter_cron_schedule'] = $data->newsletterCronSchedule;
        $npOptions['prune_cron_schedule'] = $data->pruneCronSchedule;
        $npOptions['prune_older_than_days'] = $data->pruneOlderThanDays;
        $npOptions['subscriber_list_prefix'] = $data->subscriberListPrefix;
        $npOptions['article_count_per_category'] = $data->articleCountPerCategory;
        $npOptions['newsletter_category_order'] = $data->newsletterCategoryOrder;
        $npOptions['newsletter_source_name'] = $data->newsletterSourceName;
        $npOptions['newsletter_source_email'] = $data->newsletterSourceEmail;
        $npOptions['newsletter_confirmation_email'] = $data->newsletterConfirmationEmail;
        foreach ($npOptions as $npOptionKey => $npOptionValue) {
            $dbo->update('np_options', array('value'=>$npOptionValue), array('key'=>$npOptionKey));
        }
        echo 1;
    }

    protected function getWordpressCategories()
    {
        return get_categories();
    }

}
