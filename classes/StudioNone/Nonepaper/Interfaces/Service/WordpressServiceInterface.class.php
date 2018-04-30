<?php
/**
 * Nonepaper Wordpress Service Interface Class file
 *
 * PHP version 7
 *
 * @category  NonepaperWordpress
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Interfaces\Service;

/**
 * Wordpress Service Interface Class
 *
 * @category Nonepaper
 * @package  Wordpress
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
interface WordpressServiceInterface
{
    public function loadWordpress();
    public function getWpdb();
    public function doesPostExist(array $args);
    public function insertPost($postArray);
    public function setPostTags(int $postId, ?array $keywordsArray);
    public function updatePost(array $postArray);
    public function updatePostCategories(int $postId, array $postCategoriesArray);
    public function getWordpressCategoryId(string $title);
    public function getPermalink(int $postId);
}
