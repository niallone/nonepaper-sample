<?php
/**
 * Nonepaper Feedly Sync File
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperFeedlySync
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\ServerRequest;
use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\BaseController;
use StudioNone\Nonepaper\Model\DataModel as Data;
use StudioNone\Nonepaper\Interfaces\Controller\SyncControllerInterface;
use StudioNone\Nonepaper\Service\FeedlyService;
use StudioNone\Nonepaper\Service\WordpressService;
use StudioNone\Nonepaper\Transformer\FeedlyTransformer;
use StudioNone\Nonepaper\Transformer\WordpressTransformer;
use StudioNone\Nonepaper\Transformer\AmazonSesTransformer;

/**
 * Nonepaper Feedly Sync Class
 *
 * @category Nonepaper
 * @package  NonepaperFeedlySync
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class FeedlySyncController extends BaseController implements SyncControllerInterface
{
    protected $request;
    protected $feedly;
    protected $wordpress;

    /**
     * Construct
     *
     * @param FeedlyTransformer    $feedly    Feedly transformer di container
     * @param WordpressTransformer $wordpress Wordpress transformer di container
     * @param AmazonSesTransformer $amazonSes AmazonSes transformer di container
     * @param ServerRequest        $request   Request object
     *
     * @return void
     */
    public function __construct(FeedlyTransformer $feedly, WordpressTransformer $wordpress, AmazonSesTransformer $amazonSes, ServerRequest $request)
    {
        parent::__construct($amazonSes);
        $this->request = $request;
        $this->feedly = $feedly;
        $this->wordpress = $wordpress;
        $this->wordpress->loadWordpress();
        Data::setConnection($this->wordpress->getWpdb());
        return;
    }

    /** 
     * Run Feedly Sync Cron
     *
     * @return void
     */
    public function runCron()
    {
        if (Data::getPluginValue('feedly_cron_status') == 1) {
            return $this->feedlyCron(); 
        }
    }

    /**
     * Plugin Data Management
     *
     * @return JsonResponse
     */
    public function pluginData()
    {
        if (isset($this->request->getParsedBody()['plugin_update'])) {
            $data = json_decode(stripslashes($this->request->getParsedBody()['plugin_update']));
            $this->wordpress->setPluginData($data);
            exit;
        }
        $response = new JsonResponse($this->wordpress->getPluginData());
        return $response;
    }

    /**
     * Feedly Sync Cron
     *
     * @return Response
     */
    protected function feedlyCron() : Response
    {
        $responseText = '';
        foreach (Data::getFeedlyBoardIds() as $board) {
            $responseText .= $this->scanArticles($this->feedly->getFeedlyArticles($board->board_id), $board->board_id);
        }
        $response = new Response();
        $response->getBody()->write($responseText, 200);
        return $response;
    }

    /**
     * Scan Articles
     *
     * @param \stdClass $stories Feedly api response
     * @param string    $boardId Feedly board id
     *
     * @return string
     */
    protected function scanArticles(\stdClass $stories, string $boardId) : string
    {
        if (is_array($stories->items) && count($stories->items) > 0) {
            $ac = 0;
            $uc = 0;
            $thisBoard = Data::getFeedlyBoardByBoardId($boardId);
            foreach ($stories->items as $story) {
                $insertArray = $this->feedly->createInsertArray($thisBoard->id, $story);
                $postId = Data::getPostIdByFeedlyId($insertArray['feedly_id']);
                if (is_null($postId)) {
                    $this->addNewArticle($insertArray, $thisBoard->board_name, $story);
                    $ac++;
                } else {
                    $this->updateArticle($insertArray, $thisBoard->board_name, $story, $postId);
                    $uc++;
                }
                wp_cache_flush();
            }
            return $thisBoard->board_name." - ".$ac." Articles Added, ".$uc." Articles Updated<br>";
        } else {
            throw new NonepaperException('No articles');
        }
    }
  
    /**
     * Add New Article
     *
     * @param array     $insertArray Article array (from base table)
     * @param string    $boardName   Board name
     * @param \stdClass $story       Feedly api response for single article
     *
     * @return void
     */
    protected function addNewArticle(array $insertArray, string $boardName, \stdClass $story) : void
    {
        if (!$this->wordpress->doesPostExist($insertArray['feedly_id'])) {
            $postArray = $this->feedly->createPostArray($insertArray, $boardName, $story->tags);
            $articleId = Data::insertArticle($insertArray);
            if ($articleId) {
                $postId = $this->wordpress->insertPost($postArray);
                Data::connectArticlePost($articleId, $postId);
                if (isset($story->keywords)) {
                    if (count($story->keywords) > 0) {
                        $this->wordpress->setPostTags($postId, $story->keywords);
                    }
                }
            }
        }
        return;
    }

    /**
     * Update Article
     *
     * @param array     $insertArray Article array (from base table)
     * @param string    $boardName   Board name
     * @param \stdClass $story       Feedly api response for single article
     * @param int       $postId      Id of Wordpress post to update
     *
     * @return void
     */
    protected function updateArticle(array $insertArray, string $boardName, \stdClass $story, int $postId) : void
    {
        Data::updateArticle($postId, $insertArray);
        $postArray = $this->feedly->createPostArray($insertArray, $boardName, $story->tags, $postId);
        $this->wordpress->updatePost($postArray);
        $this->wordpress->updatePostCategories($postId, $postArray['post_category']);
        return;
    }


}
