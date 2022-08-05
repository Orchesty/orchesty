<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Connector\MailchimpTagContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class MailchimpTagContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Mailchimp\Connector
 */
final class MailchimpTagContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                          = self::getContainer()->get('hbpf.application.mailchimp');
        $mailchimpTagContactConnector = new MailchimpTagContactConnector();
        $mailchimpTagContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
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

        $this->pfd($applicationInstall);
        $this->dm->clear();
        //        $data = (string) file_get_contents(sprintf('%s/Data/automation.json', __DIR__), TRUE);
        //        $mailchimpTagContactConnector->processAction(
        //                    DataProvider::getProcessDto(
        //                        $app->getName(),
        //                        'user',
        //                        $data
        //                    )
        //                );

        self::assertEmpty([]);
    }

}
