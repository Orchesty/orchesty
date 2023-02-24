<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Application\Repository;

use Exception;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationInstallRepositoryTest
 *
 * @package PipesFrameworkTests\Integration\Application\Repository
 */
final class ApplicationInstallRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers  \Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository::getApplicationsCount
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setUser
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setKey
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setExpires
     *
     * @throws Exception
     */
    public function testGetApplicationsBasicData(): void
    {
        $this->createApps();
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertEquals(
            $this->getBasicData(),
            $appInstallRepository->getApplicationsCount(),
        );
    }

    /**
     * @covers  \Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository::getApplicationsCount
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setUser
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setKey
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setExpires
     *
     * @throws Exception
     */
    public function testGetApplicationsCount(): void
    {
        $this->createApps();
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertEquals(
            4,
            $appInstallRepository->getInstalledApplicationsCount(),
        );
    }


    /**
     * @covers  \Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository::getApplicationsCountDetails
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setUser
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setKey
     * @covers  \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setExpires
     *
     * @throws Exception
     */
    public function testGetApplicationsUsers(): void
    {
        $this->createApps();
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertEquals(
            $this->getApplicationsUsers('mailchimp'),
            $appInstallRepository->getApplicationsCountDetails('mailchimp'),
        );
        self::assertEquals(
            $this->getApplicationsUsers('hubspot'),
            $appInstallRepository->getApplicationsCountDetails('hubspot'),
        );
        self::assertEquals(
            $this->getApplicationsUsers('shipstation'),
            $appInstallRepository->getApplicationsCountDetails('shipstation'),
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository::findUserApp
     *
     * @throws Exception
     */
    public function testFindUserAppErr(): void
    {
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::expectException(Exception::class);
        $appInstallRepository->findUserApp('user', 'key');
    }

    /**
     * @return mixed[]
     */
    private function getBasicData(): array
    {
        return [
            0 => [
                '_id'   => 'hubspot',
                'value' => [
                    'total_sum'      => 2,
                    'non_expire_sum' => 1,
                ],
            ],
            1 => [
                '_id'   => 'mailchimp',
                'value' => [
                    'total_sum'      => 1,
                    'non_expire_sum' => 1,
                ],
            ],
            2 => [
                '_id'   => 'shipstation',
                'value' => [
                    'total_sum'      => 1,
                    'non_expire_sum' => 1,
                ],
            ],
        ];
    }

    /**
     * @param string $key
     *
     * @return mixed[]
     */
    private function getApplicationsUsers(string $key): array
    {
        $array = [
            'hubspot'     => [
                '_id'   => 'hubspot',
                'value' => [
                    'users' => [
                        0 => [
                            'active' => FALSE,
                            'name'   => 'user2',
                        ],
                        1 => [
                            'active' => TRUE,
                            'name'   => 'user3',
                        ],
                    ],
                ],
            ],
            'mailchimp'   => [
                '_id'   => 'mailchimp',
                'value' => [
                    'users' => [
                        0 => [
                            'active' => TRUE,
                            'name'   => 'user2',
                        ],
                    ],
                ],
            ],
            'shipstation' => [
                '_id'   => 'shipstation',
                'value' => [
                    'users' => [
                        0 => [
                            'active' => TRUE,
                            'name'   => 'user2',
                        ],
                    ],
                ],
            ],
        ];

        return [$array[$key]];
    }

    /**
     * @throws Exception
     */
    private function createApps(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setKey('hubspot');
        $applicationInstall->setUser('user2');
        $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime('- 10 Days'));

        $applicationInstall2 = new ApplicationInstall();
        $applicationInstall2->setKey('mailchimp');
        $applicationInstall2->setUser('user2');

        $applicationInstall3 = new ApplicationInstall();
        $applicationInstall3->setKey('hubspot');
        $applicationInstall3->setUser('user3');
        $applicationInstall3->setExpires(DateTimeUtils::getUtcDateTime('+ 1 Day'));

        $applicationInstall4 = new ApplicationInstall();
        $applicationInstall4->setKey('shipstation');
        $applicationInstall4->setUser('user2');
        $applicationInstall4->setExpires(DateTimeUtils::getUtcDateTime('+ 1 Day'));

        $this->dm->persist($applicationInstall);
        $this->dm->persist($applicationInstall2);
        $this->dm->persist($applicationInstall3);
        $this->dm->persist($applicationInstall4);
        $this->dm->flush();
        $this->dm->clear();
    }

}
