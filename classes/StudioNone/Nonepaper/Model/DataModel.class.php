<?php
/**
 * Nonepaper Model file
 *
 * PHP version 7
 *
 * @category  Data
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Model;

use StudioNone\Nonepaper\Exception\NonepaperExecption;

/**
 * Nonepaper Model
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class DataModel
{
    protected static $dbo;
    
    /**
     * Connect
     *
     * @param object $dbo Database object
     *
     * @return void
     */
    public static function setConnection($dbo)
    {
        self::$dbo = $dbo;
        return;
    }

    /**
     * Prune Articles Older Than n Days
     * 
     * @param int $days number of days to chop off
     * 
     * @return int
     */
    public static function emailPoolPrune($days = 10) : int
    {
        try {
            return self::$dbo->query('update feedly_stories set sent = 1 where sent = 0 and crawled/1000 < UNIX_TIMESTAMP(NOW() - INTERVAL '.$days.' DAY);');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Mark All Articles as Sent
     *
     * @return void
     */
    public static function markAllAsSent() : void
    {
        try {
            self::$dbo->query('update feedly_stories set sent = 1');
            return;
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Feedly board ids
     *
     * @param bool $withFeatured Execute query with or without the featured board
     *
     * @return array
     */
    public static function getFeedlyBoardIds(bool $withFeatured = true) : array
    {
        try {
            $featured = '';
            if (!$withFeatured) {
                $featured = 'where board_name != "Featured" ';
            }
            return self::$dbo->get_results('select id, board_id, board_name from feedly_team_boards ' . $featured . 'order by board_order');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get board article count
     * 
     * @param string $boardId Feedly board id
     *
     * @return int
     */
    public static function getArticleQueueCount($boardId) : int
    {
        try {
            return self::$dbo->get_var('select count(id) from feedly_stories where feedly_team_boards_id = '.$boardId.' and sent = 0');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Feedly board by board id
     *
     * @param string $boardId Feedly board id
     *
     * @return \stdClass
     */
    public static function getFeedlyBoardByBoardId(string $boardId) : \stdClass
    {
        try {
            return self::$dbo->get_row('select id, board_name from feedly_team_boards where board_id = "'.$boardId.'"');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Feedly boards
     *
     * @return \stdClass
     */
    public static function getFeedlyBoards() : array
    {
        try {
            return self::$dbo->get_results('select * from feedly_team_boards order by board_order');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Wordpress post id by feedly article id
     *
     * @param string $feedlyId Feedly article id
     *
     * @return int
     */
    public static function getPostIdByFeedlyId(string $feedlyId) : ?int
    {
        try {
            return self::$dbo->get_var('select post_id from feedly_stories where feedly_id = "'.$feedlyId.'" and post_id is not null');
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Articles by board id
     *
     * @param string $boardId Feedly board id
     *
     * @return array
     */
    public static function getArticlesByBoard(string $boardId) : array
    {
        try {
            return self::$dbo->get_results(self::getEmailAlgorithm($boardId));
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Email Algorithm
     *
     * @param string $boardId Feedly board id
     *
     * @return array
     */
    public static function getEmailAlgorithm(string $boardId) : string
    {
        $engagement = '';
        if (self::getPluginValue('newsletter_category_order') == 'engagement') {
            $engagement = ' engagement desc,';
        }
        return 'select id, post_id, feedly_team_boards_id, title, crawled, summary_content, engagement, origin_title from feedly_stories where feedly_team_boards_id = ' . $boardId . ' and sent = 0 order by'.$engagement.' crawled desc';
    }

    /**
     * Mark articles as sent
     *
     * @param int $storyId Article id
     *
     * @return void
     */
    public static function markArticleAsSent(int $storyId) : void
    {
        try {
            self::$dbo->query('update feedly_stories set sent = 1 where id = '.$storyId);
            return;
        } catch (NonepaperException $e) {
            throw $e;
        }
    }
    
    /**
     * Insert Article into Database
     *
     * @param array $insertArray Array of data to insert
     *
     * @return int
     */
    public static function insertArticle(array $insertArray) : Int
    {
        $count = self::$dbo->get_var('select count(id) from feedly_stories where feedly_id = "'.$insertArray['feedly_id'].'"');
        if ($count == 0) {
            try {
                self::$dbo->insert('feedly_stories', $insertArray);
                return self::$dbo->insert_id;
            } catch (NonepaperException $e) {
                throw $e;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Insert Post into Wordpress
     *
     * @param int   $articleId Id of article that the post is attached to
     * @param array $postId    Array of data to insert
     *
     * @return Int
     */
    public static function setArticlePostId(int $articleId, array $postId) : int
    {
        try {
            //$postId = wp_insert_post($postArray);
            self::$dbo->query('update feedly_stories set post_id = '.$postId.' where id = '.$articleId);
            return $postId;
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Insert Post into Wordpress
     *
     * @param int   $articleId Id of article that the post is attached to
     * @param array $postId    Array of data to insert
     *
     * @return Int
     */
    public static function connectArticlePost(int $articleId, int $postId) : int
    {
        try {
            return self::$dbo->query('update feedly_stories set post_id = '.$postId.' where id = '.$articleId);
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Update Article
     *
     * @param int   $postId      Id of post to update
     * @param array $insertArray Array of data to insert
     *
     * @return void
     */
    public static function updateArticle(int $postId, array $insertArray) : void
    {
        try {
            self::$dbo->update('feedly_stories', $insertArray, array('ID'=>$postId));
            return;
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

    /**
     * Get Plugin Value
     *
     * @param string $key   
     *
     * @return void
     */
    public static function getPluginValue($key = '')
    {
        try {
            if ($key != '') {
                return self::$dbo->get_var("SELECT `value` FROM `np_options` WHERE `key` = '".$key."'");
            } else {
                throw new NonepaperException('Key not set');
            }
        } catch (NonepaperException $e) {
            throw $e;
        }
    }

}
