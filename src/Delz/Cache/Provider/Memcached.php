<?php

namespace Delz\Cache\Provider;

use \Memcached as PHPMemcached;
use Delz\Cache\Contract\ICache;

/**
 * Memcached缓存
 *
 * @package Delz\Cache\Provider
 */
class Memcached extends Base
{
    /**
     * @var PHPMemcached
     */
    private $memcached;

    /**
     * @param PHPMemcached $memcached
     */
    public function __construct(PHPMemcached $memcached)
    {
        $this->setMemcached($memcached);
    }

    /**
     * @return PHPMemcached
     */
    public function getMemcached()
    {
        return $this->memcached;
    }

    /**
     * @param PHPMemcached $memcached
     */
    public function setMemcached(PHPMemcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        return $this->memcached->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        return false !== $this->memcached->get($id)
        || $this->memcached->getResultCode() !== PHPMemcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        //超过30天直接用unix时间戳
        if($lifeTime > 2592000 ) {
            $lifeTime = time() + $lifeTime;
        }

        return $this->memcached->set($id, $data, (int)$lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        // memcache如果删除不存在的$id，会返回false，我们要他返回true
        return $this->memcached->delete($id) || $this->memcached->getResultCode() === PHPMemcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $stats   = $this->memcached->getStats();
        $servers = $this->memcached->getServerList();
        $key     = $servers[0]['host'] . ':' . $servers[0]['port'];
        $stats   = $stats[$key];
        return array(
            ICache::STATS_HITS              => $stats['get_hits'],
            ICache::STATS_MISSES            => $stats['get_misses'],
            ICache::STATS_UPTIME            => $stats['uptime'],
            ICache::STATS_MEMORY_USAGE      => $stats['bytes'],
            ICache::STATS_MEMORY_AVAILABLE  => $stats['limit_maxbytes'],
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetMulti(array $keys = [])
    {
        return $this->memcached->getMulti($keys) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSetMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        //超过30天直接用unix时间戳
        if($lifeTime > 2592000 ) {
            $lifeTime = time() + $lifeTime;
        }

        return $this->memcached->setMulti($keysAndValues, $lifeTime);
    }


}