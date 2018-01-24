<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Class SystemLimitManagerTest
 *
 * @package Tests\Unit\AppBundle\Model\Limits
 */
class SystemLimitManagerTest extends TestCase
{

    /**
     * @var StartingPoint|MockObject
     */
    private $startingPoint;

    /**
     *
     * @var DocumentManager|MockObject
     */
    private $dm;

    /**
     * @var TopologyRepository|MockObject
     */
    private $topologyRepository;

    /**
     * @var NodeRepository|MockObject
     */
    private $nodeRepository;

    /**
     *
     */
    public function setUp(): void
    {
        $this->startingPoint = $this->createMock(StartingPoint::class);
        $this->topologyRepository = $this->createMock(TopologyRepository::class);
        $this->nodeRepository = $this->createMock(NodeRepository::class);

        $this->dm = $this->createMock(DocumentManager::class);
        $this->dm->expects($this->at(0))->method('getRepository')->willReturn($this->topologyRepository);
        $this->dm->expects($this->at(1))->method('getRepository')->willReturn($this->nodeRepository);
    }

    /**
     *
     */
    public function testAddSystemLimitToRequestHeaders(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user_id')
            ->setSystem('system_key');

        $date           = new DateTime('2018-01-20 12:00:00');
        $systemLimitDto = new SystemLimitDto($systemInstall, SystemLimitDto::LIMIT_FOR_USER, 66, 22, $date);

        /** @var SystemInterface|MockObject $system */
        $system = $this->createMock(SystemInterface::class);
        $system->method('getLimit')->willReturn($systemLimitDto);

        $headers = new HeaderBag();

        $manager = new SystemLimitManager($this->startingPoint, $this->dm);
        $manager->addSystemLimitToRequestHeaders($system, $systemInstall, $headers);

        $expected = [
            'pf-limit-key'         => ['user_id-system_key'],
            'pf-limit-last-update' => [$date->getTimestamp()],
            'pf-limit-time'        => [66],
            'pf-limit-value'       => [22],
        ];

        $this->assertEquals($expected, $headers->all());
    }

    /**
     *
     */
    public function testAddSystemLimitToSuccessMessage(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user_id')
            ->setSystem('system_key');

        $date           = new DateTime('2018-01-20 12:00:00');
        $systemLimitDto = new SystemLimitDto($systemInstall, SystemLimitDto::LIMIT_FOR_USER, 66, 22, $date);

        /** @var SystemInterface|MockObject $systemLimit */
        $systemLimit = $this->createMock(SystemInterface::class);
        $systemLimit->method('getLimit')->willReturn($systemLimitDto);

        $message = new SuccessMessage(1);

        $manager = new SystemLimitManager($this->startingPoint, $this->dm);
        $manager->addSystemLimitToSuccessMessage($systemLimit, $systemInstall, $message);

        $expected = [
            'pf-limit-key'         => 'user_id-system_key',
            'pf-limit-last-update' => strval($date->getTimestamp()),
            'pf-limit-time'        => '66',
            'pf-limit-value'       => '22',
        ];

        $result = $message->getHeaders();
        unset($result['pf-result-code']);

        $this->assertEquals($expected, $result);
    }

}