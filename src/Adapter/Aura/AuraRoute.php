<?php declare(strict_types=1);

namespace Tolkam\Routing\Adapter\Aura\Adapter\Aura;

use Aura\Router\Route;
use Tolkam\Routing\RouteInterface;

class AuraRoute implements RouteInterface
{
    /**
     * @param Route $route
     */
    public function __construct(private readonly Route $route)
    {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->route->name;
    }

    /**
     * @inheritDoc
     */
    public function getHandler(): mixed
    {
        return $this->route->handler;
    }

    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        return $this->route->extras['middlewares'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->route->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getOriginalRoute(): Route
    {
        return $this->route;
    }
}
