<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntityField;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class AuditEntityTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Document
 */
#[CoversClass(AuditEntity::class)]
final class AuditEntityTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $auditEntity = (new AuditEntity())
            ->setKey('key')
            ->setName('name')
            ->setFields(new ArrayCollection([(new AuditEntityField())->setKey('key')->setName('name')]));
        $this->persistAndFlush($auditEntity);

        self::assertSame('key', $auditEntity->getKey());
        self::assertSame('name', $auditEntity->getName());
        self::assertEquals(
            [
                AuditEntity::FIELDS => [
                    [AuditEntityField::KEY => 'key', AuditEntityField::NAME => 'name'],
                ],
                AuditEntity::ID     => $auditEntity->getId(),
                AuditEntity::KEY    => 'key',
                AuditEntity::NAME   => 'name',
            ],
            $auditEntity->toArray(),
        );
    }

}
