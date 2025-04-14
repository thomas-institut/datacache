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

/**
 * Interface to a generic data cache that can only store strings.
 *
 * A DataCache is just a key-value store where items can expire
 * after a certain amount of time.
 *
 * Keys are strings without any restriction as to their content. That
 * is, any character can be part of a key. Any key under 1024 characters
 * in length is supported, but specific implementations may allow
 * even longer keys.
 *
 * The values to be cached are strings without any restriction as to their
 * content. Specific cache implementations
 *
 * @package ThomasInstitut\DataCache
 */

interface DataCache
{

    /**
     * Gets the value associated with the given key.
     *
     * If the key is not the cache, throws a `KeyNotInCacheException`
     *
     * @param string $key
     * @return string
     * @throws KeyNotInCacheException
     */
    public function get(string $key) : string;


    /**
     * Returns the number of seconds until the item expires, 0
     * if the item never expires or -1 if the cache
     * does not support reporting remaining TTLs.
     *
     * @param string $key
     * @return int
     * @throws KeyNotInCacheException
     */
    public function getRemainingTtl(string $key) : int;


    /**
     * Returns true if the given key is stored in the cache
     * @param string $key
     * @return bool
     */
    public function isInCache(string $key) : bool;

    /**
     * Sets the value for the given key.
     *
     * If `$ttl` is greater than 0, the item expires after `$ttl` seconds.
     *
     * If `$ttl` is equal to 0, the cache item never expires, and
     * if `$ttl` < 0, a default TTL is used.
     *
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return void
     */
    public function set(string $key, string $value, int $ttl = -1) : void;


    /**
     * Sets the default TTL.
     *
     * @param int $ttl MUST be >= 0
     * @return void
     */
    public function setDefaultTtl(int $ttl) : void;

    /**
     * Deletes the cache entry for the given key.
     * No exception is thrown if the cache entry did not exist
     * in the first place.
     *
     * @param string $key
     */
    public function delete(string $key) : void;


    /**
     * Deletes all entries in the cache.
     *
     * @return void
     */
    public function flush() : void;


    /**
     * Tries to delete all expired entries.
     *
     * Depending on the cache this may not do anything
     * right away.
     *
     * @return void
     */
    public function clean() : void;


}