<?php declare(strict_types=1);

namespace PortalTests\Unit\Model\Installer;

use Exception;
use Hanaboso\Portal\Model\Installer\DataTransport;
use Hanaboso\Portal\Model\Installer\Exception\InstallerException;
use Hanaboso\Portal\Model\Installer\Installer;
use PortalTests\KernelTestCaseAbstract;
use ReflectionException;

/**
 * Class InstallerTest
 *
 * @package PortalTests\Unit\Model\Installer
 */
final class InstallerTest extends KernelTestCaseAbstract
{

    private const IMAGE = 'image';

    /**
     * @var mixed[]
     */
    protected $logsServices = [
        'logs' => [
            'elasticsearch',
            'logstash',
        ],
    ];

    /**
     * @var Installer
     */
    private Installer $installer;

    /**
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport::getLog
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport::getMetric
     * @covers \Hanaboso\Portal\Model\Installer\DataTransport::getDatabase
     */
    public function testCreateArray(): void
    {
        $dto = new DataTransport();

        $secondaryArray = $this->getSecondaryKeysSorted();
        $primaryArray   = $this->getPrimaryKeys();

        $secondaryArray = $this->createTestArray(
            $dto->getLog(),
            $dto->getMetric(),
            $dto->getDatabase(),
            $secondaryArray
        );

        $array          = $this->installer->createArray($dto);
        $installerArray = $array[0];

        foreach ($primaryArray as $value) {
            self::assertArrayHasKey($value, $installerArray, sprintf('%s is missing in prime keys', $value));
        }

        foreach ($secondaryArray as $value) {
            self::assertArrayHasKey(
                $value,
                $installerArray['services'],
                sprintf('%s is missing in secondary keys', $value)
            );
        }

        foreach ($installerArray['services'] as $key => $service) {
            self::assertArrayHasKey(self::IMAGE, $service, sprintf('%s is missing the %s key', $key, self::IMAGE));
        }

        self::assertEquals(
            count($installerArray),
            count($primaryArray),
            sprintf(
                'The amount of prime keys (%d) is different from the required amount (%d)',
                count($installerArray),
                count($primaryArray)
            )
        );

        self::assertEquals(
            count($installerArray['services']),
            count($secondaryArray),
            sprintf(
                'The amount of secondary keys (%d) is different from the required amount (%d)',
                count($installerArray['services']),
                count($secondaryArray)
            )
        );
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::unsetValue
     *
     * @throws Exception
     */
    public function testUnsetValue(): void
    {
        $result = $this->installer->unsetValue('1', ['data1' => '1', 'data2' => '2']);

        self::assertEquals(['data2' => '2'], $result);
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::unsetValue
     * @throws Exception
     */
    public function testUnsetValueErr(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('Value is wrong and couldnt be unset.');
        $this->installer->unsetValue('data1', ['data1' => '1', 'data2' => '2']);
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::generate
     * @throws InstallerException
     */
    public function testGenerate(): void
    {
        $installer = self::createPartialMock(Installer::class, ['createArray']);
        $installer->expects(self::any())->method('createArray')->willThrowException(new Exception());

        self::expectException(InstallerException::class);
        $installer->generate(new DataTransport());
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getCronApiServices
     *
     * @throws ReflectionException
     */
    public function testGetCronApiServices(): void
    {
        $result = $this->invokeMethod($this->installer, 'getCronApiServices');

        self::assertEquals(['cron-api' => ['image' => 'dkr.hanaboso.net/pipes/pipes/python-cron:master']], $result);
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getAllVolumes
     *
     * @throws ReflectionException
     */
    public function testGetAllVolumes(): void
    {
        $result = $this->invokeMethod($this->installer, 'getAllVolumes');

        self::assertEquals(['logs' => ['elasticsearch'], 'metrics' => ['influxdb']], $result);
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::getVolumes3
     *
     * @throws ReflectionException
     */
    public function testGetVolumes3(): void
    {
        $result = $this->invokeMethod($this->installer, 'getVolumes3');

        self::assertEquals(4, count($result['volumes']));
    }

    /**
     * @covers \Hanaboso\Portal\Model\Installer\Installer::unsetMetrics
     *
     * @throws ReflectionException
     */
    public function testUnsetMetrics(): void
    {
        $result = $this->invokeMethod($this->installer, 'unsetMetrics', [['influxdb' => 'influxdb', 'data' => 'data']]);

        self::assertEquals(['data' => 'data'], $result);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->installer = new Installer();
    }

    /**
     * @return mixed[]
     */
    private function getPrimaryKeys(): array
    {
        return [
            'version',
            'services',
            'volumes',
            'networks',
        ];
    }

    /**
     * @return mixed[]
     */
    private function getSecondaryKeysSorted(): array
    {
        return [
            'logs'      => [
                'elasticsearch',
                'logstash',
            ],
            'metrics'   => [
                'influxdb',
                'telegraf',
                'kapacitor',
            ],
            'core'      => [
                'batch',
                'batch-connector',
                'long-running-node',
                'status-service',
                'multi-probe',
                'multi-counter',
                'starting-point',
                'repeater',
                'monolith-api',
                'notification-sender-api',
                'notification-sender-consumer',
                'topology-api',
                'frontend',
                'stream',
            ],
            'databases' => [
                'mongo',
                'redis',
                'rabbitmq',
            ],
        ];
    }

    /**
     * @param string  $valueLog
     * @param string  $valueMetric
     * @param bool    $valueDatabase
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    private function createTestArray(string $valueLog, string $valueMetric, bool $valueDatabase, array $array): array
    {
        $newArray = [];

        if ($valueLog !== 'logs' && ($valueLog === 'elasticsearch' or $valueLog === 'logstash')) {
            $newArray[] = $valueLog;

        }
        if ($valueMetric === Installer::INFLUXDB) {
            $newArray[] = $array['metrics'];

        } else if ($valueMetric === Installer::MONGO) {

            $keys = [Installer::INFLUXDB, Installer::KAPACITOR];
            foreach ($keys as $key) {
                $index = array_search($key, $array['metrics'], TRUE);
                if ($index !== FALSE) {
                    unset($array['metrics'][$index]);
                }
            }
            $newArray[] = $array['metrics'];
        }

        if ($valueDatabase === TRUE) {
            $newArray[] = $array['databases'];
        }

        $newArray[] = $array['core'];

        $newArray = $this->createFinalTestArray($newArray);

        return $newArray;
    }

    /**
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    private function createFinalTestArray(array $array): array
    {

        $merge = [];
        foreach ($array as $key => $key) {

            if (is_string($array[$key])) {
                $array[$key] = [$array[$key]];
            }
            $merge = array_merge($merge, $array[$key]);
        }

        return $merge;
    }

}
