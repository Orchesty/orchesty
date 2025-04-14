<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Provider\dto;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class OAuth1DtoTest
 *
 * @package PipesPhpSdkTests\Unit\Authorization\Provider\dto
 */
#[CoversClass(OAuth1Dto::class)]
final class OAuth1DtoTest extends KernelTestCaseAbstract
{

    /**
     * @return void
     */
    public function testOauth1Dto(): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        ApplicationInterface::TOKEN => [
                            'access_token' => '__token__',
                            'expires_in'   => 'inFuture',
                        ],
                        OAuth1ApplicationInterface::CONSUMER_KEY    => '__consumerKey__',
                        OAuth1ApplicationInterface::CONSUMER_SECRET => '__consumerSecret__',
                    ],
                ],
            )
            ->setKey('key');

        $dto = new OAuth1Dto($applicationInstall);

        self::assertSame('__consumerKey__', $dto->getConsumerKey());
        self::assertSame('__consumerSecret__', $dto->getConsumerSecret());
        self::assertSame('HMAC-SHA1', $dto->getSignatureMethod());
        self::assertEquals('3', $dto->getAuthType());
        self::assertSame('key', $dto->getApplicationInstall()->getKey());
        self::assertEquals(
            [
                'access_token' => '__token__',
                'expires_in'   => 'inFuture',
            ],
            $dto->getToken(),
        );
    }

}
