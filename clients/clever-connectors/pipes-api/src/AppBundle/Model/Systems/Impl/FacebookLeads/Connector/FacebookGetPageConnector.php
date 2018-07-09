<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 3:52 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookGetPageConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetPageConnector extends FacebookLeadConnectorAbstract
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * FacebookGetAccountConnector constructor.
     *
     * @param FacebookLeadsSystem $system
     * @param DocumentManager     $dm
     * @param CurlManager         $curlManager
     */
    public function __construct(
        FacebookLeadsSystem $system,
        DocumentManager $dm,
        CurlManager $curlManager
    )
    {
        parent::__construct($system, $dm);

        $this->curlManager = $curlManager;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebook-get-page-connector';

    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $requestDto    = $this->prepareRequestDto($systemInstall, $dto);

        try {
            $response = $this->curlManager->send($requestDto);
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto->setData($response->getBody());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        $requestDto = $this->prepareRequestDto($systemInstall);

        try {
            $response = $this->curlManager->send($requestDto);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);
        }

        if (isset($response) && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $data = json_decode($response->getBody(), TRUE);
            $res  = [];
            foreach ($data['data'] as $page) {
                $res[$page['id']] = $page['name'];
            }

            return $res;
        } else {
            throw new CleverConnectorsException(
                'Facebook Leads Error: Getting leads pages failed.', CleverConnectorsException::REQUEST_FAILED
            );
        }
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|null $dto
     *
     * @return RequestDto
     * @throws SystemException
     */
    private function prepareRequestDto(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): RequestDto
    {
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = new Uri($requestDto->getUri(TRUE) . '/me/accounts?limit=1000&fields=id%2Cname&access_token=' . urlencode($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return RequestDto::from($requestDto, $url);
    }

}