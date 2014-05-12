<?php

namespace Markatom\RestApp\Naming;

use Doctrine\Common\Inflector\Inflector;
use Nette\Object;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 *
 * @property-read IConvention $convention
 * @property-read Inflector $inflector
 */
class Naming extends Object
{

    /** @var IConvention */
    private $convention;

    /** @var \Doctrine\Common\Inflector\Inflector */
    private $inflector;

    /**
     * @param IConvention $convention
     * @param Inflector $inflector
     */
    public function __construct(IConvention $convention, Inflector $inflector)
    {
        $this->convention = $convention;
        $this->inflector = $inflector;
    }

    /**
     * @return \Markatom\RestApp\Naming\IConvention
     */
    public function getConvention()
    {
        return $this->convention;
    }

    /**
     * @return \Doctrine\Common\Inflector\Inflector
     */
    public function getInflector()
    {
        return $this->inflector;
    }

    /**
     * @param array $words
     * @return string
     */
    public function pascalize(array $words)
    {
        return implode('', array_map(function ($word) {
            return ucfirst($word);
        }, $words));
    }

}