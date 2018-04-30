<?php
/**
 * Nonepaper Amazon SES Transformer Class file
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

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;
use StudioNone\Nonepaper\Exception\NonepaperException;
use StudioNone\Nonepaper\Service\AmazonSesService;
use StudioNone\Nonepaper\Interfaces\Service\AmazonSesServiceInterface;

/**
 * Amazon SES Service Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class AmazonSesTransformer
{
    protected const EMAIL_SOURCE = 'paper@studionone.com.au';
    protected $config;
    protected $amazonSes;
    
    /**
     * Construct
     *
     * @return void
     */
    public function __construct($config, AmazonSesService $amazonSes)
    {
        $this->config = $config;
        $this->amazonSes = $amazonSes;
    }

    /**
     * Send email
     *
     * @param string $to      Send email to
     * @param string $subject Email subject
     * @param string $body    Raw body of email
     *
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $body)
    {
        $this->amazonSes->sendEmail($to, self::EMAIL_SOURCE, $subject, $body, $this->getAwsAuth());
    }

    /**
     * Get Aws Auth Array
     *
     * @return array
     */
    protected function getAwsAuth() : array
    {
        return array('key' => $this->getAwsKey(), 'secret'  => $this->getAwsSecret());
    }

    /**
     * Get Aws Key
     *
     * @return string
     */
    protected function getAwsKey() : string
    {
        return $this->config['amazonSes']['awsSesKey'];
    }

    /**
     * Get Aws Secret
     *
     * @return string
     */
    protected function getAwsSecret() : string
    {
        return $this->config['amazonSes']['awsSesSecret'];
    }
}
