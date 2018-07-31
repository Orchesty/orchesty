<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Repository\AudienceMirrorRepository;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class FacebookaudienceUpdateAudienceConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceUpdateAudienceConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-update-audience-connector';
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
        $data  = json_decode($dto->getData(), TRUE);
        $props = $data[Comparator::KEY_PASS_DATA];

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        $req           = $this->system->getRequestDto($sysInst, CurlManager::METHOD_POST);
        $this->baseUrl = (string) $req->getUri(TRUE);

        try {
            $id = $props['audience_id'];
            if (!$id) {
                $res = $this->createAudience($req, $sysInst, $props);
                $id  = json_decode($res->getBody(), TRUE)['id'];

                if (!$id) {
                    throw new CleverConnectorsException(
                        'Failed to create a new audience in facebook'
                    );
                }

                /** @var AudienceMirrorRepository $repo */
                $repo = $this->dm->getRepository(AudienceMirror::class);
                $mirr = $repo->getByAudience($props['audience']['id'], $props['type']);
                $mirr->setSystemAudienceId($id);
                $this->dm->flush();
                $data[Comparator::KEY_PASS_DATA]['audience_id'] = $id;
            }

            if (!empty($data['create'] ?? [])) {
                try {
                    $res  = $this->addUsers($sysInst, $id, $data);
                    $data = $this->unsetErrors($data, 'create', $res);
                } catch (CurlException $e) {
                    $this->logConnectorError($e, $sysInst, $this->system, $dto);
                    $data['create'] = [];
                }
            }
            if (!empty($data['delete'] ?? [])) {
                try {
                    $res  = $this->removeUsers($sysInst, $id, $data);
                    $data = $this->unsetErrors($data, 'delete', $res);
                } catch (CurlException $e) {
                    $this->logConnectorError($e, $sysInst, $this->system, $dto);
                    $data['delete'] = [];
                }
            }

            $dto->setData(json_encode($data));
        } catch (CurlException $e) {
            $this->logConnectorError($e, $sysInst, $this->system, $dto);
        }

        return $dto;
    }

    /**
     * @param RequestDto    $req
     * @param SystemInstall $sysInst
     * @param array         $data
     *
     * @return ResponseDto
     */
    private function createAudience(RequestDto $req, SystemInstall $sysInst, array $data): ResponseDto
    {
        $req->setUri(new Uri(sprintf('%s/act_%s/customaudiences', $this->baseUrl,
            $sysInst->getSettings()[FacebookaudienceSystem::AD_ACCOUNT])));

        $opt = [
            'form_params' => [
                'name'         => $data['audience']['name'],
                'subtype'      => 'CUSTOM',
                'description'  => $data['audience_description'] ?? '',
                'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
            ],
        ];

        return $this->manager->send($req, $opt);
    }

    /**
     * @param SystemInstall $sysInst
     * @param string        $audienceId
     * @param array         $data
     *
     * @return ResponseDto
     * @throws CurlException
     */
    private function addUsers(SystemInstall $sysInst, string $audienceId, array $data): ResponseDto
    {
        $req = new RequestDto(CurlManager::METHOD_POST,
            new Uri(sprintf('%s/%s/users', $this->baseUrl, $audienceId))
        );
        $req->setHeaders($this->getHeaders());

        $opt = [
            'form_params' => [
                'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                'payload'      => [
                    'schema' => 'EMAIL_SHA256',
                    'data'   => $data['create'],
                ],
            ],
        ];

        return $this->manager->send($req, $opt);
    }

    /**
     * @param SystemInstall $sysInst
     * @param string        $audienceId
     * @param array         $data
     *
     * @return ResponseDto
     * @throws CurlException
     */
    private function removeUsers(SystemInstall $sysInst, string $audienceId, array $data): ResponseDto
    {
        $req = new RequestDto(CurlManager::METHOD_DELETE,
            new Uri(sprintf('%s/%s/users', $this->baseUrl, $audienceId))
        );
        $req->setHeaders($this->getHeaders());

        $opt = [
            'form_params' => [
                'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                'payload'      => [
                    'schema' => 'EMAIL_SHA256',
                    'data'   => $data['delete'],
                ],
            ],
        ];

        return $this->manager->send($req, $opt);
    }

    /**
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Accept'       => 'application/json',
            'Content-Type' => 'multipart/form-data',
        ];
    }

    /**
     * @param array       $data
     * @param string      $key
     * @param ResponseDto $res
     *
     * @return array
     */
    private function unsetErrors(array $data, string $key, ResponseDto $res): array
    {
        $body = json_decode($res->getBody(), TRUE);
        foreach ($body['invalid_entry_samples'] as $hash => $err) {
            unset($data[$key][array_search(trim($hash, "\""), $data[$key])]);
        }

        return $data;
    }

}