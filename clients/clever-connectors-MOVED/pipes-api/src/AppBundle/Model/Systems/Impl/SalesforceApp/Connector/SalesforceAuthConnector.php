<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.3.18
 * Time: 13:54
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Throwable;

/**
 * Class SalesforceAuthConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
class SalesforceAuthConnector implements ConnectorInterface
{

    private const URL = '%s/services/apexrest/CMHB/pipes/authorization';

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * SalesforceAuthConnector constructor.
     *
     * @param CurlManager $curl
     */
    public function __construct(CurlManager $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hbpf.connector.salesforce_app-auth-connector';
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
            'Salesforceapp has no support for Event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Salesforceapp has no support for Action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param SystemInstall       $systemInstall
     * @param SalesforceAppSystem $system
     *
     * @throws ConnectorException
     * @throws SystemException
     * @throws CurlException
     */
    public function sendAuthorizeConfirm(SystemInstall $systemInstall, SalesforceAppSystem $system): void
    {
        $dto = $system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $uri = new Uri(sprintf(static::URL, rtrim($dto->getUri(TRUE), '/')));
        $dto = RequestDto::from($dto, $uri);

        try {
            $this->curl->send($dto);
        } catch (Throwable | GuzzleException $t) {
            throw new ConnectorException($t->getMessage());
        }
    }

}