<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\ShoptetApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;

/**
 * Class ShoptetGetApiAccessTokenConnector
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Shoptet\Connector
 */
class ShoptetGetApiAccessTokenConnector extends ShoptetConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shoptet-get-access-token';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUserAppByHeaders($dto);
        $response           = $this->processActionArray($applicationInstall, $dto);

        return $this->setJsonContent($dto, $response);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDto|null    $processDto
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function processActionArray(ApplicationInstall $applicationInstall, ?ProcessDto $processDto = NULL): array
    {
        /** @var ShoptetApplication $application */
        $application = $this->application;
        $requestDto  = $application->getApiTokenDto($applicationInstall);
        if ($processDto) {
            $requestDto->setDebugInfo($processDto);
        }

        return $this->sender->send($requestDto)->getJsonBody();
    }

}
