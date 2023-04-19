<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockCurlMethod;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    'responseDatacenter.json',
                    [],
                ),
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    [],
                ),
            ],
        );

        $app                             = self::getContainer()->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $mailchimpCreateContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
            'user',
            'fa830d8d4308625ba**********de659',
        );

        $applicationInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => array_merge(
                    $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM],
                    [MailchimpApplication::AUDIENCE_ID => '2a8******8'],
                ),
                MailchimpApplication::API_KEYPOINT       => $app->getApiEndpoint($applicationInstall),
            ],
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["mailchimp"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $dto      = DataProvider::getProcessDto(
            $app->getName(),
            'user',
            File::getContent(__DIR__ . sprintf('/Data/response%s.json', $code)),
        );
        $response = $mailchimpCreateContactConnector->processAction($dto);

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );

        self::assertEquals(
            'mailchimp_create_contact',
            $mailchimpCreateContactConnector->getName(),
        );
    }

    /**
     * @return mixed[]
     */
    public static function getDataProvider(): array
    {
        return [
            [400, FALSE],
            [200, TRUE],
        ];
    }

}
