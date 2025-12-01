<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntityField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\AuditEntityManager;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class AuditEntityManagerTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Model
 */
#[CoversClass(AuditEntityManager::class)]
final class AuditEntityManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var AuditEntityManager
     */
    private AuditEntityManager $manager;

    /**
     * @var ObjectRepository<AuditEntity>&AuditEntityRepository
     */
    private AuditEntityRepository $auditEntityRepository;

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createAuditEntity('keyOne', 'nameOne');
        $this->createAuditEntity('keyTwo', 'nameTwo');

        $data = $this->manager->getAll();

        self::assertCount(2, $data);
        self::assertSame('keyOne', $data[0]->getKey());
        self::assertSame('nameOne', $data[0]->getName());
        self::assertSame('keyTwo', $data[1]->getKey());
        self::assertSame('nameTwo', $data[1]->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $data = $this->manager->getOne($this->createAuditEntity('key', 'name')->getId());

        self::assertSame('key', $data->getKey());
        self::assertSame('name', $data->getName());

        self::expectException(DocumentNotFoundException::class);
        $this->manager->getOne('Unknown');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $data = $this->manager->create([
            AuditEntity::FIELDS => [
                [AuditEntityField::KEY => 'key', AuditEntityField::NAME => 'name'],
            ],
            AuditEntity::KEY => 'key',
            AuditEntity::NAME => 'name',
        ]);

        $this->dm->clear();
        self::assertCount(1, $this->auditEntityRepository->findAll());

        self::assertSame('key', $data->getKey());
        self::assertSame('name', $data->getName());
        self::assertCount(1, $data->getFields());
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $data = $this->manager->update(
            $this->createAuditEntity('keyOne', 'nameOne'),
            [
                AuditEntity::FIELDS => [
                    [AuditEntityField::KEY => 'keyTwo', AuditEntityField::NAME => 'nameTwo'],
                ],
                AuditEntity::KEY => 'keyTwo',
                AuditEntity::NAME => 'nameTwo',
            ],
        );

        $this->dm->clear();
        self::assertCount(1, $this->auditEntityRepository->findAll());

        self::assertSame('keyTwo', $data->getKey());
        self::assertSame('nameTwo', $data->getName());
        self::assertCount(1, $data->getFields());
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $data = $this->manager->delete($this->createAuditEntity('key', 'name'));

        $this->dm->clear();
        self::assertCount(0, $this->auditEntityRepository->findAll());

        self::assertSame('key', $data->getKey());
        self::assertSame('name', $data->getName());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager               = self::getContainer()->get('hbpf.mcp.manager.audit_entity');
        $this->auditEntityRepository = $this->dm->getRepository(AuditEntity::class);
    }

    /**
     * @param string $key
     * @param string $name
     *
     * @return AuditEntity
     * @throws Exception
     */
    private function createAuditEntity(string $key, string $name): AuditEntity
    {
        $entity = (new AuditEntity())
            ->setKey($key)
            ->setName($name)
            ->setFields(new ArrayCollection([(new AuditEntityField())->setKey('key')->setName('name')]));

        $this->persistAndFlush($entity);

        return $entity;
    }

}
