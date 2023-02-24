<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\PipesHeaders;

/**
 * Class RefreshOAuth2TokenNode
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector
 */
final class RefreshOAuth2TokenNode extends CommonNodeAbstract
{

    public const NAME = 'refresh_oauth2_token';

    /**
     * RefreshOAuth2TokenNode constructor.
     *
     * @param ApplicationLoader            $loader
     * @param ApplicationInstallRepository $repository
     */
    public function __construct(
        private readonly ApplicationLoader $loader,
        private readonly ApplicationInstallRepository $repository,
    )
    {
        parent::__construct($this->repository);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ApplicationInstallException
     * @throws AuthorizationException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationId = PipesHeaders::get(GetApplicationForRefreshBatchConnector::NAME, $dto->getHeaders());
        /** @var ApplicationInstall|null $applicationInstall */
        $applicationInstall = $this->repository->findById($applicationId ?? '');

        if ($applicationInstall) {
            /** @var OAuth2ApplicationAbstract $application */
            $application = $this->loader->getApplication($applicationInstall->getKey() ?? '');
            $application->refreshAuthorization($applicationInstall);
        }

        return $dto;
    }

}
