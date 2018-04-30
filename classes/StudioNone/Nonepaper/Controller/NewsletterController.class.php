<?php
/**
 * Nonepaper Newsletter Class file
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperNewsletter
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\ServerRequest;
use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\BaseController;
use StudioNone\Nonepaper\Model\DataModel as Data;
use StudioNone\Nonepaper\Interfaces\Controller\NewsletterControllerInterface;
use StudioNone\Nonepaper\Transformer\{
    CampaignMonitorTransformer,
    WordpressTransformer,
    AmazonSesTransformer
};

/**
 * Email Class
 *
 * @category Nonepaper
 * @package  NonepaperNewsletter
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class NewsletterController extends BaseController implements NewsletterControllerInterface
{
    protected const CM_RELATIVE_DIR = '/html/cm-template';
    protected const EMAIL_TEMPLATE_FILENAME = '/index.html';
    protected const EMAIL_TEMPLATE_LOGO = '/images/logo.png';
    protected const CM_ROOT_DIR = NONEPAPER_ROOT_DIR . self::CM_RELATIVE_DIR;
    protected const CM_SITE_DIR = NONEPAPER_SITE_DIR . self::CM_RELATIVE_DIR;
    protected $pruneOlderThanDays;
    protected $campaignMonitor;
    protected $wordpress;
    protected $request;

    /**
     * Construct
     *
     * @param CampaignMonitorTransformer $campaignMonitor transformer class 
     * @param WordpressTransformer       $wordpress       tansformer class 
     * @param ServerRequest              $request 
     * @param AmazonSesTransformer       $amazonSes 
     * 
     * @return void
     */
    public function __construct(CampaignMonitorTransformer $campaignMonitor, WordpressTransformer $wordpress, ServerRequest $request, AmazonSesTransformer $amazonSes)
    {
        parent::__construct($amazonSes);
        $this->campaignMonitor = $campaignMonitor;
        $this->wordpress = $wordpress;
        $this->request = $request;
        $this->wordpress->loadWordpress();
        Data::setConnection($this->wordpress->getWpdb());
        $this->pruneOlderThanDays = Data::getPluginValue('prune_older_than_days');
    }

    /**
     * Email Pool Prune
     * 
     * This cuts out the number of articles older then n days. 
     * They would have already published on the website, 
     * but just means content older than n days won't be in the emails.
     *
     * @return Response
     */
    public function emailPoolPrune() : Response
    {
        $countArticles = Data::emailPoolPrune($this->pruneOlderThanDays);
        $response = new Response();
        $response->getBody()->write('Email Pool Prune Finished - '.$countArticles.' Articles Pruned', 200);
        return $response;
    }

    /**
     * Reset Email Pool
     * 
     * This ctrl alt deletes every article in the pools. 
     * A complete resync would probably fix though - use with caution.
     *
     * @return Response
     */
    public function emailPoolReset() : Response
    {
        Data::markAllAsSent();
        $response = new Response();
        $response->getBody()->write('Email Pool Reset', 200);
        return $response;
    }

    /**
     * Run Email Cron
     *
     * @return void
     */
    public function runCron()
    {
        return $this->emailCron();
    }
    
    /**
     * Preview Email
     *
     * @return void
     */
    public function previewNewsletter()
    {
        return $this->emailPreview();
    }

    /**
     * Subscribe Email
     *
     * @return void
     */
    public function subscribeNewsletter()
    {
        return $this->emailSubscribe();
    }

    /**
     * View Email Pool
     *
     * @return Response
     */
    public function emailPool() : Response
    {
        if ($_GET['type'] == 'json') {
            $arr = [];
            foreach ($this->checkLevels() as $boardName => $count) {
                $arr[$boardName] = $count;
            }
            $response = new Response();
            $response->getBody()->write(json_encode($arr), 200);
            return $response;
        }
        $html = '<h3>Email Pool Levels</h3>';
        foreach ($this->checkLevels() as $boardName => $count) {
            $html .= '<strong>'.$boardName.'</strong> = '.$count.'<br>';
        }
        $response = new Response();
        $response->getBody()->write($html, 200);
        return $response;
    }
    
    /**
     * Subscribe To Newsletter
     *
     * @return Response
     */
    protected function emailSubscribe() : Response
    {
        $response = new Response();
        try {
            if ($this->campaignMonitor->subscribeEmail(
                $this->request->getParsedBody()['nonepaper_email']
            )->was_successful()
            ) {
                $response->getBody()->write('Thank you', 200);
            } else {
                throw new NonepaperException('Apologies, there was an error, please try again later');
            }
        } catch (NonepaperException $e) {
            $response->getBody()->write($e->getMessage(), 200);
        }
        return $response;
    }

    /**
     * Email Cron Entrypoint
     *
     * @return response
     */
    protected function emailCron() : Response
    {
        if ($this->campaignMonitor->sendCampaign(
            $this->campaignMonitor->buildEmailContentHtml(
                $this->campaignMonitor->getUnsentArticles()
            )
        )
        ) {
            $this->logData($this->checkLevels());
            $this->sendLog('pretty', 'Nonepaper Oil Levels');
            $response = new Response();
            $responseText = $this->sendLog('pretty', 'Nonepaper Oil Levels');
            $response->getBody()->write('SUCCESS', 200);
            return $response;
        }
    }

    /**
     * Email Preview
     *
     * @return Response
     */
    protected function emailPreview() : Response
    {
        $content = str_replace(
            '<multiline>Default Content</multiline>',
            $this->campaignMonitor->buildEmailContentHtml(
                $this->campaignMonitor->getUnsentArticles()
            ),
            str_replace(
                'images/logo.png',
                self::CM_SITE_DIR . self::EMAIL_TEMPLATE_LOGO,
                file_get_contents(
                    self::CM_ROOT_DIR . self::EMAIL_TEMPLATE_FILENAME
                )
            )
        );
        $response = new Response();
        $response->getBody()->write($content, 200);
        return $response;
    }

    /**
     * Check Levels
     *
     * @return array
     */
    protected function checkLevels() : array
    {
        $boards = [];
        foreach (Data::getFeedlyBoardIds(true) as $board) {
            $boards[$board->board_name] = Data::getArticleQueueCount($board->id);
        }
        return $boards;
    }
}
