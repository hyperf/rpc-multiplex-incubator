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
namespace HyperfTest\Cases;

use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\LoadBalancer\Node;
use Hyperf\RpcMultiplex\Socket;
use Hyperf\RpcMultiplex\SocketFactory;
use Hyperf\Utils\Reflection\ClassInvoker;
use HyperfTest\Stub\ContainerStub;

/**
 * @internal
 * @coversNothing
 */
class SocketFactoryTest extends AbstractTestCase
{
    public function testSocketConfig()
    {
        $container = ContainerStub::mockContainer();

        $factory = new SocketFactory($container, [
            'connect_timeout' => $connectTimeout = rand(5, 10),
            'settings' => [
                'package_max_length' => $lenght = rand(1000, 9999),
            ],
            'recv_timeout' => $recvTimeout = rand(5, 10),
            'retry_count' => 2,
            'retry_interval' => 100,
            'client_count' => 4,
        ]);

        $factory->setLoadBalancer($balancer = \Mockery::mock(LoadBalancerInterface::class));
        $balancer->shouldReceive('getNodes')->andReturn([
            new Node('192.168.0.1', 9501),
            new Node('192.168.0.2', 9501),
        ]);

        $factory->refresh();

        $this->assertSame($connectTimeout, (new ClassInvoker($factory))->config['connect_timeout']);

        $clients = (new ClassInvoker($factory))->clients;
        $this->assertSame(4, count($clients));

        /** @var Socket $client */
        $client = $clients[0];
        $invoker = new ClassInvoker($client);
        $this->assertSame(9501, $invoker->port);
        $this->assertSame($lenght, $invoker->config->get('package_max_length'));
        $this->assertSame($connectTimeout, $invoker->config->get('connect_timeout'));
        $this->assertSame($recvTimeout, $invoker->config->get('recv_timeout'));
    }
}
