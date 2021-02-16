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

use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\RpcMultiplex\Exception\NotSupportException;
use Hyperf\Utils\Collection;

class Transporter implements TransporterInterface
{
    /**
     * @var Collection
     */
    protected $config;

    /**
     * @var null|Socket
     */
    protected $socket;

    public function __construct(array $config = [])
    {
        $this->config = new Collection(array_replace_recursive($this->getDefaultConfig(), $config));
        $this->socket = make(Socket::class);
    }

    public function send(string $data)
    {
        return $this->socket->request($data);
    }

    public function recv()
    {
        throw new NotSupportException('Recv is not supported.');
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->socket->getLoadBalancer();
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer): TransporterInterface
    {
        $this->socket->setLoadBalancer($loadBalancer);
        return $this;
    }

    protected function getDefaultConfig(): array
    {
        return [
            'connect_timeout' => 5.0,
            'settings' => [
                'package_max_length' => 1024 * 1024 * 2,
            ],
            'recv_timeout' => 5.0,
            'retry_count' => 2,
            'retry_interval' => 100,
        ];
    }
}
