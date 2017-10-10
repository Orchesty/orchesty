<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\SystemInstall;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use JMS\Serializer\SerializerBuilder;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TokenRefresherTest
 *
 * @package Tests\Integration\AppBundle\Model\CustomNode
 */
final class TokenRefresherTest extends DatabaseTestCaseAbstract
{

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

        $serializer = SerializerBuilder::create()->build();

        $dto = new ProcessDto();
        $dto->setData($serializer->serialize($systemInstall, 'json'));

        $node   = $this->container->get('hbpf.custom_node.token-refresher');
        $result = $node->process($dto);

        self::assertEquals($result, $dto);

        $this->dm->clear(SystemInstall::class);

        /** @var SystemInstall $result */
        $result = $this->dm->getRepository(SystemInstall::class)->find($systemInstall->getId());

        self::assertEquals($timestamp + 3600, $result->getExpires()->getTimestamp());
    }

}