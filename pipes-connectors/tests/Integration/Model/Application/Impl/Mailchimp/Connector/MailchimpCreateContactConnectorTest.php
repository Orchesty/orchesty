<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpCreateContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class MailchimpCreateContactConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpCreateContactConnectorTest extends DatabaseTestCaseAbstract
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
        $this->mockCurl([
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
        ]);

        $app                             = self::$container->get('hbpf.application.mailchimp');
        $mailchimpCreateContactConnector = new MailchimpCreateContactConnector(
            $app,
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getKey(),
            'user',
            'fa830d8d4308625bac307906e83de659'
        );

        $applicationInstall->setSettings([
            MailchimpApplication::AUDIENCE_ID  => 'c9e7f10c5b',
            MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
        ]);

        $this->pf($applicationInstall);

        $dto      = DataProvider::getProcessDto(
            $app->getKey(),
            'user',
            (string) file_get_contents(__DIR__ . sprintf('/Data/response%s.json', $code), TRUE)
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
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [400, FALSE],
            [200, TRUE],
        ];
    }

}

