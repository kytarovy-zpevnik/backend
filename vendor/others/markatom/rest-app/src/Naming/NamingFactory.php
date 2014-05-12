<?php

namespace Markatom\RestApp\Naming;

use Doctrine\Common\Inflector\Inflector;
use Nette\DI\Container;
use Nette\Object;

/**
 * Naming service factory.
 * Creates service with naming convention from config.
 * @author	Tomáš Markacz
 */
class NamingFactory extends Object
{

    /** @var \Nette\DI\Container */
    private $dic;

    /** @var \Doctrine\Common\Inflector\Inflector */
    private $inflector;

    /**
     * @param Container $dic
     * @param Inflector $inflector
     */
    public function __construct(Container $dic, Inflector $inflector)
    {
        $this->dic       = $dic;
        $this->inflector = $inflector;
    }

    /**
     * @param string $namingConvention
     * @throws \InvalidArgumentException
     * @return \Markatom\RestApp\Naming\Naming
     */
    public function create($namingConvention)
    {
        $names = $this->dic->findByType('Markatom\RestApp\Naming\IConvention');

        foreach ($names as $name) {
            /** @var IConvention $service */
            $service = $this->dic->getService($name);
            if ($service->getName() === $namingConvention) {
                return new Naming($service, $this->inflector);
            }
        }

        throw new \InvalidArgumentException("Invalid convention name $namingConvention, corresponding service not found.");
    }

}