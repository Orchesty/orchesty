<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Exception\ApplicationException;

/**
 * Class ApplicationInstallRepository
 *
 * @package Hanaboso\PipesFramework\Application\Repository
 */
class ApplicationInstallRepository extends DocumentRepository
{

    /**
     * @param string $key
     * @param string $user
     *
     * @return ApplicationInstall
     * @throws ApplicationException
     */
    public function findAppByUserAndKey(string $key, string $user): ApplicationInstall
    {
        /** @var ApplicationInstall | null $app */
        $app = $this->createQueryBuilder()
            ->field('key')->equals($key)
            ->field('user')->equals($user)
            ->getQuery()->getSingleResult();

        if (!$app) {
            throw new ApplicationException(
                sprintf('Application [%s] was not found .', $key), ApplicationException::APP_WAS_NOT_FOUND
            );
        }

        return $app;
    }

}