<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\Applinth\Authenticator\Document\MarketPlaceRestrictedToken;
use Hanaboso\Applinth\Authenticator\Repository\MarketPlaceRestrictedTokenRepository;
use Hanaboso\Applinth\Manager\AuthorizationManager;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Repository\ApplicationInstallRepository;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class AuthorizationHandler
 *
 * @package Hanaboso\Applinth\Handler
 */
final class AuthorizationHandler
{

    public const  AUTHORIZATION_FORM = 'authorization_form';
    public const  EXP                = 'exp';

    public const EU_ALIAS = 'eu_alias';
    public const EU_SUB   = 'eu_sub';
    public const SUB      = 'sub';

    private const IAT      = 'iat';
    private const PIN      = 'pin';
    private const SETTINGS = 'settings';

    /**
     * @var MarketPlaceRestrictedTokenRepository
     */
    private MarketPlaceRestrictedTokenRepository $jweRepository;

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $appInstallRepository;

    /**
     * AuthorizationHandler constructor.
     *
     * @param AuthorizationManager $authorizationManager
     * @param DocumentManager      $dm
     * @param ServiceLocator       $locator
     */
    public function __construct(
        private readonly AuthorizationManager $authorizationManager,
        private readonly DocumentManager $dm,
        private readonly ServiceLocator $locator,
    )
    {
        $repo                = $this->dm->getRepository(MarketPlaceRestrictedToken::class);
        $this->jweRepository = $repo;

        $appRepo                    = $this->dm->getRepository(ApplicationInstall::class);
        $this->appInstallRepository = $appRepo;
    }

    /**
     * @param string $jwsToken
     *
     * @return array<mixed>
     */
    public function payloadFromJws(string $jwsToken): array
    {
        return $this->authorizationManager->payloadFromJws($jwsToken);
    }

    /**
     * @param array<mixed> $payload
     *
     * @return string
     */
    public function jwsFromPayload(array $payload): string
    {
        return $this->authorizationManager->jwsFromPayload($payload);
    }

    /**
     * @param string $jwsToken
     *
     * @return mixed[]
     */
    public function jwsFromJws(string $jwsToken): array
    {
        $payload = $this->payloadFromJws($jwsToken);

        $payload[self::IAT] = time();
        $payload[self::EXP] = time() + 3_600;

        return [
            $this->jwsFromPayload($payload),
            $payload[self::EXP],
        ];
    }

    /**
     * @param string $jweToken
     *
     * @return bool
     */
    public function isTokenExits(string $jweToken): bool
    {
        return (bool) $this->jweRepository->findOneBy([MarketPlaceRestrictedToken::VALUE => $jweToken]);
    }

    /**
     * @param string $jweToken
     *
     * @return void
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function saveRestrictToken(string $jweToken): void
    {
        if (!$this->isTokenExits($jweToken)) {
            $this->dm->persist(new MarketPlaceRestrictedToken($jweToken));
            $this->dm->flush();
        }
    }

    /**
     * @param mixed[] $jwePayload
     *
     * @return string|null
     */
    public function initRootApp(array $jwePayload): ?string
    {
        $link     = NULL;
        $key      = $jwePayload[self::SUB];
        $user     = $jwePayload[self::EU_SUB];
        $settings = $jwePayload[self::SETTINGS] ?? [];

        try {
            $this->appInstallRepository->findUserApp($key, $user);
        } catch (Exception) {
            $this->locator->installApp($key, $user);
            $pin = hash('sha256', sprintf('%s-%s-%s', time(), $key, $user));

            $formSettings            = [];
            $formSettings[self::PIN] = $pin;
            foreach ($settings as $k => $v) {
                $formSettings[$k] = $v;
            }

            $resp = $this->locator->updateApp($key, $user, [self::AUTHORIZATION_FORM => $formSettings]);

            try {
                $app = $this->appInstallRepository->findUserApp($key, $user);
                $app->setEnabled(TRUE);
                $app->setNonEncryptedSettings([self::PIN => $pin, self::EU_ALIAS => $jwePayload[self::EU_ALIAS]]);
                $this->dm->flush();

                $link = $resp['applicationSettings'][self::AUTHORIZATION_FORM]['redirect_url'] ?? NULL;
            } catch (Exception) {
            }
        }

        return $link;
    }

    /**
     * @param string $jweToken
     * @param bool   $includeSettings
     *
     * @return mixed[]
     */
    public function payloadFromJwe(string $jweToken, bool $includeSettings = FALSE): array
    {
        $payload = $this->authorizationManager->payloadFromJwe($jweToken);

        if ($includeSettings && isset($payload[self::SETTINGS])) {
            unset($payload[self::SETTINGS]);
        }

        return $payload;
    }

    /**
     * @param mixed[]  $payload
     * @param int|null $expirationTime
     *
     * @return mixed[]
     */
    public function jwsFromJwe(array $payload, ?int $expirationTime = 3_600): array
    {
        unset($payload[self::EU_ALIAS]);

        $payload[self::IAT] = time();
        if ($expirationTime) {
            $payload[self::EXP] = time() + $expirationTime;
        }

        return [
            $this->jwsFromPayload($payload),
            $payload[self::EXP] ?? NULL,
        ];
    }

}
