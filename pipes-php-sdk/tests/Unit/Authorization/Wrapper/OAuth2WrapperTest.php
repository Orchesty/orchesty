<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Wrapper;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Wrapper\OAuth2Wrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class OAuth2WrapperTest
 *
 * @package PipesPhpSdkTests\Unit\Authorization\Wrapper
 */
#[CoversClass(OAuth2Wrapper::class)]
final class OAuth2WrapperTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetParsedResponseErr(): void
    {
        //        $wrapper = $this->createPartialMock(OAuth2Wrapper::class, ['getParsedResponse']);
        //        $wrapper->expects(self::any())->method('getParsedResponse')->willReturn(['result']);
        //        $res = $wrapper->getParsedResponse(new Request('GET', ''));
        //
        //        self::assertEquals(['result'], $res);

        //        $wrapper->expects(self::any())->method('getParsedResponse')
        //            ->willThrowException(new Exception('Upps, something went wrong.'));
        //
        //        self::expectException(AuthorizationException::class);
        //        $wrapper->getParsedResponse(new Request('GET', ''));

        $wrapper = new OAuth2Wrapper(
            [
                'urlAccessToken'          => 'access/url',
                'urlAuthorize'            => 'auth/url',
                'urlResourceOwnerDetails' => 'resource/url',
            ],
        );

        self::expectException(AuthorizationException::class);
        $wrapper->getParsedResponse(new Request('GET', ''));
    }

    /**
     * @throws Exception
     */
    public function testGetParsedResponseE(): void
    {
        $wrapper = $this->createPartialMock(OAuth2Wrapper::class, ['getResponse']);
        $wrapper->expects(self::any())->method('getResponse')->willReturn((new Response(200, [], '{"body": "body"}')));

        $res = $wrapper->getParsedResponse(new Request('GET', ''));

        self::assertEquals(['body' => 'body'], $res);
    }

}
