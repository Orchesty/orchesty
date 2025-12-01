<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Controller\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller\AuditEntityController;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntityField;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\ControllerTestCaseAbstract;

/**
 * Class AuditEntityControllerTest
 *
 * @package PipesFrameworkEnterpriseTests\Controller\HbPFEnterpriseApiGatewayBundle\Controller
 */
#[CoversClass(AuditEntityController::class)]
final class AuditEntityControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createAuditEntity();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntityController/getAllRequest.json',
            ['id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOneAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntityController/getOneRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createAuditEntity()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntityController/createRequest.json',
            ['id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntityController/updateRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createAuditEntity()->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntityController/deleteRequest.json',
            ['id' => '123456789'],
            [':id' => $this->createAuditEntity()->getId()],
        );
    }

    /**
     * @return AuditEntity
     * @throws Exception
     */
    private function createAuditEntity(): AuditEntity
    {
        $entity = new AuditEntity();
        $entity
            ->setKey('key')
            ->setName('name')
            ->setFields(new ArrayCollection([(new AuditEntityField())->setKey('key')->setName('name')]));

        $this->pfd($entity);

        return $entity;
    }

}
