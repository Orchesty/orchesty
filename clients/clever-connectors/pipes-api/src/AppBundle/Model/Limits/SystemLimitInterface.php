<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Limits;

use CleverConnectors\AppBundle\Document\SystemInstall;

/**
 * Interface SystemLimitInterface
 *
 * @package CleverConnectors\AppBundle\Model\Limits
 */
interface SystemLimitInterface
{

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall;

}