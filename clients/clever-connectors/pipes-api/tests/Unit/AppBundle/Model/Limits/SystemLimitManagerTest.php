<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemTopologyRunner;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
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
     * @var SystemLoader|MockObject
     */
    private $systemLoader;

    /**
     *
     * @var DocumentManager|MockObject
     */
    private $dm;

    /**
     * @var SystemTopologyRunner|MockObject
     */
    private $systemTopologyRunner;

    /**
     * @var SystemInstallRepository
     */
    private $systemInstallRepository;

    /**
     *
     */
    public function setUp(): void
    {
        $this->systemLoader            = $this->createMock(SystemLoader::class);
        $this->systemTopologyRunner    = $this->createMock(SystemTopologyRunner::class);
        $this->systemInstallRepository = $this->createMock(SystemInstallRepository::class);

        $this->dm = $this->createMock(DocumentManager::class);
        $this->dm->method('getRepository')->willReturn($this->systemInstallRepository);
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

        $manager = new SystemLimitManager($this->systemLoader, $this->systemTopologyRunner, $this->dm, 86400);
        $manager->addSystemLimitToRequestHeaders($headers, $system, $systemInstall);

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

        /** @var SystemInterface|MockObject $system */
        $system = $this->createMock(AuthorizationInterface::class);
        $system->method('getLimit')->willReturn($systemLimitDto);

        $this->systemLoader->method('getSystem')->willReturn($system);

        $message = new SuccessMessage(1);

        $manager = new SystemLimitManager($this->systemLoader, $this->systemTopologyRunner, $this->dm, 86400);
        $manager->addSystemLimitToSuccessMessage($message);

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