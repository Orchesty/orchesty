<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestNotificationConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;

/**
 * Class CMTestNotificationConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\TestNotificationConnector
 */
class CMTestNotificationConnector implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = $dto->getData() ? json_decode($dto->getData(), TRUE) : NULL;

        if ($data) {
            $systemInstall = new SystemInstall();
            $systemInstall->setUser('user-guid')
                ->setToken('token');

            foreach ($data as $key => $value) {
                if ($value) {

                    $this->logger->info($key, self::getMessage($key, new NullSystem(), $systemInstall));
                }
            }
        }

        return $dto;
    }

}