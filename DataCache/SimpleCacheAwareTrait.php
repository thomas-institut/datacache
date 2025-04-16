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


use RuntimeException;

/**
 * Implements the CacheAware interface by adding
 * a protected DataCache variable and a boolean
 * cacheOn flag
 */
trait SimpleCacheAwareTrait
{

    protected bool $cacheOn = false;
    private ?DataCache $dataCache = null;
    /**
     * @var callable|null
     */
    private $dataCacheCallable = null;

    public function useCache(): void
    {
        $this->cacheOn = true;
    }
    public function doNotUseCache(): void
    {
        $this->cacheOn = false;
    }

    public function setCache(DataCache|callable $dataCache): void
    {
        if (is_callable($dataCache)) {
            $this->dataCacheCallable = $dataCache;
            $this->dataCache = null;
        } else {
            $this->dataCache = $dataCache;
            $this->dataCacheCallable = null;
        }
    }
    public function isCacheInUse() : bool {
       if ($this->dataCache === null) {
           return $this->dataCacheCallable !== null && $this->cacheOn;
       } else {
           return $this->cacheOn;
       }
    }

    public function getDataCache() : DataCache {
        if ($this->dataCache === null) {
            if ($this->dataCacheCallable === null) {
                throw new RuntimeException("No DataCache instance available");
            }
            $this->dataCache = call_user_func($this->dataCacheCallable);
        }
        return $this->dataCache;
    }

}