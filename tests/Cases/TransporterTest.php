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
namespace HyperfTest\RpcMultiplex\Cases;

use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\RpcMultiplex\Transporter;
use Hyperf\Utils\Reflection\ClassInvoker;
use HyperfTest\RpcMultiplex\Stub\ContainerStub;
use Mockery;

/**
 * @internal
 * @coversNothing
 */
class TransporterTest extends AbstractTestCase
{
    public function testGetLoadBalancer()
    {
        $container = ContainerStub::mockContainer();

        $transporter = new Transporter($container);

        $transporter->setLoadBalancer($balancer = Mockery::mock(LoadBalancerInterface::class));
        $this->assertSame($balancer, $transporter->getLoadBalancer());
    }

    public function testConfig()
    {
        $container = ContainerStub::mockContainer();

        $transporter = new Transporter($container, [
            'connect_timeout' => $timeout = rand(50, 100),
        ]);

        $invoker = new ClassInvoker($transporter);
        $this->assertSame($timeout, $invoker->config['connect_timeout']);

        $factory = new ClassInvoker($invoker->factory);
        $this->assertSame($timeout, $factory->config['connect_timeout']);
    }
}
