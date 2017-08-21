<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Base;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;

/**
 * Class AuthorizationAbstract
 *
 * @package Hanaboso\PipesFramework\Authorization\Base
 */
abstract class AuthorizationAbstract implements AuthorizationInterface
{

    protected const ID          = 'id';
    protected const NAME        = 'name';
    protected const DESCRIPTION = 'description';

    /**
     * @var string[]
     */
    private $config = [];

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Authorization
     */
    protected $authorization;

    /**
     * @var bool
     */
    protected $authorized = TRUE;

    /**
     * AuthorizationAbstract constructor.
     *
     * @param string          $id
     * @param string          $name
     * @param string          $description
     * @param DocumentManager $dm
     */
    public function __construct(string $id, string $name, string $description, DocumentManager $dm)
    {
        $this->dm     = $dm;
        $this->config = [
            self::ID          => $id,
            self::NAME        => $name,
            self::DESCRIPTION => $description,
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->config[self::ID];
    }

    /**
     * @return mixed[]
     */
    public function getInfo(): array
    {
        return [
            'name'          => $this->config[self::NAME],
            'description'   => $this->config[self::DESCRIPTION],
            'type'          => $this->getAuthorizationType(),
            'is_authorized' => $this->isAuthorized(),
        ];
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * @return bool
     */
    protected function load(): bool
    {
        if (!$this->authorization) {
            $this->authorization = $this->dm->getRepository(Authorization::class)->findOneBy([
                'authorizationKey' => $this->getId(),
            ]);

            if (!$this->authorization) {
                return FALSE;
            }
            $this->dm->detach($this->authorization);
        }

        return TRUE;
    }

    /**
     * @param array $data
     */
    protected function save(array $data): void
    {
        if (!$this->authorization) {
            $this->authorization = new Authorization($this->getId());
        }
        $this->authorization->setToken($data);

        $this->dm->persist($this->authorization);
        $this->dm->flush($this->authorization);
        $this->dm->detach($this->authorization);
    }

    /**
     * @param string[] $array
     * @param string   $key
     * @param string   $default
     *
     * @return string
     */
    protected function getParam(array $array, string $key, string $default = ''): string
    {
        if (!array_key_exists($key, $array)) {
            return $default;
        }

        return $array[$key];
    }

    /**
     * @return string[]
     */
    protected function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $data
     */
    protected function setConfig(array $data): void
    {
        $this->config = array_merge($this->config, $data);
    }

}