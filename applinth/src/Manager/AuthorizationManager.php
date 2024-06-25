<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Manager;

use Hanaboso\Utils\String\Json;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\ECDHES;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JWECompactSerializer;
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
final readonly class AuthorizationManager
{

    /**
     * @var JWESerializerManager
     */
    private JWESerializerManager $jweSerializerManager;

    /**
     * @var JWEDecrypter
     */
    private JWEDecrypter $jweDecrypter;

    /**
     * @var JWSSerializerManager
     */
    private JWSSerializerManager $jwsSerializerManager;

    /**
     * @var JWSBuilder
     */
    private JWSBuilder $jwsBuilder;

    /**
     * @var JWSVerifier
     */
    private JWSVerifier $jwsVerifier;

    /**
     * AuthorizationManager constructor.
     *
     * @param string $jwePrivateKey
     * @param string $jwsKey
     */
    public function __construct(private string $jwePrivateKey, private string $jwsKey)
    {

        $this->jweSerializerManager = new JWESerializerManager([new JWECompactSerializer()]);
        $this->jweDecrypter         = new JWEDecrypter(
            new AlgorithmManager([new ECDHES()]),
            new AlgorithmManager([new A128GCM()]),
            new CompressionMethodManager([new Deflate()]),
        );
        $this->jwsSerializerManager = new JWSSerializerManager([new CompactSerializer()]);
        $algoManager                = new AlgorithmManager([new HS512()]);
        $this->jwsBuilder           = new JWSBuilder($algoManager);
        $this->jwsVerifier          = new JWSVerifier($algoManager);
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
            return Json::decode($jwe->getPayload() ?? '[]');
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
