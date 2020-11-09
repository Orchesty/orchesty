<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpTagContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class MailchimpTagContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpTagContactConnectorTest extends DatabaseTestCaseAbstract
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
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    'responseDatacenter.json',
                    []
                ),
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    []
                ),
            ]
        );

        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpTagContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $mailchimpCreateContactConnector->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d4308625ba**********de659'
        );

        $applicationInstall->addSettings(
            [
                ApplicationAbstract::FORM          => [
                    MailchimpApplication::AUDIENCE_ID => '2a8******8',
                ],
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
                MailchimpApplication::SEGMENT_ID   => 'segment_id',
            ]
        );

        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);

        $dto      = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/response%s.json', $code), TRUE)
        );
        $response = $mailchimpCreateContactConnector->processAction($dto);

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpTagContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $mailchimpCreateContactConnector->setApplication($app);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getKey(),
            'user',
            'password'
        );

        $this->pfd($applicationInstall);
        $this->dm->refresh($applicationInstall);
        self::expectException(ConnectorException::class);
        $mailchimpCreateContactConnector->processEvent(
            DataProvider::getProcessDto(
                $app->getKey(),
                'user',
                ''
            )
        );
    }

    /**
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [400, FALSE],
            [200, TRUE],
        ];
    }

    /**
     * @throws Exception
     */
    public function testGetId(): void
    {
        $mailchimpCreateContactConnector = new MailchimpTagContactConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );
        self::assertEquals(
            'mailchimp_tag_contact',
            $mailchimpCreateContactConnector->getId()
        );
    }

}
