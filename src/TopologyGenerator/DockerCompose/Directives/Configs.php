<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 23.11.17
 * Time: 15:34
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Directives;

/**
 * Class Configs
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Directives
 */
class Configs
{

    /**
     * @var null|string
     */
    protected $source = NULL;

    /**
     * @var null|string
     */
    protected $target = NULL;

    /**
     * @var bool
     */
    protected $external = TRUE;

    /**
     * @var null|int
     */
    protected $uid = NULL;

    /**
     * @var null|int
     */
    protected $gid = NULL;

    /**
     * @var null|string
     */
    protected $mode = NULL;

    /**
     * Configs constructor.
     *
     * @param null|string $source
     * @param null|string $target
     * @param bool        $external
     * @param int|null    $uid
     * @param int|null    $gid
     * @param null|string $mode
     */
    public function __construct(?string $source = NULL, ?string $target = NULL, bool $external = TRUE, ?int $uid = NULL,
                                ?int $gid = NULL, ?string $mode = NULL)
    {
        $this->source   = $source;
        $this->target   = $target;
        $this->external = $external;
        $this->uid      = $uid;
        $this->gid      = $gid;
        $this->mode     = $mode;
    }

    /**
     * @param string $suffix
     *
     * @return null|string
     */
    public function getSource(?string $suffix = NULL): ?string
    {
        if ($suffix) {
            return sprintf('%s_%s', $this->source, $suffix);
        }

        return $this->source;
    }

    /**
     * @param null|string $source
     *
     * @return Configs
     */
    public function setSource(?string $source): Configs
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @param null|string $target
     *
     * @return Configs
     */
    public function setTarget(string $target): Configs
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->external;
    }

    /**
     * @param bool $external
     *
     * @return Configs
     */
    public function setExternal(bool $external): Configs
    {
        $this->external = $external;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    /**
     * @param int|null $uid
     *
     * @return Configs
     */
    public function setUid(?int $uid): Configs
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getGid(): ?int
    {
        return $this->gid;
    }

    /**
     * @param int|null $gid
     *
     * @return Configs
     */
    public function setGid(?int $gid): Configs
    {
        $this->gid = $gid;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getMode(): ?string
    {
        return $this->mode;
    }

    /**
     * @param null|string $mode
     *
     * @return Configs
     */
    public function setMode(?string $mode): Configs
    {
        $this->mode = $mode;

        return $this;
    }

}
