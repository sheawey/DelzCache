<?php

namespace Delz\Cache\Provider;

use \Redis as PHPRedis;
use Delz\Cache\Contract\ICache;

/**
 * redis缓存
 *
 * 取名CRedis为了避免跟系统的Redis冲突
 *
 * @package Delz\Cache\Provider
 */
class Redis extends Base
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @param PHPRedis $redis
     */
    public function __construct(PHPRedis $redis)
    {
        $this->setRedis($redis);
    }

    /**
     * @return PHPRedis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param PHPRedis $redis
     */
    public function setRedis(PHPRedis $redis)
    {
        $redis->set(PHPRedis::OPT_SERIALIZER, PHPRedis::SERIALIZER_NONE);
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        return $this->redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        return $this->redis->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        if($lifeTime > 0) {
            return $this->redis->setex($id, $lifeTime, $data);
        }

        return $this->redis->set($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->redis->delete($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $info = $this->redis->info();
        return array(
            ICache::STATS_HITS              => $info['keyspace_hits'],
            ICache::STATS_MISSES            => $info['keyspace_misses'],
            ICache::STATS_UPTIME            => $info['uptime_in_seconds'],
            ICache::STATS_MEMORY_USAGE      => $info['used_memory'],
            ICache::STATS_MEMORY_AVAILABLE  => false
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetMulti(array $keys = [])
    {
        $items = array_combine($keys, $this->redis->mget($keys));
        $foundItems = [];
        foreach($items as $k => $v) {
            if(false !== $v || $this->redis->exists($k)) {
                $foundItems[$k] = $v;
            }
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSetMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        //如果有lifeTime,那么就用setex
        if($lifeTime > 0) {
            $result  = true;
            foreach($keysAndValues as $k => $v) {
                if($this->redis->setex($k, $lifeTime, $v)) {
                    $result = false;
                }
            }

            return $result;
        }
        //如果没有lifeTime,那么就用mset
        return (bool) $this->redis->mset($keysAndValues);
    }

}