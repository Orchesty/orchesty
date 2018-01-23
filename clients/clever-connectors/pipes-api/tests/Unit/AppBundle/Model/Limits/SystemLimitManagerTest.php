<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitInterface;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
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
     */
    public function setUp(): void
    {
        $this->systemLoader = $this->createMock(SystemLoader::class);
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

        $date           = new DateTime('2018-01-23 12:00:00');
        $systemLimitDto = new SystemLimitDto($systemInstall, SystemLimitDto::LIMIT_FOR_USER, 66, 22, $date);

        /** @var SystemLimitInterface|MockObject $systemLimit */
        $systemLimit = $this->createMock(SystemLimitInterface::class);
        $systemLimit->method('getLimit')->willReturn($systemLimitDto);

        $headers = new HeaderBag();

        $manager = new SystemLimitManager($this->systemLoader);
        $manager->addSystemLimitToRequestHeaders($systemLimit, $systemInstall, $headers);

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

        $date           = new DateTime('2018-01-23 12:00:00');
        $systemLimitDto = new SystemLimitDto($systemInstall, SystemLimitDto::LIMIT_FOR_USER, 66, 22, $date);

        /** @var SystemLimitInterface|MockObject $systemLimit */
        $systemLimit = $this->createMock(SystemLimitInterface::class);
        $systemLimit->method('getLimit')->willReturn($systemLimitDto);

        $message = new SuccessMessage(1);

        $manager = new SystemLimitManager($this->systemLoader);
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