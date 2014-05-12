<?php

namespace Markatom\RestApp\Naming;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
interface IConvention
{

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $string
     * @return string[]
     */
    public function parse($string);

} 