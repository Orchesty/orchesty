<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\Utils;

use Hanaboso\PipesFramework\Utils\JWTParser;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use LogicException;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class JWTParserTest
 *
 * @package PipesFrameworkTests\Unit\Utils
 */
final class JWTParserTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::verifyAndReturn
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testVerifyAndReturn(): void
    {
        self::assertTrue(count(JWTParser::verifyAndReturn()) > 0);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::verifyAndReturn
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testVerifyAndReturnEnv(): void
    {
        /** @phpstan-ignore-next-line */
        putenv(sprintf('%s=%s', 'ORCHESTY_LICENSE', $this->createJwtToken()));
        self::assertTrue(count(JWTParser::verifyAndReturn()) > 0);
        /** @phpstan-ignore-next-line */
        putenv('ORCHESTY_LICENSE');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::verifyAndReturn
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testVerifyAndReturnRootPath(): void
    {
        File::putContent('tests/Unit/Utils/data/license/license', $this->createJwtToken());
        self::assertTrue(count(JWTParser::verifyAndReturn('tests/Unit/Utils/data/')) > 0);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::verifyAndReturn
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testVerifyAndReturnNotValid(): void
    {
        File::putContent('tests/Unit/Utils/data/license/license', $this->createJwtToken('-1 minute'));
        self::expectException(LogicException::class);
        JWTParser::verifyAndReturn('tests/Unit/Utils/data/');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::getJwtLicense
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testGetJwtLicense(): void
    {
        self::assertEquals(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ2ZXJzaW9uIjoxLCJ1c2VycyI6MSwiYXBwbGljYXRpb25zIjozLCJ0eXBlIjoiZnJlZSIsImVtYWlsIjoicHVibGljQG9yY2hlc3R5LmlvIiwibmFtZSI6IkZyZWUgYWNjb3VudCIsIm51bWJlciI6IjEiLCJpc3MiOiJIYW5hYm9zbyBzLnIuby4iLCJpYXQiOjE2Mzc3NjMwNjAuNTg5MzcxLCJuYmYiOjE2Mzc3NjMwNjAuNTg5MzcxLCJleHAiOjE3MDA4MzUwNjAuNTg5MzcxfQ.Kx3dIAUHLjrri-Fd74XGCC-HXtqosAawUnLBB6yWMREim_zCTJsz876zhKEALtrJwESskVOCYu1YRAo3Hggx0jWzld2ncRjNRSotV7xivF_o5hwUfbxiwMF5ovpmvCI1NP-3m1GrV1CLRepSI3GbbvD3HwGTViJ0Ax1k6xp0jpd0jms4G-CWE5IOm4bB7HZeSTrORvjUh86aZmJun3x2JyNI46ZikohOK-AueK6VgI_AB62IEXIr46P2cfkBCrKpDzzkXJ3h-GhNgd8-E5fn9Ir_9hmyQ9KF1YTYVhe7aB4sacYkOmh2C2cmv9Heb25eUylBDIh5BoU-OLvDvTWSOaRjbTlIbttWcD2e8Mqt3l1SCNWymiOtjDNwXbZHSZznOfwLV2K6_qhE_3X-OPucdib0c1qU3pT6O2a8ENAgzYxIxPbG1h2-Qsf1Pgm1t_FARPjUW2RwJac6w9S579aSvztlIdfEWjRb4xE1gSeFUxD402dD1FIwvuK-L-ntEyK08j4aib5wSWfc7Nltfr4ADt8HyG7Qr_jNe-3IrMkk_kXkNLxbepNf_VhWp_LMYKEhKNTRnWBCQogUeY3E46-wLnhQQryLygWsE-mBbincKCt6SZySWrTzVOQQdUPP1TGRt0ARy4g6-exAchCVxYIGcBUsPOk1mqFVALh7FlsLfXY',
            JWTParser::getJwtLicense(),
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::getJwtLicense
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testGetJwtLicenseEnv(): void
    {
        $token = $this->createJwtToken();
        /** @phpstan-ignore-next-line */
        putenv(sprintf('%s=%s', 'ORCHESTY_LICENSE', $token));
        self::assertEquals($token, JWTParser::getJwtLicense());
        /** @phpstan-ignore-next-line */
        putenv('ORCHESTY_LICENSE');
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::getJwtLicense
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testGetJwtLicensePath(): void
    {
        $token = $this->createJwtToken('+2 year');
        File::putContent('tests/Unit/Utils/data/license/license', $token);
        self::assertEquals($token, JWTParser::getJwtLicense('tests/Unit/Utils/data/'));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::getJwtLicense
     * @covers \Hanaboso\PipesFramework\Utils\JWTParser::jwtVerify
     */
    public function testGetJwtLicenseNotValid(): void
    {
        File::putContent('tests/Unit/Utils/data/license/license', $this->createJwtToken('-1 minute'));
        self::expectException(LogicException::class);
        JWTParser::getJwtLicense('tests/Unit/Utils/data/');
    }

    /**
     * @param string $timeModify
     *
     * @return string
     * @throws DateTimeException
     */
    private function createJwtToken(string $timeModify = '+1 minute'): string
    {
        $dateTime = DateTimeUtils::getUtcDateTime();
        $jws      = (new JWSBuilder(new AlgorithmManager([new RS256()])))
            ->create()
            ->withPayload(
                Json::encode(
                    [
                        'version'      => 1,
                        'users'        => 10,
                        'applications' => 300,
                        'type'         => 'free',
                        'email'        => 'public@orchesty.io',
                        'name'         => 'Free account',
                        'number'       => '1',
                        'iss'          => 'Hanaboso s.r.o.',
                        'iat'          => $dateTime->getTimestamp(),
                        'nbf'          => $dateTime->getTimestamp(),
                        'exp'          => $dateTime->modify($timeModify)->getTimestamp(),
                    ],
                ),
            )
            ->addSignature(JWKFactory::createFromKeyFile(__DIR__ . '/jwt.pem'), ['alg' => 'RS256'])
            ->build();

        return (new CompactSerializer())->serialize($jws);
    }

}
