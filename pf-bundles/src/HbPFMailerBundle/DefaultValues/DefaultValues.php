<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 16.9.17
 * Time: 22:58
 */

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues;

/**
 * Class DefaultValues
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues
 */
/**
 * Class DefaultValues
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\DefaultValues
 */
class DefaultValues
{

    /**
     * @var array
     */
    protected $from = [];

    /**
     * @var array
     */
    protected $subject = [];

    /**
     * @var array
     */
    protected $to = [];

    /**
     * @var array
     */
    protected $bcc = [];

    /**
     * DefaultValues constructor.
     *
     * @param array $from
     * @param array $subject
     * @param array $to
     * @param array $bcc
     */
    public function __construct(array $from = [], array $subject = [], array $to = [], array $bcc = [])
    {
        $this->from    = $from;
        $this->subject = $subject;
        $this->to      = $to;
        $this->bcc     = $bcc;
    }

    /**
     * @param array $data
     * @param array $defaults
     * @param array $fields
     *
     * @return array
     */
    public static function handleDefaults(
        array $data,
        array $defaults,
        array $fields = ['from', 'subject', 'to', 'bcc']
    )
    {
        foreach ($fields as $field) {
            if ((!array_key_exists($field, $data) || empty($data[$field])) && $defaults[$field]) {
                $data[$field] = $defaults[$field];
            }
        }

        return $data;
    }

    /**
     * @param string $module
     *
     * @return null|string
     */
    public function getFrom(string $module): ?string
    {
        if (array_key_exists($module, $this->from)) {
            return $this->from[$module];
        }

        return NULL;
    }

    /**
     * @param string $module
     *
     * @return null|string
     */
    public function getSubject(string $module): ?string
    {
        if (array_key_exists($module, $this->subject)) {
            return $this->subject[$module];
        }

        return NULL;
    }

    /**
     * @param string $module
     *
     * @return null|string
     */
    public function getTo(string $module): ?string
    {
        if (array_key_exists($module, $this->to)) {
            return $this->to[$module];
        }

        return NULL;
    }

    /**
     * @param string $module
     *
     * @return null|string
     */
    public function getBcc(string $module): ?string
    {
        if (array_key_exists($module, $this->bcc)) {
            return $this->bcc[$module];
        }

        return NULL;
    }

    /**
     * @param string $module
     *
     * @return array
     */
    public function getDefaults(string $module): array
    {
        return [
            "from"    => $this->getFrom($module),
            "subject" => $this->getSubject($module),
            "to"      => $this->getTo($module),
            "bcc"     => $this->getBcc($module),
        ];
    }

}
