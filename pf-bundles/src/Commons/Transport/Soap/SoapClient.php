<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use SoapClient as PhpSoapClient;

/**
 * Class SoapClient
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
class SoapClient extends PhpSoapClient
{

    /**
     * SoapClient constructor.
     *
     * @param mixed      $wsdl
     * @param array|null $options
     */
    public function __construct($wsdl, ?array $options = NULL)
    {
        parent::__construct($wsdl, $options);
    }

}