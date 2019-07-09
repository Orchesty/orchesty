<?php declare(strict_types=1);

namespace Tests\Unit\Installer\Model;

use Hanaboso\PipesFramework\Installer\Model\DataTransport;
use Hanaboso\PipesFramework\Installer\Model\Installer;
use PHPUnit\Framework\TestCase;

/**
 * Class InstallerTest
 *
 * @package Tests\Unit\Installer\Model
 */
final class InstallerTest extends TestCase
{

    private const IMAGE = 'image';

    /**
     * @var array
     */
    protected $logsServices = [
        'logs' => [
            'elasticsearch',
            'logstash',
        ],
    ];

    /**
     *
     */
    public function testCreateArray(): void
    {

        $installer = new Installer();

        $dto = new DataTransport();

        $secondaryArray = $this->getSecondaryKeysSorted();
        $primaryArray   = $this->getPrimaryKeys();

        $secondaryArray = $this->createTestArray($dto->getLog(), $dto->getMetric(), $dto->getDatabase(),
            $secondaryArray);

        $array          = $installer->createArray($dto);
        $installerArray = $array[0];

        file_put_contents(sprintf('%s/docker-compose.yml',__DIR__),$installer->createInstaller($dto));
        foreach ($primaryArray as $value) {
            self::assertArrayHasKey($value, $installerArray, sprintf('%s is missing in prime keys', $value));
        }

        foreach ($secondaryArray as $value) {
            self::assertArrayHasKey($value, $installerArray['services'],
                sprintf('%s is missing in secondary keys', $value));
        }

        foreach ($installerArray['services'] as $key => $service) {
            self::assertArrayHasKey(self::IMAGE, $service, sprintf('%s is missing the %s key', $key, self::IMAGE));
        }

        self::assertEquals(count($installerArray), count($primaryArray),
            sprintf('The amount of prime keys (%d) is different from the required amount (%d)', count($installerArray),
                count($primaryArray)));

        self::assertEquals(count($installerArray['services']), count($secondaryArray),
            sprintf('The amount of secondary keys (%d) is different from the required amount (%d)',
                count($installerArray['services']), count($secondaryArray)));

    }

    /**
     * @return array
     */
    private function getPrimaryKeys(): array
    {

        return [
            'version',
            'services',
            'volumes',
            'networks'
        ];

    }

    /**
     * @return array
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
     * @param string $valueLog
     * @param string $valueMetric
     * @param bool   $valueDatabase
     * @param array  $array
     *
     * @return array
     */
    private function createTestArray(string $valueLog, string $valueMetric, bool $valueDatabase, array $array): array
    {

        $newArray = [];

        if ($valueLog !== 'logs' && ($valueLog === 'elasticsearch' or $valueLog === 'logstash')) {
            $newArray[] = $valueLog;

        }
        if ($valueMetric === Installer::INFLUXDB) {
            $newArray[] = $array['metrics'];

        } elseif ($valueMetric === Installer::MONGO) {

            $keys = [Installer::INFLUXDB, Installer::KAPACITOR];
            foreach ($keys as $key) {
                $index = array_search($key, $array['metrics']);
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
     * @param array $array
     *
     * @return array
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
