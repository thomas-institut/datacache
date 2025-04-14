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


class InMemoryDataCache implements DataCache
{

    /**
     * @var array
     */
    private array $theCache;
    private int $defaultTtl;

    public function __construct()
    {
        $this->theCache = [];
        $this->defaultTtl = 0;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): string
    {
        $now = time();
        if ($this->isInCache($key)) {
            if ($this->theCache[$key]['expires'] !== -1 && $this->theCache[$key]['expires'] <= $now) {
                // expired!
                $this->delete($key);
                throw new KeyNotInCacheException("Key '$key' not in cache");
            }
            return $this->theCache[$key]['value'];
        }
        throw new KeyNotInCacheException();
    }

    /**
     * @inheritDoc
     */
    public function getRemainingTtl(string $key): int
    {
        $now = time();
        // check that the item is not expired by trying to get it
        // this will throw an KeyNotCacheException if the item
        // is expired or not present in the cache
        $this->get($key);

        // if we get here, the item is in the cache and is not expired
        if ($this->theCache[$key]['expires'] === -1){
            return 0;
        }

        return $this->theCache[$key]['expires'] - $now;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, string $value, int $ttl = -1): void
    {
        if ($ttl < 0) {
            $ttl = $this->defaultTtl;
        }
        $expires = -1;
        if ($ttl > 0) {
            $expires = time() + $ttl;
        }
        $this->theCache[$key] = [ 'value' => $value, 'expires' => $expires];
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        if ($this->isInCache($key)) {
            unset($this->theCache[$key]);
        }
    }

    public function isInCache(string $key): bool
    {
        return isset($this->theCache[$key]);
    }

    public function flush(): void
    {
       $this->theCache = [];
    }

    public function clean(): void
    {
        $keysToDelete = [];
        $now = time();
        foreach (array_keys($this->theCache) as $key) {
            $expires = $this->theCache[$key]['expires'];
            if ($expires !== -1 && $expires < $now) {
                $keysToDelete[] = $key;
            }
        }
        foreach ($keysToDelete as $key) {
            $this->delete($key);
        }
    }

    /**
     * @inheritDoc
     */
    public function setDefaultTtl(int $ttl): void
    {
        if ($ttl >= 0) {
            $this->defaultTtl = $ttl;
        }
    }
}