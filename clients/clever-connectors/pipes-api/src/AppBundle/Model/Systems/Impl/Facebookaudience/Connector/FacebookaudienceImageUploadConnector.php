<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;

/**
 * Class FacebookaudienceImageUploadConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
class FacebookaudienceImageUploadConnector extends FacebookaudienceConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebookaudience-upload-image-connector';
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
        $data = json_decode($dto->getData(), TRUE);

        $sysInst = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $req     = $this->system->getRequestDto($sysInst, 'POST');
        $req->setUri(new Uri(sprintf('%s/act_%s/adimages', $req->getUri(TRUE),
            $sysInst->getSettings()[FacebookaudienceSystem::AD_ACCOUNT])));
        $req->setDebugInfo(CMHeaders::debugInfo($dto->getHeaders()));

        foreach ($data['ad_data'] as $index => $item) {
            if (!array_key_exists('image_content', $item)) {
                unset($data['ad_data'][$index]);
                continue;
            }

            $opt = [
                'form_params' => [
                    'access_token' => $sysInst->getSettings()[OAuth2Provider::ACCESS_TOKEN],
                    'bytes'        => $item['image_content'],
                ],
            ];

            try {
                $res                                   = $this->manager->send($req, $opt);
                $data['ad_data'][$index]['image_hash'] = json_decode($res->getBody(), TRUE)['images']['bytes']['hash'];
                unset($data['ad_data'][$index]['image_content']);
            } catch (CurlException $e) {
                $this->logConnectorError($e, $sysInst, $this->system, $dto);
            }
        }

        return $dto->setData(json_encode($data));
    }

}