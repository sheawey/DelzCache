<?php

namespace Delz\Cache\Contract;

/**
 * 缓存接口类
 *
 * @package Delz\Cache
 */
interface ICache
{
    /**
     * 缓存命中次数参数
     */
    const STATS_HITS = 'hits';

    /**
     * 缓存未命中次数参数
     */
    const STATS_MISSES = 'misses';

    /**
     * 缓存服务上次启动时间参数
     */
    const STATS_UPTIME = 'uptime';

    /**
     * 内存使用量参数
     */
    const STATS_MEMORY_USAGE = 'memory_usage';

    /**
     * 内存可用量参数
     */
    const STATS_MEMORY_AVAILABLE = 'memory_available';

    /**
     * 读取缓存
     *
     * @param string $id 缓存id
     * @return mixed
     */
    public function get($id);

    /**
     * 判断指定的缓存是否存在
     *
     * @param string $id 缓存id
     * @return bool 存在返回true，否则返回false
     */
    public function has($id);

    /**
     * 写入缓存
     *
     * @param string $id 缓存id
     * @param mixed $data 缓存数据
     * @param int $lifeTime 缓存时间，单位为秒，如果设置为0，表示永不过期（当然这不是绝对的，如果内存满了，系统会启用LRU\FIFO等策略删除）
     * @return bool 写入成功，返回true，否则返回false
     */
    public function set($id, $data, $lifeTime = 0);

    /**
     * 删除指定的缓存
     *
     * @param string $id 缓存id
     * @return bool 删除成功返回true，否则返回false
     */
    public function delete($id);

    /**
     * 清空某个命令空间下的缓存
     *
     * 每个命令空间表示一类缓存，实现方法可清除某一类的全部缓存
     *
     * 注意与flush方法的区别，flush是清除所有缓存，clear是清除某类缓存
     *
     * @return bool 成功返回true，否则返回false
     */
    public function clear();

    /**
     * 清空缓存
     *
     * @return bool 成功返回true，否则返回false
     */
    public function flush();

    /**
     * 批量获取缓存
     *
     * @param array $keys 多个缓存id的数组
     * @return mixed[] 根据$key数组返回相应的缓存值数组
     */
    public function getMulti(array $keys= []);

    /**
     * 批量设置缓存
     *
     * @param array $keysAndValues 缓存id和值数组，如['a'=>1,'b'=>2],其中a、b是缓存id，1、2是对应的值
     * @param int $lifeTime 缓存时间，单位为秒，如果设置为0，表示永不过期（当然这不是绝对的，如果内存满了，系统会启用LRU\FIFO等策略删除）
     * @return bool 全部保存成功返回true，只要有一个保存失败则返回false
     */
    public function setMulti(array $keysAndValues = [], $lifeTime = 0);

    /**
     * 返回缓存统计信息，主要返回如下数据：
     *
     * - <b>hits</b>
     * 缓存命中次数
     *
     * - <b>misses</b>
     * 缓存未命中次数
     *
     * - <b>uptime</b>
     * 缓存服务上次启动时间
     *
     * - <b>memory_usage</b>
     * 内存使用量
     *
     * - <b>memory_available</b>
     * 内存可用量
     *
     * @return array|null
     */
    public function stats();
}