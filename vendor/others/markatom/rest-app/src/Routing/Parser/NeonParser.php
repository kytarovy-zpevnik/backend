<?php

namespace Markatom\RestApp\Routing\Parser;

use Nette\Object;
use Nette\Utils\Neon;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class NeonParser extends Object
{

    /** @var ArrayParser */
    private $arrayParser;

    /**
     * @param ArrayParser $arrayParser
     */
    public function __construct(ArrayParser $arrayParser)
    {
        $this->arrayParser = $arrayParser;
    }

    /**
     * @param array $neonFiles
     * @return string
     */
    public function generateRouteList(array $neonFiles)
    {
        $neon = implode("\n", array_map(function ($path) {
            return file_get_contents($path);
        }, $neonFiles));
        return $this->arrayParser->generateRouteList(Neon::decode($neon));
    }

}
