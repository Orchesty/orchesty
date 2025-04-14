<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
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

    public const string  CLIENT_SETTINGS = 'client_settings';
    public const string  AUTH_SESSION_ID = 'authSessionId';
    public const string  REFRESH_TOKEN   = 'refreshToken';
    public const string  CSRF_TOKEN      = 'csrfToken';
    public const string  TOKEN_GET       = 'token_get';

    public const string INCORECT_RESPONSE = 'Incorect response';
    public const string CANNOT_GET_BODY   = 'Cannot get body from response.';
    public const string TOKEN_NOT_SUCCESS = 'Token is not successed returned';

    public const string  FLEXIBEE_URL = 'flexibeeUrl';

    private const string KEY = 'flexibee';

    private const string AUTH      = 'auth';
    private const string AUTH_JSON = 'json';
    private const string AUTH_HTTP = 'http';

    private const int TOKEN_MAX_LIFE = 60 * 30; // 30mmin

    private const string ENDPOINT_LOGIN = 'login-logout/login.json';

    /**
     * FlexiBeeApplication constructor.
     *
     * @param CurlManager                  $curlManager
     * @param ApplicationInstallRepository $applicationInstallRepository
     */
    public function __construct(
        private CurlManager $curlManager,
        private readonly ApplicationInstallRepository $applicationInstallRepository,
    )
    {
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::BASIC->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::KEY;
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $request = new RequestDto($this->getUri($url), $method, $dto);
        if ($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::AUTH] == self::AUTH_JSON) {
            $request->setHeaders(
                [
                    'Accept'          => 'application/json',
                    'Content-Type'    => 'application/json',
                    'X-authSessionId' => $this->getApiToken($applicationInstall, $dto),
                ],
            );
        } else if ($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::AUTH] == self::AUTH_HTTP) {
            $request->setHeaders(
                [
                    'Accept'        => 'application/json',
                    'Authorization' => base64_encode(
                        sprintf(
                            ' Basic %s:%s',
                            $applicationInstall->getSettings(
                            )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
                            $applicationInstall->getSettings(
                            )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
                        ),
                    ),
                    'Content-Type'  => 'application/json',
                ],
            );
        }

        if ($data !== NULL) {
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
        $host = $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::FLEXIBEE_URL] ?? '';

        if ($host === '') {
            throw new ApplicationInstallException(
                'There is no flexibee url',
                ApplicationInstallException::INVALID_FIELD_TYPE,
            );
        }

        return new Uri(sprintf('%s/%s', $host, parent::getUri($url)));
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $authTypeField = new Field(Field::SELECT_BOX, self::AUTH, 'Authorize type', NULL, TRUE);
        $authTypeField->setChoices([self::AUTH_HTTP, self::AUTH_JSON]);

        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, BasicApplicationInterface::USER, 'User', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, BasicApplicationInterface::PASSWORD, 'Password', NULL, TRUE))
            ->addField(new Field(Field::URL, self::FLEXIBEE_URL, 'Flexibee URL', NULL, TRUE))
            ->addField($authTypeField);

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $settings = $applicationInstall->getSettings();

        return isset($settings[ApplicationInterface::AUTHORIZATION_FORM][self::USER])
            && isset($settings[ApplicationInterface::AUTHORIZATION_FORM][self::PASSWORD]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param ProcessDtoAbstract $dto
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    private function getApiTokenDto(ApplicationInstall $applicationInstall, ProcessDtoAbstract $dto): RequestDto
    {
        $settings = $applicationInstall->getSettings();
        if (!$this->isAuthorized($applicationInstall)) {
            throw new ApplicationInstallException(
                'User is not authenticated',
                ApplicationInstallException::INVALID_FIELD_TYPE,
            );
        }

        $user     = $settings[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER];
        $password = $settings[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD];

        $request = new RequestDto(
            $this->getUrl($applicationInstall, self::ENDPOINT_LOGIN),
            CurlManager::METHOD_POST,
            $dto,
        );

        $request->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
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
     * @param ProcessDtoAbstract $dto
     *
     * @return string
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    private function getApiToken(ApplicationInstall $applicationInstall, ProcessDtoAbstract $dto): string
    {
        $token = $this->getApiTokenFromSettings($applicationInstall);

        if (!$token) {
            $res = $this->curlManager->send($this->getApiTokenDto($applicationInstall, $dto));

            if ($res->getStatusCode() != 200) {
                throw new Exception(self::INCORECT_RESPONSE);
            }

            try {
                $token = $res->getJsonBody();
            } catch (Throwable $e) {
                $e;

                throw new Exception(self::CANNOT_GET_BODY);
            }

            if (!$token['success']) {
                throw new Exception(self::TOKEN_NOT_SUCCESS);
            }

            $applicationInstall->addSettings(
                [
                    self::CLIENT_SETTINGS => [
                        self::AUTH_SESSION_ID => $token[self::AUTH_SESSION_ID],
                        self::CSRF_TOKEN      => $token[self::CSRF_TOKEN],
                        self::REFRESH_TOKEN   => $token[self::REFRESH_TOKEN],
                        self::TOKEN_GET       => DateTimeUtils::getUtcDateTime()->getTimestamp(),
                    ],
                ],
            );

            $this->applicationInstallRepository->insert($applicationInstall);
        }

        return $token[self::AUTH_SESSION_ID];
    }

}
