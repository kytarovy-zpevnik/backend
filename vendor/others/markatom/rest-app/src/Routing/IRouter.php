<?php

namespace Markatom\RestApp\Routing;

use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Naming\Naming;
use Nette\Http\IRequest;

/**
 * Api router interface
 * @author	Tomáš Markacz
 */
interface IRouter
{

    /**
     * @param IRequest $httpRequest
     * @return Request
     */
    public function getApiRequest(IRequest $httpRequest);

    /**
     * @param Naming $naming
     */
    public function setNaming(Naming $naming);

    /**
     * @return Naming
     */
    public function getNaming();

} 