<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use Throwable;

/**
 * Class FlexiBeeApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee
 */
final class FlexiBeeApplication extends BasicApplicationAbstract
{

    public const  CLIENT_SETTINGS = 'client_settings';
    public const  AUTH_SESSION_ID = 'authSessionId';
    public const  REFRESH_TOKEN   = 'refreshToken';
    public const  CSRF_TOKEN      = 'csrfToken';
    public const  TOKEN_GET       = 'token_get';

    public const INCORECT_RESPONSE = 'Incorect response';
    public const CANNOT_GET_BODY   = 'Cannot get body from response.';
    public const TOKEN_NOT_SUCCESS = 'Token is not successed returned';

    public const  FLEXIBEE_URL = 'flexibeeUrl';

    private const KEY = 'flexibee';

    private const AUTH      = 'auth';
    private const AUTH_JSON = 'json';
    private const AUTH_HTTP = 'http';

    private const TOKEN_MAX_LIFE = 60 * 30; // 30mmin

    private const ENDPOINT_LOGIN = 'login-logout/login.json';

    /**
     * FlexiBeeApplication constructor.
     *
     * @param CurlManager     $curlManager
     * @param DocumentManager $dm
     */
    public function __construct(private CurlManager $curlManager, private DocumentManager $dm)
    {
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::BASIC;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return self::KEY;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'FlexiBee Application';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'FlexiBee Application';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        if ($applicationInstall->getSettings()[self::FORM][self::AUTH] == self::AUTH_JSON) {
            $request->setHeaders(
                [
                    'Content-Type'    => 'application/json',
                    'Accept'          => 'application/json',
                    'X-authSessionId' => $this->getApiToken($applicationInstall),
                ],
            );
        } else if ($applicationInstall->getSettings()[self::FORM][self::AUTH] == self::AUTH_HTTP) {
            $request->setHeaders(
                [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => base64_encode(
                        sprintf(
                            ' Basic %s:%s',
                            $applicationInstall->getSettings(
                            )[self::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER],
                            $applicationInstall->getSettings(
                            )[self::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD],
                        ),
                    ),
                ],
            );
        }

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string|null        $url
     *
     * @return Uri
     * @throws ApplicationInstallException
     */
    public function getUrl(ApplicationInstall $applicationInstall, ?string $url): Uri
    {
        $host = $applicationInstall->getSettings()[self::FORM][self::FLEXIBEE_URL] ?? '';

        if (empty($host)) {
            throw new ApplicationInstallException(
                'There is no flexibee url',
                ApplicationInstallException::INVALID_FIELD_TYPE,
            );
        }

        return new Uri(sprintf('%s/%s', $host, parent::getUri($url)));
    }

    /**
     * @return Form
     */
    public function getSettingsForm(): Form
    {
        $authTypeField = new Field(Field::SELECT_BOX, self::AUTH, 'Authorize type', NULL, TRUE);
        $authTypeField->setChoices([self::AUTH_HTTP, self::AUTH_JSON]);

        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, BasicApplicationInterface::USER, 'User', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, BasicApplicationInterface::PASSWORD, 'Password', NULL, TRUE))
            ->addField(new Field(Field::URL, self::FLEXIBEE_URL, 'Flexibee URL', NULL, TRUE))
            ->addField($authTypeField);

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $settings = $applicationInstall->getSettings();

        return isset($settings[ApplicationInterface::AUTHORIZATION_SETTINGS][self::USER])
            && isset($settings[ApplicationInterface::AUTHORIZATION_SETTINGS][self::PASSWORD]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    private function getApiTokenDto(ApplicationInstall $applicationInstall): RequestDto
    {
        $settings = $applicationInstall->getSettings();
        if (!$this->isAuthorized($applicationInstall)) {
            throw new ApplicationInstallException(
                'User is not authenticated',
                ApplicationInstallException::INVALID_FIELD_TYPE,
            );
        }

        $user     = $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::USER];
        $password = $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD];

        $request = new RequestDto(
            CurlManager::METHOD_POST,
            $this->getUrl($applicationInstall, self::ENDPOINT_LOGIN),
        );

        $request->setHeaders(
            [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        )->setBody(Json::encode([self::USER => $user, self::PASSWORD => $password]));

        return $request;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed
     * @throws DateTimeException
     */
    private function getApiTokenFromSettings(ApplicationInstall $applicationInstall): mixed
    {
        $this->dm->refresh($applicationInstall);
        $token = $applicationInstall->getSettings()[self::CLIENT_SETTINGS] ?? [];

        $valid = DateTimeUtils::getUtcDateTime()->getTimestamp() - self::TOKEN_MAX_LIFE;
        if (isset($token[self::AUTH_SESSION_ID]) &&
            isset($token[self::TOKEN_GET]) &&
            $token[self::TOKEN_GET] > $valid
        ) {
            return $token;
        }

        return NULL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws MongoDBException
     */
    private function getApiToken(ApplicationInstall $applicationInstall): string
    {
        $token = $this->getApiTokenFromSettings($applicationInstall);

        if (!$token) {
            $res = $this->curlManager->send($this->getApiTokenDto($applicationInstall));

            if ($res->getStatusCode() != 200) {
                throw new Exception(self::INCORECT_RESPONSE);
            }

            try {
                $token = $res->getJsonBody();
            } catch (Throwable) {
                throw new Exception(self::CANNOT_GET_BODY);
            }

            if (!$token['success']) {
                throw new Exception(self::TOKEN_NOT_SUCCESS);
            }

            $applicationInstall->addSettings(
                [
                    self::CLIENT_SETTINGS => [
                        self::AUTH_SESSION_ID => $token[self::AUTH_SESSION_ID],
                        self::REFRESH_TOKEN   => $token[self::REFRESH_TOKEN],
                        self::CSRF_TOKEN      => $token[self::CSRF_TOKEN],
                        self::TOKEN_GET       => DateTimeUtils::getUtcDateTime()->getTimestamp(),
                    ],
                ],
            );

            $this->dm->persist($applicationInstall);
            $this->dm->flush();
        }

        return $token[self::AUTH_SESSION_ID];
    }

}
