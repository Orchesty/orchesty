<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp;

use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFApplication\Model\Webhook\WebhookSubscription;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class MailchimpApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp
 */
final class MailchimpApplicationTest extends DatabaseTestCaseAbstract
{

    private const CLIENT_ID = '105786712126';

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
            self::CLIENT_ID,
            );
        $this->pf($applicationInstall);
        $app->authorize($applicationInstall);
    }

    /**
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     */
    public function testWebhookSubscribeRequestDto(): void
    {
        $application        = self::$container->get('hbpf.application.mailchimp');
        $applicationInstall = DataProvider::getOauth2AppInstall(
            $application->getKey(),
            'user',
            'fa830d8d4308625bac307906e83de659'
        );
        $applicationInstall->setSettings([
            ApplicationAbstract::FORM          => [MailchimpApplication::AUDIENCE_ID => 'c9e7f10c5b'],
            MailchimpApplication::API_KEYPOINT => $application->getApiEndpoint($applicationInstall),
        ]);

        $subscription = new WebhookSubscription('test', 'node', 'xxx', ['name' => 0]);

        $request = $application->getWebhookSubscribeRequestDto(
            $applicationInstall,
            $subscription,
            sprintf('%s/webhook/topologies/%s/nodes/%s/token/%s',
                rtrim('www.xx.cz', '/'),
                $subscription->getTopology(),
                $subscription->getNode(),
                bin2hex(random_bytes(25)))
        );
        self::assertEquals(
            $request->getUriString(),
            sprintf('%s/3.0/lists/%s/webhooks',
                $applicationInstall->getSettings()[MailchimpApplication::API_KEYPOINT],
                $applicationInstall->getSettings()[ApplicationAbstract::FORM][MailchimpApplication::AUDIENCE_ID])
        );
    }

}
