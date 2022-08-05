<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use DateTimeZone;
use Hanaboso\Utils\File\File;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use LogicException;

/**
 * Class JWTParser
 *
 * @package Hanaboso\PipesFramework\Utils
 */
final class JWTParser
{

    private const ORCHESTY_LICENSE = 'ORCHESTY_LICENSE';
    private const DEFAULT_JWT      = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ2ZXJzaW9uIjoxLCJ1c2VycyI6MSwiYXBwbGljYXRpb25zIjozLCJ0eXBlIjoiZnJlZSIsImVtYWlsIjoicHVibGljQG9yY2hlc3R5LmlvIiwibmFtZSI6IkZyZWUgYWNjb3VudCIsIm51bWJlciI6IjEiLCJpc3MiOiJIYW5hYm9zbyBzLnIuby4iLCJpYXQiOjE2Mzc3NjMwNjAuNTg5MzcxLCJuYmYiOjE2Mzc3NjMwNjAuNTg5MzcxLCJleHAiOjE3MDA4MzUwNjAuNTg5MzcxfQ.Kx3dIAUHLjrri-Fd74XGCC-HXtqosAawUnLBB6yWMREim_zCTJsz876zhKEALtrJwESskVOCYu1YRAo3Hggx0jWzld2ncRjNRSotV7xivF_o5hwUfbxiwMF5ovpmvCI1NP-3m1GrV1CLRepSI3GbbvD3HwGTViJ0Ax1k6xp0jpd0jms4G-CWE5IOm4bB7HZeSTrORvjUh86aZmJun3x2JyNI46ZikohOK-AueK6VgI_AB62IEXIr46P2cfkBCrKpDzzkXJ3h-GhNgd8-E5fn9Ir_9hmyQ9KF1YTYVhe7aB4sacYkOmh2C2cmv9Heb25eUylBDIh5BoU-OLvDvTWSOaRjbTlIbttWcD2e8Mqt3l1SCNWymiOtjDNwXbZHSZznOfwLV2K6_qhE_3X-OPucdib0c1qU3pT6O2a8ENAgzYxIxPbG1h2-Qsf1Pgm1t_FARPjUW2RwJac6w9S579aSvztlIdfEWjRb4xE1gSeFUxD402dD1FIwvuK-L-ntEyK08j4aib5wSWfc7Nltfr4ADt8HyG7Qr_jNe-3IrMkk_kXkNLxbepNf_VhWp_LMYKEhKNTRnWBCQogUeY3E46-wLnhQQryLygWsE-mBbincKCt6SZySWrTzVOQQdUPP1TGRt0ARy4g6-exAchCVxYIGcBUsPOk1mqFVALh7FlsLfXY';

    /**
     * @param string|null $rootPath
     *
     * @return mixed[]
     */
    public static function verifyAndReturn(?string $rootPath = NULL): array
    {
        return self::jwtVerify($rootPath)->claims()->all();
    }

    /**
     * @param string|null $rootPath
     *
     * @return string
     */
    public static function getJwtLicense(?string $rootPath = NULL): string
    {
        return self::jwtVerify($rootPath)->toString();
    }

    /**
     * @param string|null $rootPath
     *
     * @return UnencryptedToken
     */
    private static function jwtVerify(?string $rootPath = NULL): UnencryptedToken
    {
        $jwt           = self::DEFAULT_JWT;
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file(sprintf('%s%s', __DIR__, '/jwt.pem')),
            InMemory::base64Encoded('ZjlqU2VncVl3dmRsSTRDOXN0bFc='),
        );
        $configuration->setValidationConstraints(
            new StrictValidAt(new SystemClock(new DateTimeZone('UTC'))),
        );

        if (getenv(self::ORCHESTY_LICENSE)) {
            $jwt = getenv(self::ORCHESTY_LICENSE);
        } else if ($rootPath) {
            $jwt = trim(File::getContent(sprintf('%s/license/license', $rootPath)));
        }

        /** @var UnencryptedToken $token */
        $token       = $configuration->parser()->parse($jwt);
        $constraints = $configuration->validationConstraints();
        if (!$configuration->validator()->validate($token, ...$constraints)) {
            throw new LogicException('Jwt is not valid');
        }

        return $token;
    }

}
