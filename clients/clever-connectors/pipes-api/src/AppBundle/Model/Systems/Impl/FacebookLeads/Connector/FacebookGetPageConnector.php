<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 3:52 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

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
     * FacebookGetAccountConnector constructor.
     *
     * @param CurlManager $curlManager
     */
    public function __construct(CurlManager $curlManager)
    {

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
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        // TODO: Implement processEvent() method.
    }


    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        // TODO: Implement processAction() method.
    }

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     *
     * @return array
     */
    public function getAccounts(SystemInterface $system, SystemInstall $systemInstall): array
    {
        $requestDto  = $system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url = new Uri($requestDto->getUri(TRUE) . '/me/accounts?limit=1000&fields=access_token%2Cname&access_token=' . urlencode($systemInstall->getSettings()['user_access_token']));
        $response = $this->curlManager->send(RequestDto::from($requestDto, $url));
        if ($response->getStatusCode() >= 200 && $response->getStatusCode()){
            $data = json_decode($response->getBody(), TRUE);
            $res = [];
            foreach ($data['data'] as $page){
                $res[$page['access_token']] = $page['name'];
            }

            return $res;

        } else {
            return[]; // TODO Vyhodit exception
        }

    }
}