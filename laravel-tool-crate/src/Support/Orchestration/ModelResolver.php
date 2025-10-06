<?php

namespace HollisLabs\ToolCrate\Support\Orchestration;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ModelResolver
{
    /**
     * Resolve a configured Eloquent model class name with sensible defaults.
     *
     * @template TModel of Model
     *
     * @param  string  $key
     * @param  string|null  $default
     * @return class-string<TModel>
     */
    public static function resolve(string $key, ?string $default = null): string
    {
        $configured = config('tool-crate.orchestration.' . $key);

        if (is_string($configured) && $configured !== '') {
            if (! class_exists($configured)) {
                throw new InvalidArgumentException(sprintf(
                    'Configured orchestration model [%s] does not exist.',
                    $configured
                ));
            }

            return $configured;
        }

        if ($default && class_exists($default)) {
            return $default;
        }

        throw new InvalidArgumentException(sprintf(
            'Unable to resolve orchestration model for key [%s]; configure tool-crate.orchestration.%s.',
            $key,
            $key
        ));
    }

    /**
     * Resolve a service from the container if configured.
     *
     * @template TService of object
     *
     * @param  string  $key
     * @param  class-string<TService>|null  $default
     * @return TService
     */
    public static function resolveService(string $key, ?string $default = null): object
    {
        $class = config('tool-crate.orchestration.' . $key, $default);

        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve orchestration service for key [%s]; configure tool-crate.orchestration.%s.',
                $key,
                $key
            ));
        }

        if (! class_exists($class)) {
            throw new InvalidArgumentException(sprintf(
                'Configured orchestration service [%s] does not exist.',
                $class
            ));
        }

        try {
            return app($class);
        } catch (BindingResolutionException $e) {
            throw new InvalidArgumentException(sprintf(
                'Failed to resolve orchestration service [%s]: %s',
                $class,
                $e->getMessage()
            ), previous: $e);
        }
    }
}
