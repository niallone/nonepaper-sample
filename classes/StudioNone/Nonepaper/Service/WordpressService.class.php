<?php
/**
 * Nonepaper Wordpress Service Class file
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperWordpress
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Service;

use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Interfaces\Service\WordpressServiceInterface;

/**
 * Wordpress Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class WordpressService
{
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
    public function doesPostExist(array $args) : bool
    {
        try {
            if (!empty(get_posts($args))) {
                return true;
            }
        } catch (NonepaperException $e) {
            throw $e;
        }
        return false;
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
        try {
            return wp_insert_post($postArray);
        } catch (NonepaperException $e) {
            throw $e;
        }
        return false;
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
        try {
            return wp_set_post_terms($postId, $keywordsArray);
        } catch (NonepaperException $e) {
            throw $e;
        }
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
        try {
            return wp_update_post($postArray);
        } catch (NonepaperException $e) {
            throw $e;
        }
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
        try {
            return wp_set_post_categories($postId, $postCategoriesArray);
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /* Get Wordpress Category Id
     * 
     * @param string $title Category title
     *
     * @return string
     */
    public function getWordpressCategoryId(string $title)
    {
        return get_cat_ID($title);
    }

    /* Get Wordpress Permalink
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getPermalink(int $postId)
    {
        return get_permalink($postId);
    }

    /* Get Wordpress Permalink
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getTemplateDirUri()
    {
        return get_template_directory_uri();
    }

    /* Get Wordpress Permalink
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getPostCategories(int $postId)
    {
        return wp_get_post_categories($postId);
    }

    /* Get Wordpress Category
     * 
     * @param int $postId Post Id
     *
     * @return string
     */
    public function getCategory(int $categoryId)
    {
        return get_category($categoryId);
    }

    /**
     * Load Wordpress Environment
     *
     * @return void
     */
    function includeWordpress() : void
    {
        //define('DISABLE_WP_CRON', true);
        require_once NONEPAPER_ROOT_DIR . '/../../../../wp-load.php';
        return;
    }

    /**
     * Get Wordpress db object
     *
     * @return \wpdb
     */
    function setWpdb() : \wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    
}
