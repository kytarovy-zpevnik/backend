<?php

namespace Markatom\RestApp\Routing;
use Markatom\RestApp\Naming\Naming;

/**
 * @author	Tomáš Markacz
 */
interface IRoute
{

    /**
     * @return array
     */
    public function getMethods();

    /**
     * @return string
     */
    public function getMask();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @return int|NULL
     */
    public function getSinceVersion();

    /**
     * @return int|NULL
     */
    public function getUntilVersion();

    /**
     * @param Naming $naming
     */
    public function setNaming(Naming $naming);

    /**
     * @return Naming
     */
    public function getNaming();

} 