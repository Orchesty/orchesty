<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CustomNode\TokenRefresher;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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

        $dto = new ProcessDto();
        $dto->setData(json_encode($systemInstall));

        $node   = new TokenRefresher($this->dm, $this->container->get('systems.loader'));
        $result = $node->process($dto);

        self::assertEquals($result, $dto);

        /** @var SystemInstall $result */
        $result = $this->dm->getRepository(SystemInstall::class)->find($systemInstall->getId());

        self::assertEquals($timestamp, $result->getExpires()->getTimestamp() + 3600);
    }

}