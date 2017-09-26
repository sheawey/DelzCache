<?php

namespace Delz\Cache\Provider;

use Delz\Cache\Contract\ICache;

/**
 * 文件类缓存
 *
 * 不同文件可继承此类
 *
 * @package Delz\Cache\Provider
 */
class File extends Base
{
    /**
     * 缓存保存目录
     *
     * @var string
     */
    protected $directory;

    /**
     * 目录层次
     *
     * @var int
     */
    protected $directoryLevel;

    /**
     * 缓存后缀名
     *
     * @var string
     */
    private $extension;

    /**
     * @var int
     */
    private $umask;

    /**
     * 构造方法
     *
     * @param string $directory
     * @param int $directoryLevel 目录层次
     * @param string $extension
     * @param int $umask
     */
    public function __construct($directory, $directoryLevel = 0, $extension = '.cache', $umask = 0002)
    {
        if (!is_int($umask)) {
            throw new \InvalidArgumentException(sprintf(
                'The umask parameter is required to be integer, was: %s',
                gettype($umask)
            ));
        }
        $this->umask = $umask;

        if (!$this->createPath($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" does not exist and could not be created.',
                $directory
            ));
        }

        if (!is_writable($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" is not writable.',
                $directory
            ));
        }

        $this->directory = realpath($directory);
        $this->directoryLevel = (int)$directoryLevel;
        $this->extension = (string)$extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        $data = '';
        $lifetime = -1;
        $filename = $this->getFilename($id);

        if (!is_file($filename)) {
            return false;
        }

        $resource = fopen($filename, "r");

        if (false !== ($line = fgets($resource))) {
            $lifetime = (int)$line;
        }

        if ($lifetime !== 0 && $lifetime < time()) {
            fclose($resource);

            return false;
        }

        while (false !== ($line = fgets($resource))) {
            $data .= $line;
        }

        fclose($resource);

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        $lifetime = -1;
        $filename = $this->getFilename($id);

        if (!is_file($filename)) {
            return false;
        }

        $resource = fopen($filename, "r");

        if (false !== ($line = fgets($resource))) {
            $lifetime = (int)$line;
        }

        fclose($resource);

        return $lifetime === 0 || $lifetime > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        $data = serialize($data);
        $filename = $this->getFilename($id);

        return $this->writeFile($filename, $lifeTime . PHP_EOL . $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $filename = $this->getFilename($id);

        return @unlink($filename) || !file_exists($filename);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        foreach ($this->getIterator() as $name => $file) {
            if ($file->isDir()) {
                @rmdir($name);
            } elseif (pathinfo($name, PATHINFO_EXTENSION) === $this->extension) {
                @unlink($name);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doStats()
    {
        $usage = 0;
        foreach ($this->getIterator() as $name => $file) {
            if (!$file->isDir() && pathinfo($name, PATHINFO_EXTENSION) === $this->extension) {
                $usage += $file->getSize();
            }
        }

        $free = disk_free_space($this->directory);

        return array(
            ICache::STATS_HITS => null,
            ICache::STATS_MISSES => null,
            ICache::STATS_UPTIME => null,
            ICache::STATS_MEMORY_USAGE => $usage,
            ICache::STATS_MEMORY_AVAILABLE => $free,
        );
    }

    /**
     * 根据缓存Id获取缓存文件
     *
     * @param string $id
     * @return string
     */
    protected function getFilename($id)
    {
        $hash = md5($id);

        $path = '';
        for ($i = 0; $i < $this->directoryLevel; $i++) {
            $path .= DIRECTORY_SEPARATOR . substr($hash, $i * 2, 2);
        }

        return $this->directory
        . $path
        . DIRECTORY_SEPARATOR
        . $hash
        . $this->extension;
    }

    /**
     * 创建目录
     *
     * @param string $path
     * @return bool
     */
    protected function createPath($path)
    {
        if (!is_dir($path)) {
            if (false === @mkdir($path, 0777 & (~$this->umask), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 将内容写入文件
     *
     * @param string $filename
     * @param string $content
     * @return bool
     */
    protected function writeFile($filename, $content)
    {
        $filePath = pathinfo($filename, PATHINFO_DIRNAME);

        if (!$this->createPath($filePath)) {
            return false;
        }

        if (!is_writable($filePath)) {
            return false;
        }

        $tmpFile = tempnam($filePath, 'swap');
        @chmod($tmpFile, 0666 & (~$this->umask));

        if (file_put_contents($tmpFile, $content) !== false) {
            if (@rename($tmpFile, $filename)) {
                return true;
            }

            @unlink($tmpFile);
        }

        return false;
    }

    /**
     * @return \Iterator
     */
    private function getIterator()
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

}