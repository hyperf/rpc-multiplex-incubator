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
namespace HyperfTest\Stub;

use Hyperf\Di\Container;
use Hyperf\RpcMultiplex\Socket;
use Hyperf\RpcMultiplex\SocketFactory;
use Hyperf\Utils\ApplicationContext;
use Mockery;
use Multiplex\Constract\IdGeneratorInterface;
use Multiplex\Constract\PackerInterface;
use Multiplex\Constract\SerializerInterface;
use Multiplex\IdGenerator;
use Multiplex\Packer;
use Multiplex\Serializer\StringSerializer;

class ContainerStub
{
    public static function mockContainer(): Container
    {
        $container = Mockery::mock(Container::class);
        ApplicationContext::setContainer($container);
        $container->shouldReceive('get')->with(PackerInterface::class)->andReturn(new Packer());
        $container->shouldReceive('make')->with(Socket::class, Mockery::any())->andReturnUsing(function () use ($container) {
            return new Socket($container);
        });
        $container->shouldReceive('get')->with(IdGeneratorInterface::class)->andReturn(new IdGenerator());
        $container->shouldReceive('get')->with(SerializerInterface::class)->andReturn(new StringSerializer());
        $container->shouldReceive('get')->with(PackerInterface::class)->andReturn(new Packer());
        $container->shouldReceive('make')->with(SocketFactory::class, Mockery::any())->andReturnUsing(function ($_, $args) use ($container) {
            return new SocketFactory($container, ...array_values($args));
        });
        return $container;
    }
}
