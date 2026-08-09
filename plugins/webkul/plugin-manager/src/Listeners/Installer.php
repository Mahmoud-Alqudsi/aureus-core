<?php

namespace Webkul\PluginManager\Listeners;

use Exception;
use GuzzleHttp\Client;
use Webkul\Security\Models\User;

class Installer
{
    /**
     * Api endpoint
     *
     * @var string
     */
    protected const API_ENDPOINT = 'https://updates.aureuserp.com/api/updates';

    /**
     * After Krayin is successfully installed
     *
     * @return void
     */
    public function installed(): void
    {
        return;
    }
}
