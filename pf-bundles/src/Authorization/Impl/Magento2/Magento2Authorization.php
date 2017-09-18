<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Base\AuthorizationAbstract;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;

/**
 * Class Magento2Authorization
 *
 * @package Hanaboso\PipesFramework\Authorization\Impl\Magento2
 */
class Magento2Authorization extends AuthorizationAbstract implements Magento2AuthorizationInterface
{

    private const URL      = 'field1';
    private const USERNAME = 'field2';
    private const PASSWORD = 'field3';
    private const TOKEN    = 'token';

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * Magento2Authorization constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param string               $id
     * @param string               $name
     * @param string               $description
     */
    public function __construct(
        DocumentManager $dm,
        CurlManagerInterface $curl,
        string $id,
        string $name,
        string $description
    )
    {
        parent::__construct($id, $name, $description, $dm);
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            return FALSE;
        }

        return isset($this->authorization->getToken()[self::TOKEN]);
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getHeaders(string $method, string $url): array
    {
        if (!$this->isAuthorized()) {
            $this->authorize();
        }

        $settings = $this->authorization->getSettings();
        if (empty($settings[self::URL]) || empty($settings[self::USERNAME]) || empty($settings[self::PASSWORD])) {
            throw new AuthorizationException(
                sprintf('Authorization settings \'%s\' not found', $this->getId()),
                AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
            );
        }

        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('Bearer %s', $this->authorization->getToken()[self::TOKEN]),
        ];
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getSettings()[self::URL];
    }

    /**
     * @return string[]
     *
     * @throws AuthorizationException
     */
    public function getSettings(): array
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            return ['readme' => $this->getReadMe()];
        }

        $settings = $this->authorization->getSettings();
        if (empty($settings[self::URL]) || empty($settings[self::USERNAME]) || empty($settings[self::PASSWORD])) {
            return ['readme' => $this->getReadMe()];
        }

        $settings['readme'] = $this->getReadMe();

        return $settings;
    }

    /**
     * @param string[] $data
     *
     * @throws AuthorizationException
     */
    public function saveSettings(array $data): void
    {
        $this->loadAuthorization();
        if (!$this->authorization) {
            $this->authorization = new Authorization($this->getId());
            $this->dm->persist($this->authorization);
        }

        if (empty($data['field1']) || empty($data['field2']) || empty($data['field3'])) {
            throw new AuthorizationException(
                sprintf('Authorization settings \'%s\' not found', $this->getId()),
                AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
            );
        }

        $this->authorization->setSettings([
            self::URL      => $data['field1'],
            self::USERNAME => $data['field2'],
            self::PASSWORD => $data['field3'],
        ]);
        $this->dm->flush();

        $this->authorization->setToken($this->authorize());
        $this->dm->flush();
    }

    /**
     * @return string
     */
    public function getReadMe(): string
    {
        return 'Field1 contains connector URL, field2 contains username, field3 contains password.';
    }

    /**
     * @return array
     * @throws AuthorizationException
     */
    private function authorize(): array
    {
        $settings = $this->getSettings();
        if (empty($settings[self::URL]) || empty($settings[self::USERNAME]) || empty($settings[self::PASSWORD])) {
            throw new AuthorizationException(
                sprintf('Authorization settings \'%s\' not found', $this->getId()),
                AuthorizationException::AUTHORIZATION_SETTINGS_NOT_FOUND
            );
        }

        $dto = (new RequestDto('POST', new Uri($this->getAuthorizationUrl())))
            ->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->setBody(
                sprintf('{"username":"%s", "password":"%s"}', $settings[self::USERNAME], $settings[self::PASSWORD])
            );

        return Json::decode($this->curl->send($dto)->getBody(), Json::FORCE_ARRAY);
    }

    /**
     *
     */
    private function loadAuthorization(): void
    {
        $this->authorization = $this->dm->getRepository(Authorization::class)->findOneBy([
            'authorizationKey' => $this->getId(),
        ]);
    }

    /**
     * @return string
     */
    private function getAuthorizationUrl(): string
    {
        return sprintf('%s/rest/V1/integration/admin/token', $this->getSettings()[self::URL]);
    }

}