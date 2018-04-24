<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CustomNode\TokenRefresher;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use MongoDB\BSON\ObjectID;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TokenRefresherTest
 *
 * @package Tests\Integration\AppBundle\Model\CustomNode
 */
final class TokenRefresherTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TokenRefresher
     */
    private $node;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $repo;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->node = $this->container->get('hbpf.custom_node.token-refresher');
        $this->repo = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     *
     */
    public function testProcess(): void
    {
        $timestamp = time();
        $datetime  = new DateTime();
        $datetime->setTimestamp($timestamp);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setExpires($datetime);

        $this->persistAndFlush($systemInstall);

        $system = $this
            ->dm->getDocumentCollection(SystemInstall::class)
            ->findOne(['_id' => new ObjectID($systemInstall->getId())]);

        $dto = new ProcessDto();
        $dto->setData(json_encode($system));

        $result = $this->node->process($dto);

        self::assertEquals($result, $dto);

        $this->dm->clear(SystemInstall::class);

        $result = $this->repo->find($systemInstall->getId());

        self::assertEquals($timestamp + 3600, $result->getExpires()->getTimestamp());
    }

    /**
     *
     */
    public function testProcessExpiresIsNull(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setExpires(NULL);

        $this->persistAndFlush($systemInstall);

        $system = $this
            ->dm->getDocumentCollection(SystemInstall::class)
            ->findOne(['_id' => new ObjectID($systemInstall->getId())]);

        $dto = new ProcessDto();
        $dto->setData(json_encode($system));

        $result = $this->node->process($dto);

        self::assertEquals($result, $dto);

        $this->dm->clear(SystemInstall::class);

        $result = $this->repo->find($systemInstall->getId());

        self::assertEquals(NULL, $result->getExpires());
    }

}