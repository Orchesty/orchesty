<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Mailchimp;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\MailchimpApplication;
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

}
