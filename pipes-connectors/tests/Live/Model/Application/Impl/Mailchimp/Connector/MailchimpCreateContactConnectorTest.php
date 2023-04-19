<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $app                             = self::getContainer()->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $mailchimpCreateContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
        );

        $applicationInstall->setSettings(
            [
                'form'                             => ['audience_id' => '123'],
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
                MailchimpApplication::AUDIENCE_ID  => 'c9e7f***5b',
            ],
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["mailchimp"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($applicationInstall->toArray())),
            ),
        );

        //        $data = (string) file_get_contents(sprintf('%s/Data/requestMailchimp.json', __DIR__), TRUE);
        //        $mailchimpCreateContactConnector->processAction(
        //            DataProvider::getProcessDto(
        //                $app->getName(),
        //                'user',
        //                $data
        //            )
        //        );

        self::assertFake();
    }

}
