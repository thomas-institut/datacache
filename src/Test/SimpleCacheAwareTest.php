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

namespace ThomasInstitut\Test\DataCache;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ThomasInstitut\DataCache\DataCache;
use ThomasInstitut\DataCache\InMemoryDataCache;
use ThomasInstitut\Test\DataCache\Auxiliary\SimpleCacheAwareTestClass;
use TypeError;

class SimpleCacheAwareTest extends TestCase
{

    public function testBasicBehaviour() : void
    {

        $cachingClass = new SimpleCacheAwareTestClass();

        $cache = new InMemoryDataCache();

        $key = 'TestKey';
        $value = 'MyValue';


        $cachingClass->cacheSet($key, $value);
        $this->assertFalse($cache->isInCache($key));
        $exceptionCaught = false;
        try {
            $cachingClass->getDataCache();
        } catch (RuntimeException) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught);

        $cachingClass->setCache($cache);
        $cachingClass->cacheSet($key, $value);
        $this->assertFalse($cache->isInCache($key));
        $cachingClass->useCache();
        $cachingClass->cacheSet($key, $value);
        $this->assertTrue($cache->isInCache($key));
        $cache->delete($key);
        $this->assertFalse($cache->isInCache($key));
        $cachingClass->doNotUseCache();
        $cachingClass->cacheSet($key, $value);
        $this->assertFalse($cache->isInCache($key));
    }


    public function testCallablesBehaviour() : void
    {


        // bad callable
        $cachingClass = new SimpleCacheAwareTestClass();
        $cachingClass->setCache(
            function () {
                return 'bad bad callable';
            }
        );
        $exceptionCaught = false;
        try {
            $cachingClass->getDataCache();
        } catch (TypeError) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught);

        // good callable
        $cachingClass->setCache(
            function () {
                return new InMemoryDataCache();
            }
        );
        $cache = $cachingClass->getDataCache();
        $this->assertTrue(is_a($cache, DataCache::class));
    }
}
