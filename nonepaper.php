<?php
/**
 * Nonepaper Entry File
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperEntry
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

// Set some core configs..
date_default_timezone_set('Australia/Brisbane');
set_time_limit(3600);

// Set root paths..
define('HTML_ROOT_DIR', '/var/www/html');
define('NONEPAPER_ROOT_DIR', __DIR__);
define('NONEPAPER_SITE_DIR', str_replace(HTML_ROOT_DIR, '', NONEPAPER_ROOT_DIR));

// Set environment type..
define('ENVIRONMENT', getenv('NONEPAPER_ENVIRONMENT'));

if (ENVIRONMENT === false || strlen(ENVIRONMENT) === 0) {
    throw new \RuntimeException('Environment required');
}

// Load classes..
require_once NONEPAPER_ROOT_DIR . '/nonepaper.loader.php';

// Capture request..
if (isset($_GET['request']) === true) {
    $request = $_GET['request'];
} elseif (isset($argv[1]) === true) {
    $request = $argv[1];
} elseif ($isCron === true) {
    return require_once NONEPAPER_ROOT_DIR . '/config/cron.php';
} else {
    throw new \RuntimeException('Request required');
}

// Create DI app container..
$container = (new StudioNone\Nonepaper\Container\Container)();

//Route request..
switch ($request) {
    case 'feedly_cron':
        $nonepaperFeedly = $container->get('feedly_controller');
        $response = $nonepaperFeedly->runCron();
        $container->get('emitter')->emit($response);
        break;
    case 'email_cron':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->runCron();
        $container->get('emitter')->emit($response);
        break;
    case 'email_preview':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->previewNewsletter();
        $container->get('emitter')->emit($response);
        break;
    case 'email_subscribe':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->subscribeNewsletter();
        $container->get('emitter')->emit($response);
        break;
    case 'email_pool':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->emailPool();
        $container->get('emitter')->emit($response);
        break;
    case 'email_pool_prune':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->emailPoolPrune();
        $container->get('emitter')->emit($response);
        break;
    case 'email_pool_reset':
        $nonepaperEmail = $container->get('email_controller');
        $response = $nonepaperEmail->emailPoolReset();
        $container->get('emitter')->emit($response);
        break;
    case 'plugin_data':
        $nonepaperFeedly = $container->get('feedly_controller');
        $response = $nonepaperFeedly->pluginData();
        $container->get('emitter')->emit($response);
        break;
}
