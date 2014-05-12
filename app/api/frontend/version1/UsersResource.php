<?php

namespace FrontendApi\Version1;

use Markatom\RestApp\Api\Response;
use Markatom\RestApp\Resource\Resource;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
class UsersResource extends Resource
{

	public function read($id)
    {
        return Response::data(['id' => $id]);
    }

} 