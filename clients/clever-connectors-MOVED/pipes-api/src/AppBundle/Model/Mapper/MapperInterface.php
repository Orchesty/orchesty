<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 11:09
 */

namespace CleverConnectors\AppBundle\Model\Mapper;

use CleverConnectors\AppBundle\Document\MapTemplate;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Interface MapperInterface
 *
 * @package CleverConnectors\AppBundle\Model\Mapper
 */
interface MapperInterface
{

    /**
     * @param MapTemplate $template
     * @param ProcessDto  $dto
     *
     * @return ProcessDto
     */
    public function process(MapTemplate $template, ProcessDto $dto): ProcessDto;

}