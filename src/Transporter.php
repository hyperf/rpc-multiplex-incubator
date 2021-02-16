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
use Hyperf\Utils\Collection;
use Multiplex\Socket\Client;

class Transporter implements TransporterInterface
{
    /**
     * @var null|LoadBalancerInterface
     */
    protected $loadBalancer;

    /**
     * @var Collection
     */
    protected $config;

    /**
     * @var null|Client
     */
    protected $client;

    public function __construct(array $config = [])
    {
        $this->config = new Collection(array_replace_recursive($this->getDefaultConfig(), $config));
        $this->client = new Client();
    }

    public function send(string $data)
    {
        // TODO: Implement send() method.
    }

    public function recv()
    {
        // TODO: Implement recv() method.
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->loadBalancer;
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer): TransporterInterface
    {
        $this->loadBalancer = $loadBalancer;
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
