<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Manager;

use Hanaboso\Utils\String\Json;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class AuthorizationManager
 *
 * @package Hanaboso\Applinth\Manager
 */
final class AuthorizationManager
{

    /**
     * AuthorizationManager constructor.
     *
     * @param string               $jwePrivateKey
     * @param string               $jwsKey
     * @param JWESerializerManager $jweSerializerManager
     * @param JWEDecrypter         $jweDecrypter
     * @param JWSSerializerManager $jwsSerializerManager
     * @param JWSBuilder           $jwsBuilder
     * @param JWSVerifier          $jwsVerifier
     */
    public function __construct(
        private readonly string $jwePrivateKey,
        private readonly string $jwsKey,
        private readonly JWESerializerManager $jweSerializerManager,
        private readonly JWEDecrypter $jweDecrypter,
        private readonly JWSSerializerManager $jwsSerializerManager,
        private readonly JWSBuilder $jwsBuilder,
        private readonly JWSVerifier $jwsVerifier,
    )
    {
    }

    /**
     * @param string $jweToken
     *
     * @return mixed[]
     */
    public function payloadFromJwe(string $jweToken): array
    {
        $jwe = $this->jweSerializerManager->unserialize($jweToken);

        if ($this->jweDecrypter->decryptUsingKey($jwe, $this->createJweJwk(), 0)) {
            return Json::decode($jwe->getPayload());
        }

        throw new AuthenticationException('Not valid token', 403);
    }

    /**
     * @param string $jwsToken
     *
     * @return mixed[]
     */
    public function payloadFromJws(string $jwsToken): array
    {
        $jws = $this->jwsSerializerManager->unserialize($jwsToken);

        if ($this->jwsVerifier->verifyWithKey($jws, $this->createJwsJwk(), 0)) {
            return Json::decode($jws->getPayload() ?? '{}');
        }

        throw new AuthenticationException('Not valid token', 403);
    }

    /**
     * @param array<mixed> $payload
     *
     * @return string
     */
    public function jwsFromPayload(array $payload): string
    {
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(Json::encode($payload))
            ->addSignature($this->createJwsJwk(), ['alg' => (new HS512())->name()])
            ->build();

        return $this->jwsSerializerManager->serialize(CompactSerializer::NAME, $jws);
    }

    /**
     * @return JWK
     */
    private function createJwsJwk(): JWK
    {
        return JWKFactory::createFromSecret($this->jwsKey);
    }

    /**
     * @return JWK
     */
    private function createJweJwk(): JWK
    {
        return JWKFactory::createFromKey($this->jwePrivateKey);
    }

}
