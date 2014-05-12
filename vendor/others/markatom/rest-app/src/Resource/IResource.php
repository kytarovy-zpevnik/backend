<?php

namespace Markatom\RestApp\Resource;

use Markatom\RestApp\Api\Request;
use Markatom\RestApp\Api\Response;

/**
 * Resource interface.
 * @author	Tomáš Markacz
 */
interface IResource
{

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request);

}
