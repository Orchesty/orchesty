<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp;

use Exception;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class MailchimpApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '6748****7235';

    /**
     * @throws Exception
     */
    public function testAutorize(): void
    {
        $this->mockRedirect(MailchimpApplication::MAILCHIMP_URL, self::CLIENT_ID);
        $app                = self::$container->get('hbpf.application.mailchimp');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'token123',
            self::CLIENT_ID
        );
        $this->pf($applicationInstall);
        $app->authorize($applicationInstall);
    }

    /**
     * @throws Exception
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseDatacenter.json',
                    []
                ),
            ]
        );

        $application        = self::$container->get('hbpf.application.mailchimp');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $application->getKey(),
            'user',
            'fa830d8d4308*****c307906e83de659'
        );
        $applicationInstall->setSettings(
            [
                ApplicationAbstract::FORM          => [MailchimpApplication::AUDIENCE_ID => '2a8******8'],
                MailchimpApplication::API_KEYPOINT => $application->getApiEndpoint($applicationInstall),
            ]
        );

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $request = $application->getWebhookSubscribeRequestDto(
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
        self::assertEquals(
            $request->getUriString(),
            sprintf(
                '%s/3.0/lists/%s/webhooks',
                $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][MailchimpApplication::AUDIENCE_ID]
            )
        );
    }

}
