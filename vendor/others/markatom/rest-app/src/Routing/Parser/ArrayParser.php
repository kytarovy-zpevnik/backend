<?php

namespace Markatom\RestApp\Routing\Parser;

use Nette\Caching\Storages\FileStorage;
use Nette\Object;
use Nette\PhpGenerator\ClassType;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class ArrayParser extends Object
{

    /**
     * @param array $array
     * @return string
     */
    public function generateRouteList(array $array)
    {
        $definitions = '';
        foreach ($array as $apiName => $apis) {
            foreach ($apis as $apiVersion => $version) {
                foreach ($version as $resource => $routes) {
                    foreach ($routes as $handler => $maskWithMethod) {
                        list($method, $mask) = explode(' ', $maskWithMethod, 2);
                        $definitions .= '$this->addRoute("'
                            . implode("', '", [$method, $mask, $apiName, $apiVersion, $resource, $handler])
                            . "\");\n";
                    }
                }
            }
        }

        $class = new ClassType('_RouteList');
        $class->setExtends('\Markatom\RestApp\Routing\RouteList');
        $class->addMethod('__construct')->setBody($definitions);

        $content = "<?php\n\n"
            . "namespace _Cache;\n\n"
            . "/**\n"
            . " * Automatically generated route list class.\n"
            . " */\n"
            . "$class\n";

        file_put_contents()
    }

}