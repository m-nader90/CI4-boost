<?php

declare(strict_types=1);

namespace CodeIgniter\Boost;

if (! function_exists('boost')) {
    function boost(?string $key = null): mixed
    {
        $instance = BoostManager::instance();

        if ($key !== null) {
            return $instance->get($key);
        }

        return $instance;
    }
}

function boost_register_agent(string $name, string $class): void
{
    BoostManager::registerAgent($name, $class);
}

function boost_path(?string $path = null): string
{
    $base = dirname(__DIR__);

    if ($path !== null) {
        return $base . '/' . $path;
    }

    return $base;
}

function boost_resource_path(?string $path = null): string
{
    return boost_path('resources' . ($path ? '/' . $path : ''));
}
