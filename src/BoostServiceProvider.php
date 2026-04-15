<?php

declare(strict_types=1);

namespace CodeIgniter\Boost;

use CodeIgniter\Boost\Commands\BoostInstall;
use CodeIgniter\Boost\Commands\BoostMcp;
use CodeIgniter\Boost\Commands\BoostUpdate;
use CodeIgniter\Boost\Config\Boost as BoostConfig;
use CodeIgniter\Config\BaseService;

class BoostServiceProvider extends BaseService
{
    public static function boostManager(): BoostManager
    {
        return BoostManager::instance();
    }
}
