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
namespace ThomasInstitut\Test\DataCache\Auxiliary;

use ThomasInstitut\DataCache\CacheAware;
use ThomasInstitut\DataCache\SimpleCacheAwareTrait;

class SimpleCacheAwareTestClass implements CacheAware
{
    use SimpleCacheAwareTrait;

    public function cacheSet(string $key, string $data) : bool
    {
        if ($this->isCacheInUse()) {
            $this->getDataCache()->set($key, $data);
            return true;
        }
        return false;
    }
}
