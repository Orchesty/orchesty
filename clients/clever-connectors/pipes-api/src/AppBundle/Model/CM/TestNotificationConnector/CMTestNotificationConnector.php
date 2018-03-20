<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestNotificationConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;

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
                    $this->logger->error($key, self::getMessage($key, new BigcommerceSystem(), $systemInstall));
                }
            }
        }

        return $dto;
    }

}