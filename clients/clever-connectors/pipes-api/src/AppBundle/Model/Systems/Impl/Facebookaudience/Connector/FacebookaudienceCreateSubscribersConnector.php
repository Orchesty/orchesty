<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Nette\Utils\Json;

/**
 * Class FacebookaudienceCreateSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceCreateSubscribersConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/%s/users?access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-create-subscribers-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists('payload', $data) || !array_key_exists('data', $data['payload'])) {
            throw new CleverConnectorsException(
                'Missing data or required field "payload"',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall    = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $token            = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $customAudienceId = $systemInstall->getSettings()[FacebookaudienceSystem::CUSTOM_AUDIENCE];
        $requestDto       = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_POST);
        $requestDto
            ->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $customAudienceId, $token)))
            ->setBody($dto->getData())
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $response = $this->manager->send($requestDto);

        return $dto->setData($response->getBody());
    }

}