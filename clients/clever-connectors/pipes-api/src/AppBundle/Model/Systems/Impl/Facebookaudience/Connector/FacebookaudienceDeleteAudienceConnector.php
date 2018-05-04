<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceDeleteAudienceConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceDeleteAudienceConnector extends FacebookaudienceConnectorAbstract
{

    private const URL = '%s/%s?access_token=%s';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-delete-audience-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws CurlException
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        if (!array_key_exists('ref_id', $data)) {
            throw new CleverConnectorsException(
                'Missing required field [ref_id]',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req           = $this->prepareRequestDto($systemInstall, $data['ref_id'], $dto);

        try {
            $this->manager->send($req);
            if (array_key_exists('mirror_id', $data)) {
                $this->removeMirror($data['mirror_id']);
            }
        } catch (CurlException $e) {
            return $this->logConnectorError($e, $systemInstall, $this->system, $dto);
        }

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $audienceId
     * @param string        $mirrorId
     *
     * @return bool
     * @throws CurlException
     * @throws SystemException
     */
    public function deleteAudience(SystemInstall $systemInstall, string $audienceId, string $mirrorId): bool
    {
        $req = $this->prepareRequestDto($systemInstall, $audienceId);

        try {
            $this->manager->send($req);
            $this->removeMirror($mirrorId);
        } catch (CurlException $e) {
            $this->logConnectorError($e, $systemInstall, $this->system);

            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param string $mirrorId
     */
    private function removeMirror(string $mirrorId): void
    {
        $repo = $this->dm->getRepository(AudienceMirror::class);
        $mirr = $repo->find($mirrorId);
        $this->dm->remove($mirr);
        $this->dm->flush();
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param string          $audienceId
     * @param ProcessDto|null $dto
     *
     * @return RequestDto
     * @throws SystemException
     */
    private function prepareRequestDto(
        SystemInstall $systemInstall,
        string $audienceId,
        ?ProcessDto $dto = NULL
    ): RequestDto
    {
        $token      = $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN];
        $requestDto = $this->system->getRequestDto($systemInstall, CurlManager::METHOD_DELETE);
        $url        = sprintf(self::URL, $requestDto->getUri(), $audienceId, $token);
        $requestDto->setUri(new Uri($url));

        if ($dto) {
            $requestDto->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));
        }

        return $requestDto;
    }

}