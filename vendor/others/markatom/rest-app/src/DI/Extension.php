<?php

namespace Markatom\RestApp\DI;

use Nette\DI\CompilerExtension;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class Extension extends CompilerExtension
{

    private static $defaults = [
        'resource' => [
            'mapping' => '*Api\Version*\*Resource'
        ],
        'naming' => [
            'resource'   => 'plural',
            'convention' => 'dash-case'
        ],
        'routes' => [
            'autoGenerated' => FALSE
        ]
    ];

	public function loadConfiguration()
    {
        $config  = $this->getConfig(self::$defaults);
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('inflector'))
            ->setClass('Doctrine\Common\Inflector\Inflector');

        $builder->addDefinition($this->prefix('autoWireFactory'))
            ->setClass('Utils\AutoWireFactory');

        $builder->addDefinition($this->prefix('dashCase'))
            ->setClass('Markatom\RestApp\Naming\Convention\DashCase');

        $builder->addDefinition($this->prefix('namingFactory'))
            ->setClass('Markatom\RestApp\Naming\NamingFactory');

        $builder->addDefinition($this->prefix('naming'))
            ->setClass('Markatom\RestApp\Naming\Naming')
            ->setFactory($this->prefix('@namingFactory::create'), [$config['naming']['convention']]);

        if (!$builder->getByType('Markatom\RestApp\Resource\IResourceFactory')) {
            $builder->addDefinition($this->prefix('resourceFactory'))
                ->setClass('Markatom\RestApp\Resource\ResourceFactory', [$config['resource']['mapping']]);
        }

        $builder->addDefinition($this->prefix('application'))
            ->setClass('Markatom\RestApp\Application');
    }

} 