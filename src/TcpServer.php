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
use Hyperf\Contract\PackerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use Hyperf\Rpc\Protocol;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\RpcMultiplex\Contract\HttpMessageBuilderInterface;
use Hyperf\RpcServer\RequestDispatcher;
use Hyperf\RpcServer\Server;
use Hyperf\Server\Exception\InvalidArgumentException;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coroutine;
use Multiplex\Constract\PackerInterface as PacketPacker;
use Multiplex\Packet;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Coroutine\Server\Connection;
use Swoole\Server as SwooleServer;

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

    /**
     * @var PacketPacker
     */
    protected $packetPacker;

    public function __construct(
        ContainerInterface $container,
        RequestDispatcher $dispatcher,
        ExceptionHandlerDispatcher $exceptionDispatcher,
        ProtocolManager $protocolManager,
        StdoutLoggerInterface $logger,
        string $protocol = null
    ) {
        parent::__construct($container, $dispatcher, $exceptionDispatcher, $logger);

        $this->protocolManager = $protocolManager;
        $this->proto = $protocol ?? Constant::PROTOCOL_DEFAULT;
        $this->packetPacker = $container->get(PacketPacker::class);
    }

    public function initCoreMiddleware(string $serverName): void
    {
        $this->initServerConfig($serverName);

        $this->initProtocol();

        parent::initCoreMiddleware($serverName);
    }

    public function onReceive($server, int $fd, int $fromId, string $data): void
    {
        Coroutine::create(function () use ($server, $fd, $fromId, $data) {
            $packet = $this->packetPacker->unpack($data);

            Context::set(Constant::CHANNEL_ID, $packet->getId());

            parent::onReceive($server, $fd, $fromId, $data);
        });
    }

    /**
     * @param Connection|SwooleServer $server
     */
    protected function send($server, int $fd, ResponseInterface $response): void
    {
        $id = Context::get(Constant::CHANNEL_ID, 0);

        $packed = $this->packetPacker->pack(new Packet($id, (string) $response->getBody()));

        if ($server instanceof SwooleServer) {
            $server->send($fd, $packed);
        } elseif ($server instanceof Connection) {
            $server->send($packed);
        }
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
        $this->messageBuilder = make(HttpMessageBuilderInterface::class, [
            'packer' => $this->packer,
        ]);
    }

    protected function initServerConfig(string $serverName): array
    {
        $servers = $this->container->get(ConfigInterface::class)->get('server.servers', []);
        foreach ($servers as $server) {
            if ($server['name'] === $serverName) {
                $assert = Arr::only($server['settings'] ?? [], [
                    'open_length_check',
                    'package_length_type',
                    'package_length_offset',
                    'package_body_offset',
                ]);

                if ($assert != Constant::DEFAULT_SETTINGS) {
                    throw new InvalidArgumentException(sprintf(
                        'Setting of server %s is invalid. Please set settings like %s',
                        $serverName,
                        var_export(Constant::DEFAULT_SETTINGS, true)
                    ));
                }

                return $this->serverConfig = $server;
            }
        }

        throw new InvalidArgumentException(sprintf('Server name %s is invalid.', $serverName));
    }
}
