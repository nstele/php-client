<?php
namespace SplitIO\Component\Cache\Storage\Adapter;

use SplitIO\Component\Cache\Storage\Exception\AdapterException;
use SplitIO\Component\Cache\Item;

/**
 * Class PRedis
 * @package SplitIO\Component\Cache\Storage\Adapter
 */
class PRedis implements CacheStorageAdapterInterface
{
    /** @var \Predis\Client|null  */
    private $client = null;

    /**
     * @param array $options
     * @throws AdapterException
     */
    public function __construct(array $options)
    {
        if (!class_exists('\Predis\Client')) {
            throw new AdapterException("PRedis class is not loaded");
        }

        $_parameters = (isset($options['parameters'])) ? $options['parameters'] : null;
        $_options = (isset($options['options'])) ? $options['options'] : null;

        $this->client = new \Predis\Client($_parameters, $_options);
    }

    /**
     * @param string $key
     * @return \SplitIO\Component\Cache\Item
     */
    public function getItem($key)
    {
        $item = new Item($key);

        $redisItem = $this->client->get($key);

        if ($redisItem !== null) {
            $item->set($redisItem);
        }

        return $item;
    }


    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws \InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array())
    {
        return $this->client->mget($keys);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $expiration
     * @return bool
     */
    public function addItem($key, $value, $expiration = null)
    {
        return $this->save($key, $value, $expiration);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->client->flushAll();
    }

    /**
     * @param $key
     * @return bool
     */
    public function deleteItem($key)
    {
        $return = $this->client->del($key);

        if ($return > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $keys
     * @return bool
     */
    public function deleteItems(array $keys)
    {
        $return = $this->client->del($keys);

        if ($return > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param int|null $expiration
     * @return bool
     */
    public function save($key, $value, $expiration = null)
    {
        return $this->client->set($key, $value);
    }

    /**
     * Adds a values to the set value stored at key.
     * If this value is already in the set, FALSE is returned.
     *
     * @param $key
     * @param $value
     * @return boolean
     */
    public function addItemList($key, $value)
    {
        return $this->client->sAdd($key, $value);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function removeItemList($key, $value)
    {
        return $this->client->sRem($key, $value);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function isOnList($key, $value)
    {
        return $this->client->sIsMember($key, $value);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getListItems($key)
    {
        return $this->client->sMembers($key);
    }

    public function getListItemsRandomly($key, $count)
    {
        return $this->client->srandmember($key, $count);
    }

    public function getKeys($pattern = '*')
    {
        $prefix = null;
        if ($this->client->getOptions()->__isset("prefix")) {
            $prefix = $this->client->getOptions()->__get("prefix")->getPrefix();
        }

        $keys = $this->client->keys($pattern);

        if ($prefix) {
            if (is_array($keys)) {
                for ($i=0; $i < count($keys); $i++) {
                    $keys[$i] = str_replace($prefix, '', $keys[$i]);
                }
            } else {
                $keys = str_replace($prefix, '', $keys);
            }
        }

        return $keys;
    }

    public function incrementKey($key)
    {
        return $this->client->incr($key);
    }

    public function getSet($key, $value)
    {
        return $this->client->getSet($key, $value);
    }
}
