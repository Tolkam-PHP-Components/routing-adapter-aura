<?php declare(strict_types=1);

namespace Tolkam\Routing\Adapter\Aura\Adapter\Aura;

use Aura\Router\Route;
use Aura\Router\RouterContainer;
use Aura\Router\Rule\Accepts;
use Aura\Router\Rule\Allows;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Tolkam\Routing\Exception\NotAcceptedException;
use Tolkam\Routing\Exception\NotAllowedException;
use Tolkam\Routing\Exception\NotFoundException;
use Tolkam\Routing\RouteInterface;
use Tolkam\Routing\RouterAdapterInterface;

class AuraRouterAdapter implements RouterAdapterInterface
{
    /**
     * @param RouterContainer $routerContainer
     */
    public function __construct(private readonly RouterContainer $routerContainer)
    {
    }

    /**
     * @inheritDoc
     */
    public function getRoutes(): array
    {
        $original = $this->routerContainer->getMap()->getRoutes();
        $routes = [];
        foreach ($original as $route) {
            $routes[] = new AuraRoute($route);
        }

        return $routes;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(string $name = null): RouteInterface
    {
        if ($name) {
            return new AuraRoute($this->routerContainer->getMap()->getRoute($name));
        }

        $matcher = $this->routerContainer->getMatcher();

        /** @var Route $route */
        if ($route = $matcher->getMatchedRoute()) {
            return new AuraRoute($route);
        }

        /** @var Route $route */
        if ($route = $matcher->getFailedRoute()) {
            return new AuraRoute($route);
        }

        throw new RuntimeException('Failed to get current route');
    }

    /**
     * @inheritDoc
     */
    public function match(ServerRequestInterface $request): RouteInterface
    {
        $matcher = $this->routerContainer->getMatcher();

        if (!$route = $matcher->match($request)) {
            $failed = $matcher->getFailedRoute();
            $uri = $request->getUri();

            switch ($failed->failedRule) {

                case Allows::class:
                    $e = new NotAllowedException(sprintf(
                        'Method "%s" is not allowed for "%s"',
                        $request->getMethod(),
                        $uri
                    ));
                    $e->setAllowed($failed->allows);
                    throw $e;

                case Accepts::class:
                    throw new NotAcceptedException(sprintf(
                        '"%s" is not able to respond with the accepted content type "%s"',
                        $uri,
                        $request->getHeaderLine('Accept')
                    ));
            }

            throw new NotFoundException(sprintf(
                'No matching rule found for "%s"',
                $uri
            ));
        }

        return new AuraRoute($route);
    }

    /**
     * @inheritDoc
     */
    public function generate(string $name, array $parameters = [], array $options = []): string
    {
        $raw = $options['raw'] ?? false;
        $methodName = 'generate' . ($raw ? 'Raw' : '');

        // convert objects to strings
        $attributes = array_map(
            fn($v) => is_object($v) ? (string) $v : $v,
            $parameters
        );

        $generated = $this->routerContainer
            ->getGenerator()
            ->$methodName($name, $attributes);

        // remove trailing slashes for sub-pages
        $sep = '/';
        if ($generated !== $sep) {
            $generated = rtrim($generated, $sep);
        }

        return $generated;
    }
}
