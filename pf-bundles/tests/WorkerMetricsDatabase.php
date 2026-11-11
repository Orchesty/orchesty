<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use LogicException;

/**
 * Class WorkerMetricsDatabase
 *
 * @package PipesFrameworkTests
 */
final class WorkerMetricsDatabase
{

    // phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable

    private const string METRICS_DB = 'METRICS_DB';
    private const string TEST_TOKEN = 'TEST_TOKEN';

    /**
     * @var string|null
     */
    private static ?string $original = NULL;

    /**
     *
     */
    public static function configure(): void
    {
        $database = self::getDatabase();

        if ($database === NULL) {
            return;
        }

        $_SERVER[self::METRICS_DB] = $database;
        $_ENV[self::METRICS_DB]    = $database;
        putenv(sprintf('%s=%s', self::METRICS_DB, $database));
    }

    /**
     * @param DocumentManager $metricsDm
     */
    public static function drop(DocumentManager $metricsDm): void
    {
        $database = self::getDatabase();

        if ($database === NULL) {
            return;
        }

        $current = $metricsDm->getConfiguration()->getDefaultDB();

        if ($current !== $database) {
            throw new LogicException(
                sprintf(
                    'Metrics database is "%s" instead of "%s", call %s::configure() before the kernel boots.',
                    $current,
                    $database,
                    self::class,
                ),
            );
        }

        $metricsDm->getClient()->dropDatabase($database);
    }

    /**
     * @return string|null
     */
    private static function getDatabase(): ?string
    {
        $token = (string) getenv(self::TEST_TOKEN);

        if (self::$original === NULL) {
            $original       = $_ENV[self::METRICS_DB] ?? $_SERVER[self::METRICS_DB] ?? getenv(self::METRICS_DB);
            self::$original = is_string($original) ? $original : '';
        }

        if ($token === '' || self::$original === '') {
            return NULL;
        }

        return sprintf('%s%s', self::$original, $token);
    }

}
