<?php
/**
 * Nonepaper Exception Class file
 *
 * PHP version 7
 * 
 * @category  Nonepaper
 * @package   Exception
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

Namespace StudioNone\Nonepaper\Exception;

/**
 * Nonepaper Class
 *
 * @category Nonepaper
 * @package  NonepaperException
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
class NonepaperException extends \Exception
{
    /**
     * Construct
     *
     * @param string    $message  State to be output
     * @param int       $code     Whether to add a newline to the end
     * @param Exception $previous Whether to use an html break or a terminal break
     *
     * @return void
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * To String
     *
     * @return void
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
