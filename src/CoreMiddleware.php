<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\RpcMultiplex;

use Closure;
use Hyperf\HttpMessage\Base\Response;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Protocol;
use Hyperf\RpcMultiplex\Contract\HttpMessageBuilderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CoreMiddleware extends \Hyperf\RpcServer\CoreMiddleware
{
    /**
     * @var HttpMessageBuilderInterface
     */
    protected $responseBuilder;

    /**
     * @var DataFormatterInterface
     */
    protected $dataFormatter;

    public function __construct(ContainerInterface $container, Protocol $protocol, HttpMessageBuilderInterface $builder, string $serverName)
    {
        parent::__construct($container, $protocol, $serverName);

        $this->responseBuilder = $builder;
        $this->dataFormatter = $protocol->getDataFormatter();
    }

    protected function handleFound(Dispatched $dispatched, ServerRequestInterface $request)
    {
        if ($dispatched->handler->callback instanceof Closure) {
            $response = call($dispatched->handler->callback);
        } else {
            [$controller, $action] = $this->prepareHandler($dispatched->handler->callback);
            $controllerInstance = $this->container->get($controller);
            if (! method_exists($controller, $action)) {
                // Route found, but the handler does not exist.
                $data = $this->buildErrorData($request, 500, 'The handler does not exists.');
                return $this->responseBuilder->buildResponse($request, $data);
            }

            try {
                $parameters = $this->parseMethodParameters($controller, $action, $request->getParsedBody());
            } catch (\InvalidArgumentException $exception) {
                $data = $this->buildErrorData($request, 400, 'The params is invalid.');
                return $this->responseBuilder->buildResponse($request, $data);
            }

            try {
                $response = $controllerInstance->{$action}(...$parameters);
            } catch (\Throwable $exception) {
                $data = $this->buildErrorData($request, 500, $exception->getMessage());
                $response = $this->responseBuilder->buildErrorResponse($request, $data);
                $this->responseBuilder->persistToContext($response);

                throw $exception;
            }
        }
        return $this->buildData($request, $response);
    }

    protected function handleNotFound(ServerRequestInterface $request)
    {
        return $this->responseBuilder->buildErrorResponse($request, 404);
    }

    protected function handleMethodNotAllowed(array $routes, ServerRequestInterface $request)
    {
        return $this->handleNotFound($request);
    }

    protected function transferToResponse($response, ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseBuilder->buildResponse($request, $response);
    }

    protected function buildErrorData(ServerRequestInterface $request, int $code, string $message = null): array
    {
        $id = $request->getAttribute(Constant::REQUEST_ID);

        return $this->dataFormatter->formatErrorResponse([$id, $code, $message ?? Response::getReasonPhraseByCode($code)]);
    }

    protected function buildData(ServerRequestInterface $request, $response): array
    {
        $id = $request->getAttribute(Constant::REQUEST_ID);

        return $this->dataFormatter->formatResponse([$id, $response]);
    }
}
