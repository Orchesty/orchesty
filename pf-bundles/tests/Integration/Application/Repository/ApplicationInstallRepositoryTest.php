<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Application\Repository;

use Exception;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationInstallRepositoryTest
 *
 * @package PipesFrameworkTests\Integration\Application\Repository
 */
#[CoversClass(ApplicationInstall::class)]
#[CoversClass(ApplicationInstallRepository::class)]
final class ApplicationInstallRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
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
     * @throws Exception
     */
    public function testGetApplicationsCount(): void
    {
        $this->createApps();
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertSame(
            4,
            $appInstallRepository->getInstalledApplicationsCount(),
        );
    }


    /**
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
     * @throws Exception
     */
    public function testFindUserAppErr(): void
    {
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::expectException(Exception::class);
        $appInstallRepository->findUserApp('user', 'key', 'sdk');
    }

    /**
     * @return mixed[]
     */
    private function getBasicData(): array
    {
        return [
            0 => [
                'value' => [
                    'non_expire_sum' => 1,
                    'total_sum'      => 2,
                ],
                '_id'   => 'hubspot',
            ],
            1 => [
                'value' => [
                    'non_expire_sum' => 1,
                    'total_sum'      => 1,
                ],
                '_id'   => 'mailchimp',
            ],
            2 => [
                'value' => [
                    'non_expire_sum' => 1,
                    'total_sum'      => 1,
                ],
                '_id'   => 'shipstation',
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
                '_id'   => 'hubspot',
            ],
            'mailchimp'   => [
                'value' => [
                    'users' => [
                        0 => [
                            'active' => TRUE,
                            'name'   => 'user2',
                        ],
                    ],
                ],
                '_id'   => 'mailchimp',
            ],
            'shipstation' => [
                'value' => [
                    'users' => [
                        0 => [
                            'active' => TRUE,
                            'name'   => 'user2',
                        ],
                    ],
                ],
                '_id'   => 'shipstation',
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
        $applicationInstall->setSdk('sdk');
        $applicationInstall->setUser('user2');
        $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime('- 10 Days'));

        $applicationInstall2 = new ApplicationInstall();
        $applicationInstall2->setKey('mailchimp');
        $applicationInstall2->setSdk('sdk');
        $applicationInstall2->setUser('user2');

        $applicationInstall3 = new ApplicationInstall();
        $applicationInstall3->setKey('hubspot');
        $applicationInstall3->setSdk('sdk');
        $applicationInstall3->setUser('user3');
        $applicationInstall3->setExpires(DateTimeUtils::getUtcDateTime('+ 1 Day'));

        $applicationInstall4 = new ApplicationInstall();
        $applicationInstall4->setKey('shipstation');
        $applicationInstall4->setSdk('sdk');
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
