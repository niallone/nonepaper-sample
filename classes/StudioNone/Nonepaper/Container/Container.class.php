<?php
/**
 * Nonepaper Container Class File
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperContainer
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Container;

use League\Container\Container as BaseContainer;
use League\Container\ReflectionContainer;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use StudioNone\NoneCore;
use StudioNone\Nonepaper\Service\{
    AmazonSesService,
    CampaignMonitorService,
    FeedlyService,
    WordpressService
};
use StudioNone\Nonepaper\Transformer\{
    AmazonSesTransformer,
    CampaignMonitorTransformer,
    FeedlyTransformer,
    WordpressTransformer
};
use StudioNone\Nonepaper\Controller\{
    NewsletterController,
    FeedlySyncController
};

/**
 * Nonepaper Container Class
 *
 * @category Nonepaper
 * @package  NonepaperContainer
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class Container extends BaseContainer
{
    /**
     * Invoke
     *
     * @return void
     */
    public function __invoke()
    {
        // Stuff..
        $this->delegate(
            new ReflectionContainer
        );
        $this->share('emitter', SapiEmitter::class);
        $this->share(
            'request', function () {
                return ServerRequestFactory::fromGlobals(
                    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
                );
            }
        );
        $this->share('response', Response::class);
        $this->share('config', ConfigLoader::initialise());

        // Services Mapping..
        $this->share('feedly_service', FeedlyService::class);
        $this->share('ses_service', AmazonSesService::class);
        $this->share('campaignmonitor_service', CampaignMonitorService::class);
        $this->share('wordpress_service', WordpressService::class);

        // Transformer Mapping
        $this->share('ses_transformer', AmazonSesTransformer::class)
            ->withArgument('config')
            ->withArgument('ses_service');
        $this->share('campaignmonitor_transformer', CampaignMonitorTransformer::class)
            ->withArgument('config')
            ->withArgument('campaignmonitor_service')
            ->withArgument('wordpress_transformer');
        $this->share('wordpress_transformer', WordpressTransformer::class)
            ->withArgument('config')
            ->withArgument('wordpress_service');
        $this->share('feedly_transformer', FeedlyTransformer::class)
            ->withArgument('config')->withArgument('feedly_service')
            ->withArgument('wordpress_transformer');

        // Controller Mapping..
        $this->share('email_controller', NewsletterController::class)
            ->withArgument('campaignmonitor_transformer')
            ->withArgument('wordpress_transformer')
            ->withArgument('request')
            ->withArgument('ses_transformer');
        $this->share('feedly_controller', FeedlySyncController::class)
            ->withArgument('feedly_transformer')
            ->withArgument('wordpress_transformer')
            ->withArgument('ses_transformer')
            ->withArgument('request');

        // Other..
        $this->share('nonecore', NoneCore::class)->withArgument('ses_transformer');

        // Return Object..
        return $this;
    }
}
