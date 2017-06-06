<?php

namespace Delz\Cache\Provider;

use \Memcache as PHPMemcache;
use Delz\Cache\Contract\ICache;

/**
 * Memcache缓存
 *
 * @package Delz\Cache\Provider
 */
class Memcache extends Base
{
    /**
     * @var PHPMemcache
     */
    private $memcache;

    /**
     * @param PHPMemcache $memcache
     */
    public function __construct(PHPMemcache $memcache)
    {
        $this->setMemcache($memcache);
    }

    /**
     * @return PHPMemcache
     */
    public function getMemcache()
    {
        return $this->memcache;
    }

    /**
     * @param PHPMemcache $memcache
     */
    public function setMemcache(PHPMemcache $memcache)
    {
        $this->memcache = $memcache;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        return $this->memcache->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        $flag = null;
        $this->memcache->get($id, $flag);
        return $flag !== null;
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

        return $this->memcache->set($id, $data, 0, (int)$lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        // memcache如果删除不存在的$id，会返回false，我们要他返回true
        return $this->memcache->delete($id) || !$this->doHas($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->memcache->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $stats = $this->memcache->getStats();
        return array(
            ICache::STATS_HITS              => $stats['get_hits'],
            ICache::STATS_MISSES            => $stats['get_misses'],
            ICache::STATS_UPTIME            => $stats['uptime'],
            ICache::STATS_MEMORY_USAGE      => $stats['bytes'],
            ICache::STATS_MEMORY_AVAILABLE  => $stats['limit_maxbytes'],
        );
    }


}