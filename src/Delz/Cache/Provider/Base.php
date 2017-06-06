<?php

namespace Delz\Cache\Provider;

use Delz\Cache\Contract\ICache;

/**
 * 缓存抽象类
 *
 * @package Delz\Cache\Provider
 */
abstract class Base implements ICache
{
    /**
     * 缓存命名空间
     *
     * 可区分不同项目缓存主键，避免不同项目用同一个缓存系统引起冲突
     *
     * @var string
     */
    private $namespace;

    /**
     * 版本号
     *
     * 如果要删除某个命令空间的缓存，通过修改版本号的方式
     *
     * @var int|null
     */
    private $namespaceVersion;

    /**
     * 命令空间版本号在缓存中的键值
     *
     * 在缓存中保存的实际键值是$namespace@version字符串的拼接,%s是用sprintf的占位符
     *
     * @var string
     */
    const NAMESPACE_VERSION_KEY = '%s@version';

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * 设置缓存命令空间
     *
     * 重新设置缓存命名空间，等于是同一个实例对象切换命名空间，
     * 为了避免版本号的冲突问题，清空版本号，版本号获取由getNamespaceVersion()方法获取
     *
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = (string)$namespace;
        $this->namespaceVersion = null;
    }

    /**
     * @return int
     */
    public function getNamespaceVersion()
    {
        if (null !== $this->namespaceVersion) {
            return $this->namespaceVersion;
        }
        $this->namespaceVersion = $this->doGet($this->getNamespaceVersionKey()) ?: 1;
        return $this->namespaceVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->doGet($this->getNamespaceId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->doHas($this->getNamespaceId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function set($id, $data, $lifeTime = 0)
    {
        return $this->doSet($this->getNamespaceId($id), $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->doDelete($this->getNamespaceId($id));
    }

    /**
     * {@inheritdoc}
     */
    public function stats()
    {
        return $this->doStats();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $namespaceVersionKey = $this->getNamespaceVersionKey();
        $namespaceVersion = $this->getNamespaceVersion() + 1;
        if ($this->doSet($namespaceVersionKey, $namespaceVersion)) {
            $this->namespaceVersion = $namespaceVersion;
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->doFlush();
    }

    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys = [])
    {
        if (empty($keys)) {
            return [];
        }
        //生成一个原有key和经过getNamespaceId()处理的key的map
        $namespacedKeys = array_combine($keys, array_map([$this, 'getNamespaceId'], $keys));
        $items = $this->doGetMulti($namespacedKeys);
        //最后返回的值数组
        $findItems = [];

        foreach ($namespacedKeys as $k => $v) {
            // 注意isset和array_key_exists的区别，isset如果数组值为null，会返回false
            // 一般来说直接array_key_exists就好了，但是数据量大的时候，isset性能比array_key_exists高
            // if语句会先判断第一个语句，如果通过，则不会判断第二个条件。
            if (isset($items[$k]) || array_key_exists($k, $items)) {
                $findItems[$k] = $items[$v];
            }
        }

        return $findItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        //将$keysAndValues用getNamespaceId()处理
        $namespacedKeysAndValues = [];
        foreach($keysAndValues as $k=>$v) {
            $namespacedKeysAndValues[$this->getNamespaceId($k)] = $v;
        }

        return $this->doSetMulti($namespacedKeysAndValues, $lifeTime);
    }


    /**
     * 批量获取缓存
     *
     * 默认采取循环调用doGet
     *
     * 如果缓存系统自带批量获取方法，性能肯定比默认方法高，请重写覆盖本方法
     *
     * @param array $keys 多个缓存id的数组
     * @return mixed[] 根据$key数组返回相应的缓存值数组
     */
    protected function doGetMulti(array $keys = [])
    {
        $returnValues = [];
        foreach($keys as $key) {
            //why 还要执行一次doHas()?因为doGet()一般来说失败返回false，但并不绝对
            if(false !== $this->doGet($key) || $this->doHas($key)) {
                $returnValues[$key] = $this->doGet($key);
            }
        }

        return $returnValues;
    }

    /**
     * 批量设置缓存
     *
     * 默认采取循环调用doSet
     *
     * 如果缓存系统自带批量设置方法，性能肯定比默认方法高，请重写覆盖本方法
     *
     * @param array $keysAndValues 缓存id和值数组，如['a'=>1,'b'=>2],其中a、b是缓存id，1、2是对应的值
     * @param int $lifeTime 缓存时间，单位为秒，如果设置为0，表示永不过期（当然这不是绝对的，如果内存满了，系统会启用LRU\FIFO等策略删除）
     * @return bool 成功返回true，否则返回false
     */
    protected function doSetMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        $result = true;

        foreach($keysAndValues as $key=>$value) {
            if(!$this->doSet($key, $value, $lifeTime)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * 获取缓存中真正保存的缓存键值
     *
     * @param string $id 缓存Id
     * @return string 缓存中真正保存的键值
     */
    private function getNamespaceId($id)
    {
        $namespaceVersion = $this->getNamespaceVersion();
        return sprintf('%s@%s@%s', $this->namespace, $id, $namespaceVersion);
    }

    /**
     * 获取缓存版本号在缓存中保存的key值
     *
     * @return string
     */
    private function getNamespaceVersionKey()
    {
        return sprintf(self::NAMESPACE_VERSION_KEY, $this->namespace);
    }

    /**
     * 直接从缓存获取$id的值
     *
     * @param string $id 缓存id
     * @return mixed
     */
    abstract protected function doGet($id);

    /**
     * 缓存中是否存在$id
     *
     * @param string $id 缓存id
     * @return bool 存在返回true，否则返回false
     */
    abstract protected function doHas($id);

    /**
     * 写入缓存
     *
     * @param string $id 缓存id
     * @param mixed $data 缓存值
     * @param int $lifeTime 缓存时间，单位为秒，如果设置为0，表示永不过期（当然这不是绝对的，如果内存满了，系统会启用LRU\FIFO等策略删除）
     * @return bool 写入成功，返回true，否则返回false
     */
    abstract protected function doSet($id, $data, $lifeTime = 0);

    /**
     * 删除指定缓存
     *
     * @param string $id 缓存id
     * @return bool 删除成功返回true，否则返回false
     */
    abstract protected function doDelete($id);

    /**
     * 清空所有缓存
     *
     * @return bool 成功返回true，否则返回false
     */
    abstract protected function doFlush();

    /**
     * 获取缓存服务器信息
     *
     * @return array|null 有信息返回数组，没有信息返回null
     */
    abstract protected function doStats();
}