<?php
/*
 *  Copyright (C) 2020-2025 Universität zu Köln
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace ThomasInstitut\DataCache;

use InvalidArgumentException;
use RuntimeException;

/**
 * A DataCache that consists of an ordered list of DataCaches.
 *
 * When an item is set, it is set in all the caches. When it is
 * retrieved, it is retrieved from the first cache in the list
 * that has it. When it is deleted, it is deleted from all caches
 */
class MultiCacheDataCache implements DataCache
{
    /**
     * @var DataCache[]
     */
    private array $caches;

    /**
     * @var string[]
     */
    private array $prefixes;
    private bool $strict;


    /**
     *
     * @param DataCache[]|callable[] $caches DataCache objects or callables that generate a DataCache object
     * @param string[] $cachePrefixes  Prefixes to attach to the key for each cache
     * @param bool $strict  If true (default), throws an exception if a data cache cannot be constructed otherwise
     *    just continues with an InMemoryDataCache in its place
     */
    public function __construct(array $caches, array $cachePrefixes = [], bool $strict = true)
    {
        foreach($caches as $i => $dataCache) {
            if (is_callable($dataCache) || is_a($dataCache, DataCache::class)) {
                if (!isset($cachePrefixes[$i])) {
                    $cachePrefixes[$i] = '';
                }
                $this->caches[] = $dataCache;
                $this->prefixes[] = $cachePrefixes[$i];
            } else {
                throw new InvalidArgumentException("Element $i in input array neither a DataCache nor a callable");
            }
        }
        $this->strict = $strict;
    }

    public function setDefaultTtl(int $ttl): void
    {
        foreach(array_keys($this->caches) as $i) {
            $dataCache = $this->getDataCache($i);
            $dataCache->setDefaultTtl($ttl);
        }
    }

    private function getDataCache(int $index) : DataCache {
        if (is_callable($this->caches[$index])) {
            $this->caches[$index] = call_user_func($this->caches[$index]);
            if (!is_object($this->caches[$index])) {
                if ($this->strict) {
                    throw new RuntimeException("Callable for data cache $index did not return an object");
                }
                $this->caches[$index]= new InMemoryDataCache();
            }
            if (!is_a($this->caches[$index], DataCache::class)) {
                if ($this->strict) {
                    throw new RuntimeException("Callable for data cache $index did not return a DataCache: " . get_class($this->caches[$index]));
                }
                $this->caches[$index]= new InMemoryDataCache();
            }
        }
        return $this->caches[$index];
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): string
    {
        $cachesWithoutData = [];
        foreach(array_keys($this->caches) as $i) {
            $dataCache = $this->getDataCache($i);
            try {
                $data = $dataCache->get($this->prefixes[$i] . $key);
                if (count($cachesWithoutData) > 0) {
                    $remainingTtl = $dataCache->getRemainingTtl($key);
                    if ($remainingTtl >= 0) {
                        foreach ($cachesWithoutData as $cacheIndex) {
                            $this->getDataCache($cacheIndex)->set($this->prefixes[$cacheIndex] . $key, $data, $remainingTtl);
                        }
                    }
                }
                return $data;
            } catch (KeyNotInCacheException) {
                $cachesWithoutData[] = $i;
            }
        }
        throw new KeyNotInCacheException();
    }

    /**
     * @inheritDoc
     */
    public function isInCache(string $key): bool
    {
        try {
            return is_string($this->get($key));
        } catch (KeyNotInCacheException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, string $value, int $ttl = -1): void
    {
        foreach(array_keys($this->caches) as $i) {
            $this->getDataCache($i)->set($this->prefixes[$i] . $key, $value, $ttl);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        foreach(array_keys($this->caches) as $i) {
            $this->getDataCache($i)->delete($this->prefixes[$i] . $key);
        }
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        foreach(array_keys($this->caches) as $i) {
            $this->getDataCache($i)->flush();
        }
    }

    /**
     * @inheritDoc
     */
    public function clean(): void
    {
        foreach(array_keys($this->caches) as $i) {
            $this->getDataCache($i)->clean();
        }
    }

    /**
     * @inheritDoc
     */
    public function getRemainingTtl(string $key): int
    {
        return -1;
    }
}