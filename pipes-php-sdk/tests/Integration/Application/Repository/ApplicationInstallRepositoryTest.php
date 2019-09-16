<?php declare(strict_types=1);

namespace Tests\Integration\Application\Repository;

use Exception;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationInstallRepositoryTest
 *
 * @package Tests\Integration\Controller\Application\Repository
 */
final class ApplicationInstallRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetApplicationsBasicData(): void
    {
        $this->createApps();
        /** @var ApplicationInstallRepository $appInstallRepository */
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertEquals(
            $appInstallRepository->getApplicationsCount(),
            $this->getBasicData()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationsUsers(): void
    {
        $this->createApps();
        /** @var ApplicationInstallRepository $appInstallRepository */
        $appInstallRepository = $this->dm->getRepository(ApplicationInstall::class);

        self::assertEquals(
            $appInstallRepository->getApplicationsCountDetails('mailchimp'),
            $this->getApplicationsUsers('mailchimp')
        );
        self::assertEquals(
            $appInstallRepository->getApplicationsCountDetails('hubspot'),
            $this->getApplicationsUsers('hubspot')
        );
        self::assertEquals(
            $appInstallRepository->getApplicationsCountDetails('shipstation'),
            $this->getApplicationsUsers('shipstation')
        );
    }

    /**
     * @throws Exception
     */
    private function createApps(): void
    {
        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setKey('hubspot');
        $applicationInstall->setUser('user2');

        $applicationInstall3 = new ApplicationInstall();
        $applicationInstall3->setKey('hubspot');
        $applicationInstall3->setUser('user3');
        $applicationInstall3->setExpires(DateTimeUtils::getUtcDateTime());

        $applicationInstall2 = new ApplicationInstall();
        $applicationInstall2->setKey('mailchimp');
        $applicationInstall2->setUser('user2');

        $applicationInstall4 = new ApplicationInstall();
        $applicationInstall4->setKey('shipstation');
        $applicationInstall4->setUser('user2');
        $applicationInstall4->setExpires(DateTimeUtils::getUtcDateTime());

        $this->dm->persist($applicationInstall);
        $this->dm->persist($applicationInstall2);
        $this->dm->persist($applicationInstall3);
        $this->dm->persist($applicationInstall4);
        $this->dm->flush();
    }

    /**
     * ----------------------------------- HELPERS --------------------------------------
     */

    /**
     * @return array
     */
    private function getBasicData(): array
    {
        return [
            0 => [
                '_id'   => 'hubspot',
                'value' => [
                    'total_sum'      => 2.0,
                    'non_expire_sum' => 1.0,
                ],
            ],
            1 => [
                '_id'   => 'mailchimp',
                'value' => [
                    'total_sum'      => 1.0,
                    'non_expire_sum' => 0.0,
                ],
            ],
            2 => [
                '_id'   => 'shipstation',
                'value' => [
                    'total_sum'      => 1.0,
                    'non_expire_sum' => 1.0,
                ],
            ],
        ];
    }

    /**
     * @param string $key
     *
     * @return array
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
                            'active' => FALSE,
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

}
