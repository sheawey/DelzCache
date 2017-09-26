<?php

namespace Delz\Cache\Provider;

/**
 * php文件缓存类
 *
 * @package Delz\Cache\Provider
 */
class PhpFile extends File
{
    /**
     * {@inheritdoc}
     */
    public function __construct($directory, $directoryLevel = 0, $extension = '.php', $umask = 0002)
    {
        parent::__construct($directory, $directoryLevel, $extension, $umask);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGet($id)
    {
        $value = $this->includeFileWithId($id);

        if (!$value) {
            return false;
        }

        if ($value['lifetime'] !== 0 && $value['lifetime'] < time()) {
            return false;
        }

        return $value['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function doHas($id)
    {
        $value = $this->includeFileWithId($id);

        if (!$value) {
            return false;
        }

        return $value['lifetime'] === 0 || $value['lifetime'] > time();
    }

    /**
     * {@inheritdoc}
     */
    protected function doSet($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        if (is_object($data) && !method_exists($data, "__set_state")) {
            throw new \InvalidArgumentException(
                "Invalid argument given, PhpFile only allows objects that implement __set_state() " .
                "and fully support var_export()."
            );
        }

        $fileName = $this->getFilename($id);

        $value = [
            'lifetime' => $lifeTime,
            'data' => $data
        ];

        $value = var_export($value, true);
        $code = sprintf('<?php return %s;', $value);

        return $this->writeFile($fileName, $code);
    }

    /**
     * @param string $id
     * @return array|false
     */
    private function includeFileWithId($id)
    {
        $fileName = $this->getFileName($id);

        //不用file_exists\is_file\is_readable,因为直接错误信息比判断一下速度快
        $value = @include $fileName;

        if (!isset($value['lifetime'])) {
            return false;
        }

        return $value;

    }
}