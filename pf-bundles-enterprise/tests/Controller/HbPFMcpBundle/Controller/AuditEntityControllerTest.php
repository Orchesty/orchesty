<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Controller\HbPFMcpBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Controller\AuditEntityController;
use Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler\AuditEntityHandler;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntity;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Document\AuditEntityField;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\AuditEntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkEnterpriseTests\ControllerTestCaseAbstract;

/**
 * Class AuditEntityControllerTest
 *
 * @package PipesFrameworkEnterpriseTests\Controller\HbPFMcpBundle\Controller
 */
#[CoversClass(AuditEntityController::class)]
#[CoversClass(AuditEntityHandler::class)]
#[CoversClass(AuditEntityManager::class)]
final class AuditEntityControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createAuditEntity('keyOne', 'nameOne');
        $this->createAuditEntity('keyTwo', 'nameTwo');

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntity/getAllRequest.json',
            ['id' => '5e32a7a41ffeab2445696983'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOne(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntity/getOneRequest.json',
            ['id' => '5e32a9b8a1b2a70fef6fa273'],
            [':id' => $this->createAuditEntity('key', 'name')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetOneNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/AuditEntity/getOneNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testCreate(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntity/createRequest.json',
            ['id' => '5e32aab74c2bd32924205303'],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateErr(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/AuditEntity/createErrRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntity/updateRequest.json',
            ['id' => '5e32ac41505d6e1b5047eb43'],
            [':id' => $this->createAuditEntity('key', 'name')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/AuditEntity/updateNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/AuditEntity/deleteRequest.json',
            ['id' => '5e32ae5cb04e0b3566176113'],
            [':id' => $this->createAuditEntity('key', 'name')->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteErr(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/AuditEntity/deleteErrRequest.json');
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

        $this->pfd($entity);

        return $entity;
    }

}
