<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/11/17
 * Time: 2:01 PM
 */

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Traits\StaticTrait;
use CleverConnectors\AppBundle\Utils\Dto\Times;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class CronUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class CronUtils
{

    use StaticTrait;

    /**
     * @param ProcessDto $dto
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public static function parseData(ProcessDto $dto): array
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data) || empty($data)) {
            throw new CleverConnectorsException(
                'Empty data or bad format.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $data;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    public static function getSystemInstall(ProcessDto $dto): SystemInstall
    {
        $data = self::parseData($dto);

        if (!array_key_exists('system_install', $data)) {
            throw new CleverConnectorsException(
                'Missing [system_install] in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return SystemInstall::from($data['system_install']);
    }

    /**
     * @param LastSync $lastSync
     *
     * @return Times
     */
    public static function getTimes(LastSync $lastSync): Times
    {
        $start = $lastSync ? $lastSync->getTimestamp() : NULL;
        $end   = new DateTime('now');

        return new Times($start, $end);
    }

}