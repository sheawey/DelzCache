<?php

namespace Delz\Cache\Provider;

use Delz\Cache\Contract\ICache;

/**
 * apc用户缓存
 *
 * php一般系统缓存用的是opcache，apc一般用作用户缓存，新版是应该是apcu
 *
 * 为了兼容apc原有函数，可以安装apcu_bc, 使用apc原有函数操作apcu
 *
 * @package Delz\Cache\Provider
 */
class Apc extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        return apc_fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        return apc_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        return apc_store($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        //apc删除1个不存在的，会返回false，不存在的等于是删除，也应该返回true
        return apc_delete($id) || !apc_exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return apc_clear_cache() && apc_clear_cache('user');
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $cacheInfo = apc_cache_info('', true);
        $smaInfo = apc_sma_info();
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
        return apc_fetch($keys) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doSetMulti(array $keysAndValues = [], $lifeTime = 0)
    {
        $result = apc_store($keysAndValues, null, $lifeTime);

        return empty($result);
    }

}