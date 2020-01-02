<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\HbPFAppStore\Document\Synchronization;
use Hanaboso\HbPFAppStore\Repository\SynchronizationRepository;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use LogicException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SynchronizationRepositoryTest
 *
 * @package Tests\Integration\Repository
 */
final class SynchronizationRepositoryTest extends DatabaseTestCaseAbstract
{

    private const KEY  = 'key';
    private const USER = 'user';

    /**
     * @var SynchronizationRepository
     */
    private $repository;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->dm->getRepository(Synchronization::class);
    }

    /**
     * @throws Exception
     */
    public function testGet(): void
    {
        $this->createSynchronization();
        /** @var Synchronization $synchronization */
        $synchronization = $this->repository->get($this->createApplication());

        self::assertEquals($synchronization->getKey(), self::KEY);
        self::assertEquals($synchronization->getUser(), self::USER);
    }

    /**
     * @throws Exception
     */
    public function testGetNotFound(): void
    {
        self::assertNull($this->repository->get($this->createApplication()));
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->createSynchronization();

        $synchronization = $this->repository->update(
            $this->createApplication(),
            [self::USER => self::USER],
            ['data.key' => 'Value']
        );

        self::assertEquals('Value', $synchronization->getData()['key']);
    }

    /**
     * @throws Exception
     */
    public function testUpdateBadException(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Constant Hanaboso\HbPFAppStore\Document\Synchronization::Unknown not found!');

        $this->createSynchronization();
        $this->repository->update($this->createApplication(), [self::USER => self::USER], ['Unknown' => 'Unknown']);
    }

    /**
     * @throws Exception
     */
    public function testUpdateNotFoundException(): void
    {
        self::expectException(DocumentNotFoundException::class);
        self::expectExceptionMessage("Synchronization document with key 'key' and user 'user' not found!");

        $this->repository->update($this->createApplication());
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplication(): ApplicationInstall
    {
        return (new ApplicationInstall())->setKey(self::KEY)->setUser(self::USER);
    }

    /**
     * @return Synchronization
     * @throws Exception
     */
    private function createSynchronization(): Synchronization
    {
        $synchronization = ((new Synchronization())->setKey(self::KEY)->setUser(self::USER));

        $this->dm->persist($synchronization);
        $this->dm->flush();
        $this->dm->clear();

        return $synchronization;
    }

}