<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpTagContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class MailchimpTagContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpTagContactConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                          = self::getContainer()->get('hbpf.application.mailchimp');
        $mailchimpTagContactConnector = new MailchimpTagContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $mailchimpTagContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        $applicationInstall = DataProvider::getOauth2AppInstall(
            $app->getName(),
        );

        $applicationInstall->setSettings(
            [
                MailchimpApplication::AUDIENCE_ID  => 'c9e7f***5b',
                MailchimpApplication::API_KEYPOINT => $app->getApiEndpoint($applicationInstall),
                MailchimpApplication::SEGMENT_ID   => '181***',
                'form'                             => ['audience_id' => '123'],
            ],
        );

        //        $data = (string) file_get_contents(sprintf('%s/Data/automation.json', __DIR__), TRUE);
        //        $mailchimpTagContactConnector->processAction(
        //                    DataProvider::getProcessDto(
        //                        $app->getName(),
        //                        'user',
        //                        $data
        //                    )
        //                );

        self::assertFake();
    }

}
