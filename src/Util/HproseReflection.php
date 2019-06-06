<?php
namespace Imi\Hprose\Util;

use Imi\Bean\Annotation\Bean;
use Hprose\Swoole\Socket\Client as SwooleSocketClient;
use Hprose\Socket\Client as SocketClient;

/**
 * @Bean("HproseReflection")
 */
class HproseReflection
{
    /**
     * 属性反射列表
     *
     * @var \ReflectionProperty[]
     */
    private $propertyReflections = [];

    /**
     * 获取对象属性
     *
     * @param mixed $object
     * @param string $propertyName
     * @return mixed
     */
    public function getObjectProperty($object, $propertyName)
    {
        $class = get_class($object);
        if(!isset($this->propertyReflections[$class][$propertyName]))
        {
            $this->propertyReflections[$class][$propertyName] = $refProperty = new \ReflectionProperty($class, $propertyName);
            $refProperty->setAccessible(true);
        }
        return $this->propertyReflections[$class][$propertyName]->getValue($object);
    }

}