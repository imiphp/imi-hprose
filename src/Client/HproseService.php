<?php
namespace Imi\Hprose\Client;

use Imi\Rpc\Client\IService;
use Imi\Rpc\Client\IRpcClient;

class HproseService implements IService
{
    /**
     * 客户端
     *
     * @var \Imi\Rpc\Client\IRpcClient
     */
    protected $client;

    /**
     * 服务名称
     *
     * @var string
     */
    protected $name;

    public function __construct(IRpcClient $client, $name)
    {
        $this->client = $client;
        $this->name = $name;
    }

    /**
     * 获取服务名称
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 调用服务
     *
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function call($method, $args = [])
    {
        return $this->client->getInstance()->{$this->name}->$method(...$args);
    }

    /**
     * 魔术方法
     *
     * @param string $name 方法名
     * @param string $arguments 参数
     * @return void
     */
    public function __call($name, $arguments)
    {
        return $this->call($name, $arguments);
    }

    /**
     * 获取客户端对象
     *
     * @return \Imi\Rpc\Client\IRpcClient
     */
    public function getClient(): IRpcClient
    {
        return $this->client;
    }

}