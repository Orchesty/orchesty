<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class PluginCreateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginCreateSubscriberConnector extends PluginSubscriberConnectorAbstract
{

    protected const SUB_URL = 'clever_connector/subscriber/create';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-create-contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     */
    protected function getBody(ProcessDto $dto): string
    {
        return $dto->getData();
    }

    /**
     * @param SystemInstall $systemInstall
     * @param ProcessDto    $dto
     *
     * @return Uri
     */
    protected function getUri(SystemInstall $systemInstall, ProcessDto $dto): Uri
    {
        return new Uri(sprintf('%s/%s',
            rtrim($systemInstall->getSettings()[SystemInstall::SYSTEM_URL], '/'),
            ltrim(self::SUB_URL, '/')
        ));
    }

}