<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\Wsdl\RequestDto;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SoapClient;
use SoapFault;

/**
 * Class SoapClientFactory
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap
 */
class SoapClientFactory implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SoapClientFactory constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return SoapClientFactory
     */
    public function setLogger(LoggerInterface $logger): SoapClientFactory
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param RequestDtoAbstract $request
     * @param array              $options
     *
     * @return SoapClient
     * @throws SoapException
     */
    public function create(RequestDtoAbstract $request, array $options): SoapClient
    {
        try {
            $wsdl = NULL;
            if ($request->getType() == SoapManagerInterface::MODE_WSDL) {
                /** @var RequestDto $request */
                $wsdl = strval($request->getUri());
            }

            return new SoapClient($wsdl, $options);

        } catch (SoapFault $e) {
            $this->logger->error(sprintf('Invalid WSDL: %s', $e->getMessage()));
            throw new SoapException('Invalid WSDL.', SoapException::INVALID_WSDL, $e);
        }
    }

}