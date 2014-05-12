<?php

namespace Markatom\RestApp\Naming\Convention;

use Markatom\RestApp\Naming\IConvention;
use Nette\Object;

/**
 * @author	Tomáš Markacz
 */
class DashCase extends Object implements IConvention
{

    public function getName()
    {
        return 'dash-case';
    }

    /**
     * @param string $string
     * @return string[]
     */
    public function parse($string)
    {
        return explode('-', $string);
    }

}
