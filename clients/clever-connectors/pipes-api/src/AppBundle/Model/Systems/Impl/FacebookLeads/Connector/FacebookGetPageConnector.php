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
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class FacebookGetPageConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetPageConnector implements ConnectorInterface
{

    /**
     * @var CurlManager
     */
    private $curlManager;

    /**
     * @var FacebookLeadsSystem
     */
    private $system;

    /** @var SystemInstallRepository|ObjectRepository */
    private $systemInstallRepository;

    /**
     * FacebookGetAccountConnector constructor.
     *
     * @param FacebookLeadsSystem $system
     * @param DocumentManager     $dm
     * @param CurlManager         $curlManager
     */
    public function __construct(FacebookLeadsSystem $system, DocumentManager $dm, CurlManager $curlManager)
    {
        $this->curlManager             = $curlManager;
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
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
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $response      = $this->makeRequest($systemInstall, $dto);

        return $dto->setData($response->getBody());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getAccounts(SystemInstall $systemInstall): array
    {
        $response = $this->makeRequest($systemInstall);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
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
     * @return ResponseDto
     */
    private function makeRequest(SystemInstall $systemInstall, ?ProcessDto $dto = NULL): ResponseDto
    {
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = new Uri($requestDto->getUri(TRUE) . '/me/accounts?limit=1000&fields=id%2Cname&access_token=' . urlencode($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN]));
        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $this->curlManager->send(RequestDto::from($requestDto, $url));
    }

}