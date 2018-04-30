<?php
/**
 * Nonepaper Class file
 *
 * PHP version 7
 *
 * @category  Nonepaper
 * @package   Nonepaper
 * @author    Niall Heffernan <niall@studionone.com.au>
 * @copyright 2017 Studio None
 * @license   Studio None https://www.studionone.com.au/
 * @link      https://www.studionone.com.au/
 */

namespace StudioNone\Nonepaper;

use StudioNone\NoneCore;
use StudioNone\Nonepaper\Transformer\AmazonSesTransformer;

/**
 * Nonepaper Class
 *
 * @category Nonepaper
 * @package  Nonepaper
 * @author   Niall Heffernan <niall@studionone.com.au>
 * @license  Studio None https://www.studionone.com.au/
 * @link     https://www.studionone.com.au/
 */
abstract class BaseController extends NoneCore
{
    /**
     * Construct
     *
     * @return void
     */
    public function __construct(AmazonSesTransformer $amazonSes)
    {
        parent::__construct($amazonSes);
        return;
    }
}
