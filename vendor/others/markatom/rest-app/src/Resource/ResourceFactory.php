<?php

namespace Markatom\RestApp\Resource;

use Markatom\RestApp\Routing\RouterException;
use Markatom\RestApp\Routing\VersionRequiredException;
use Nette\Object;
use Utils\AutoWireFactory;
use Utils\ClassMapper;

/**
 * @author	Tomáš Markacz
 */
class ResourceFactory extends Object implements IResourceFactory
{

    /** @var \Utils\AutoWireFactory */
    private $autoWireFactory;

    /** @var string */
    private $mapping;

    /**
     * @param string $mapping
     * @param \Utils\AutoWireFactory $autoWireFactory
     */
    public function __construct($mapping, AutoWireFactory $autoWireFactory)
    {
        $this->mapping         = $mapping;
        $this->autoWireFactory = $autoWireFactory;
    }

    /**
     * @param string $api
     * @param string $resource
     * @param int|NULL $version
     * @throws \InvalidArgumentException
     * @return IResource
     */
    public function create($api, $resource, $version = NULL)
    {
        switch (substr_count($this->mapping, '*')) {
            case 2:
                $replacements = [$api, $resource];
                break;

            case 3:
                if ($version === NULL) {
                    throw self::versionRequiredException($api);
                }
                $replacements = [$api, $version, $resource];
                break;

            default:
                throw self::invalidMappingMaskException();
        }

        $class = ClassMapper::getClass($this->mapping, $replacements);

        return $this->autoWireFactory->create($class);
    }

    /**
     * @return \InvalidArgumentException
     */
    private static function invalidMappingMaskException()
    {
        return new \InvalidArgumentException('Invalid count of placeholders in mapping mask. Use 3 placeholders for versioned api or 2 placeholders for unversioned api.');
    }

    /**
     * @param $api
     * @return VersionRequiredException
     */
    private static function versionRequiredException($api)
    {
        return new VersionRequiredException("Version required for api $api.");
    }

}