<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 6.3.18
 * Time: 12:00
 */

namespace CleverConnectors\AppBundle\Model\CM\ListConnector;

use CleverConnectors\AppBundle\Model\CM\CMAuthorization;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMDistributionListAbstract
 *
 * @package CleverConnectors\AppBundle\Model\CM\ListConnector
 */
abstract class CMDistributionListAbstract extends CMAuthorization implements ConnectorInterface
{

    /**
     * @var CurlManagerInterface
     */
    protected $curl;

    /**
     * CMGetDistributionsConnector constructor.
     *
     * @param CurlManagerInterface $curl
     */
    public function __construct(CurlManagerInterface $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'CMDistributions has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}