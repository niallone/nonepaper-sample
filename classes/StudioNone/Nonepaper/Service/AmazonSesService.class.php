<?php
/**
 * Nonepaper Amazon SES Service Class file
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   NonepaperAmazon
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper\Service;

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;
use StudioNone\Nonepaper\Exception\NonepaperException;
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
class AmazonSesService implements AmazonSesServiceInterface
{
    /**
     * Send raw email
     *
     * @param string $to      Send email to
     * @param string $subject Email subject
     * @param string $body    Raw body of email
     *
     * @return void
     */
    public function sendEmail(string $to, string $from, string $subject, string $body, array $auth) : void
    {
        $region = 'us-east-1';
        $charset = 'UTF-8';
        $client = SesClient::factory(
            array(
                'version'=> 'latest',
                'region' => $region,
                'credentials' => $auth
            )
        );
        try {
            $result = $client->sendEmail(
                [
                'Destination' => [
                  'ToAddresses' => [
                  $to,
                  ],
                ],
                'Message' => [
                  'Body' => [
                    'Text' => [
                      'Charset' => $charset,
                      'Data' => $body,
                    ],
                  ],
                  'Subject' => [
                    'Charset' => $charset,
                    'Data' => $subject,
                  ],
                ],
                'Source' => $from,
                ]
            );
        } catch (SesException $e) {
            throw $e;
        }
        return;
    }
}
