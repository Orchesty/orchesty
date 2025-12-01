<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditData;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditDataField;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\DatabaseTestCaseAbstract;

/**
 * Class AuditDataTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Document
 */
#[CoversClass(AuditData::class)]
final class AuditDataTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $auditData = (new AuditData())
            ->setUser('user')
            ->setEntity('entity')
            ->setFields(new ArrayCollection([(new AuditDataField())->setKey('key')->setValue('value')]));
        $this->persistAndFlush($auditData);

        self::assertSame('user', $auditData->getUser());
        self::assertSame('entity', $auditData->getEntity());
        self::assertEquals(
            [
                AuditData::ENTITY => 'entity',
                AuditData::FIELDS => [
                    [AuditDataField::KEY => 'key', AuditDataField::VALUE => 'value'],
                ],
                AuditData::ID     => $auditData->getId(),
                AuditData::USER   => 'user',
            ],
            $auditData->toArray(),
        );
    }

}
