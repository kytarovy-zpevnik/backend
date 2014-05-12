<?php

namespace Markatom\RestApp\Resource;

/**
 * @todo	Fill desc.
 * @author	Tomáš Markacz
 */
interface IResourceFactory
{

    /**
     * @param string $api
     * @param string $resource
     * @param int $version
     * @return IResource
     */
    public function create($api, $resource, $version = NULL);

} 