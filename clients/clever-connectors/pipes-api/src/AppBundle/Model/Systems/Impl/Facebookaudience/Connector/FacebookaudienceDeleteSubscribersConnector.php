<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Nette\Utils\Json;

/**
 * Class FacebookaudienceDeleteSubscribersConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceDeleteSubscribersConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/%s/users';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-delete-subscribers-connector';
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

        // TODO
        if (!is_array($data) || !array_key_exists('name', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field "name"',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall    = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $customAudienceId = $systemInstall->getSettings()[FacebookaudienceSystem::CUSTOM_AUDIENCE];
        $requestDto       = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_DELETE);
        $requestDto
            ->setUri(new Uri(sprintf(self::URL, $requestDto->getUri(), $customAudienceId)))
            ->setBody($dto->getData())
            ->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        $response = $this->manager->send($requestDto);

        return $dto->setData($response->getBody());
    }

}