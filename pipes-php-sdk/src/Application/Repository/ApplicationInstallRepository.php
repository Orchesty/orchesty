<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;

/**
 * Class ApplicationInstallRepository
 *
 * @package Hanaboso\PipesPhpSdk\Application\Repository
 */
class ApplicationInstallRepository extends DocumentRepository
{

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function findUserApp(string $key, string $user): ApplicationInstall
    {
        /** @var ApplicationInstall | null $app */
        $app = $this->createQueryBuilder()
            ->field(ApplicationInstall::KEY)->equals($key)
            ->field(ApplicationInstall::USER)->equals($user)
            ->getQuery()->getSingleResult();

        if (!$app) {
            throw new ApplicationInstallException(
                sprintf('Application [%s] was not found .', $key),
                ApplicationInstallException::APP_WAS_NOT_FOUND
            );
        }

        return $app;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function findUsersAppDefaultHeaders(ProcessDto $dto): ApplicationInstall
    {
        return $this->findUserApp(
            (string) PipesHeaders::get('application', $dto->getHeaders()),
            (string) PipesHeaders::get('user', $dto->getHeaders())
        );
    }

}

