<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class PluginUnsubscribeSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginUnsubscribeSubscriberConnector extends PluginSubscriberConnectorAbstract
{

    protected const SUB_URL = 'clever_connector/subscriber/%s/unsubscribe';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-unsubscribe-contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     */
    protected function getBody(ProcessDto $dto): string
    {
        return '';
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
            $systemInstall->getSettings()[SystemInstall::SYSTEM_URL],
            sprintf(static::SUB_URL, $this->getIdFromDto($dto))
        ));
    }

}