<?php


namespace leinonen\DataLoader;


class CacheMap implements CacheMapInterface
{
    private $cache = [];

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $index = $this->findCacheIndexByKey($key);

        if ($index === null) {
            return false;
        }

        return $this->cache[$index]['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->cache[] = [
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $index = $this->findCacheIndexByKey($key);
        unset($this->cache[$index]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->cache = [];
    }

    /**
     * Returns the index of the value from the cache array with the given key.
     *
     * @param mixed $cacheKey
     *
     * @return mixed
     */
    private function findCacheIndexByKey($cacheKey)
    {
        foreach ($this->cache as $index => $data) {
            if ($data['key'] === $cacheKey) {
                return $index;
            }
        }

        return null;
    }
}
