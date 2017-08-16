<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: maca
 * Date: 07.03.17
 * Time: 19:43
 */

namespace Hanaboso\PipesFramework\Commons\Message;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class DefaultMessage
 *
 * @package Hanaboso\PipesFramework\Commons\Message
 */
class DefaultMessage implements MessageInterface
{

    /**
     * @Serializer\Type("array")
     * @var array
     */
    protected $settings;

    /**
     * @Serializer\Type("string")
     *
     * @var string
     */
    protected $data;

    /**
     * DefaultMessage constructor.
     *
     * @param string $data
     * @param array  $settings
     */
    public function __construct(string $data = '', array $settings = [])
    {
        $this->settings = $settings;
        $this->data     = $data;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @param string $data
     */
    public function setData(string $data): void
    {
        $this->data = $data;
    }

}
