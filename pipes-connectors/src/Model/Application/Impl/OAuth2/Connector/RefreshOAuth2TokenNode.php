<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\OAuth2\Connector;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
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
     * @param ApplicationLoader $loader
     */
    public function __construct(private ApplicationLoader $loader)
    {
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
     * @throws MongoDBException
     * @throws MappingException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationId = PipesHeaders::get(GetApplicationForRefreshBatchConnector::NAME, $dto->getHeaders());
        /** @var ApplicationInstall|null $applicationInstall */
        $applicationInstall = $this->getDb()->getRepository(ApplicationInstall::class)->find($applicationId);

        if ($applicationInstall) {
            /** @var OAuth2ApplicationAbstract $application */
            $application = $this->loader->getApplication($applicationInstall->getKey());
            $application->refreshAuthorization($applicationInstall);
            $this->getDb()->flush();
        }

        return $dto;
    }

}
