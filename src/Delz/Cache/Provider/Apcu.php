<?php

namespace Delz\Cache\Provider;

use Delz\Cache\Contract\ICache;

/**
 * Apcu缓存
 *
 * apc的用户缓存
 *
 * @package Delz\Cache\Provider
 */
class Apcu extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        return apcu_fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        return apcu_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        return apcu_store($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        //apc删除1个不存在的，会返回false，不存在的等于是删除，也应该返回true
        return apcu_delete($id) || !apcu_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return apcu_clear_cache() && apcu_clear_cache('user');
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $cacheInfo = apcu_cache_info();
        $smaInfo = apcu_sma_info();
        return array(
            ICache::STATS_HITS              => $cacheInfo['num_hits'],
            ICache::STATS_MISSES            => $cacheInfo['num_misses'],
            ICache::STATS_UPTIME            => $cacheInfo['start_time'],
            ICache::STATS_MEMORY_USAGE      => $cacheInfo['mem_size'],
            ICache::STATS_MEMORY_AVAILABLE  => $smaInfo['avail_mem'],
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetMulti(array $keys = [])
    {
        return apcu_fetch($keys) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSetMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        $result = apcu_store($keysAndValues, null, $lifeTime);

        return empty($result);
    }
}