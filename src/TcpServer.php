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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\DispatcherInterface;
use Hyperf\Contract\PackerInterface;
use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use Hyperf\Rpc\Protocol;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\RpcMultiplex\Contract\HttpMessageBuilderInterface;
use Hyperf\RpcServer\Server;
use Hyperf\Server\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class TcpServer extends Server
{
    /**
     * @var ProtocolManager
     */
    protected $protocolManager;

    /**
     * @var HttpMessageBuilderInterface
     */
    protected $messageBuilder;

    /**
     * @var PackerInterface
     */
    protected $packer;

    /**
     * @var array
     */
    protected $serverConfig;

    /**
     * @var string
     */
    protected $proto;

    public function __construct(
        ContainerInterface $container,
        DispatcherInterface $dispatcher,
        ExceptionHandlerDispatcher $exceptionDispatcher,
        ProtocolManager $protocolManager,
        LoggerInterface $logger,
        string $protocol = null
    ) {
        parent::__construct($container, $dispatcher, $exceptionDispatcher, $logger);

        $this->protocolManager = $protocolManager;
        $this->proto = $protocol ?? Constant::PROTOCOL_DEFAULT;
    }

    public function initCoreMiddleware(string $serverName): void
    {
        $this->initServerConfig($serverName);

        $this->initProtocol();

        parent::initCoreMiddleware($serverName);
    }

    protected function createCoreMiddleware(): CoreMiddlewareInterface
    {
        return new CoreMiddleware($this->container, $this->protocol, $this->messageBuilder, $this->serverName);
    }

    protected function buildRequest(int $fd, int $fromId, string $data): ServerRequestInterface
    {
        $parsed = $this->packer->unpack($data);

        $request = $this->messageBuilder->buildRequest($parsed);

        return $request->withAttribute('fd', $fd)->withAttribute('request_id', $parsed['id'] ?? null);
    }

    protected function buildResponse(int $fd, $server): ResponseInterface
    {
        return (new Response())->withAttribute('fd', $fd)->withAttribute('server', $server);
    }

    protected function initProtocol()
    {
        $this->protocol = new Protocol($this->container, $this->protocolManager, $this->proto, $this->serverConfig);
        $this->packer = $this->protocol->getPacker();
        $this->messageBuilder = make(HttpMessageBuilder::class, [
            'packer' => $this->packer,
        ]);
    }

    protected function initServerConfig(string $serverName): array
    {
        $servers = $this->container->get(ConfigInterface::class)->get('server.servers', []);
        foreach ($servers as $server) {
            if ($server['name'] === $serverName) {
                return $this->serverConfig = $server;
            }
        }

        throw new InvalidArgumentException(sprintf('Server name %s is invalid.', $serverName));
    }
}
