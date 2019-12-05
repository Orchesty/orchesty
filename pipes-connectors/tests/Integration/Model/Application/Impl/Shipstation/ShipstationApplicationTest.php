<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shipstation;

use Exception;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class ShipstationApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shipstation
 */
final class ShipstationApplicationTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '79620d3760d**********18f8a35dec8';
    public const API_SECRET = '9cabe470**********751904f45f80e2';

    public const token = 'ODkxOWJiMjEzYWFiNDdiNDhmN2JiMDdmMWNlMWUyNWM6OTk**********jE1NDQ5OWEzODIyMWQyMjM3NTQyNGI=';


    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $application        = self::$container->get('hbpf.application.shipstation');
        $applicationInstall = DataProvider::getBasicAppInstall(
            $application->getKey(),
            self::API_KEY,
            self::API_SECRET
        );

        $applicationInstall = $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        BasicApplicationAbstract::USER     => self::API_KEY,
                        BasicApplicationAbstract::PASSWORD => self::API_SECRET,
                    ],
            ]
        );
        $subscription       = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $requestSub = $application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf(
                '%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25))
            )
        );

        $requestUn = $application->getWebhookUnsubscribeRequestDto(
            $applicationInstall,
            '358'
        );

        self::assertEquals(
            $requestSub->getUriString(),
            'https://ssapi.shipstation.com/webhooks/subscribe'
        );

        self::assertEquals(
            $requestUn->getUriString(),
            'https://ssapi.shipstation.com/webhooks/358'
        );

    }

}
