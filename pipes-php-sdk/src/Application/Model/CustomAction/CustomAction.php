<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Model\CustomAction;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;

/**
 * Class CustomAction
 *
 * @package Hanaboso\PipesPhpSdk\Application\Model\CustomAction
 */
final class CustomAction
{

    public const ACTION_OPEN = 'open';
    public const ACTION_CALL = 'call';

    /**
     * CustomAction constructor.
     *
     * @param string      $name
     * @param string      $action
     * @param string|null $url
     * @param string|null $body
     * @param string|null $topologyName
     * @param string|null $nodeName
     *
     * @throws ApplicationInstallException
     */
    public function __construct(
        private readonly string $name,
        private readonly string $action,
        private ?string $url = NULL,
        private ?string $body = NULL,
        private ?string $topologyName = NULL,
        private ?string $nodeName = NULL,
    )
    {
        if (!in_array($action, $this->getActionTypes(), TRUE)) {
            throw new ApplicationInstallException(
                sprintf('Invalid action type "%s"', $action),
                ApplicationInstallException::INVALID_CUSTOM_ACTION_TYPE,
            );
        }

        if (!($url || ($topologyName && $nodeName))) {
            throw new ApplicationInstallException(
                'One of these parameters is missing: url or topologyName and nodeName',
                ApplicationInstallException::MISSING_REQUIRED_PARAMETER,
            );
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return CustomAction
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return CustomAction
     */
    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTopologyName(): ?string
    {
        return $this->topologyName;
    }

    /**
     * @param string|null $topologyName
     *
     * @return CustomAction
     */
    public function setTopologyName(?string $topologyName): self
    {
        $this->topologyName = $topologyName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNodeName(): ?string
    {
        return $this->nodeName;
    }

    /**
     * @param string|null $nodeName
     *
     * @return CustomAction
     */
    public function setNodeName(?string $nodeName): self
    {
        $this->nodeName = $nodeName;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'action'       => $this->action,
            'body'         => $this->body,
            'name'         => $this->name,
            'nodeName'     => $this->nodeName,
            'topologyName' => $this->topologyName,
            'url'          => $this->url,
        ];
    }

    /**
     * @return string[]
     */
    private function getActionTypes(): array
    {
        return [
            self::ACTION_CALL,
            self::ACTION_OPEN,
        ];
    }

}
