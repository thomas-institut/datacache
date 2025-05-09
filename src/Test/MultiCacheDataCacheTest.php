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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ThomasInstitut\DataCache\InMemoryDataCache;
use ThomasInstitut\DataCache\ItemNotInCacheException;
use ThomasInstitut\DataCache\MultiCacheDataCache;
use ThomasInstitut\DataCache\Reference\DataCacheReferenceTest;

class MultiCacheDataCacheTest extends TestCase
{
    public function testStandardTests()
    {
        $tester = new DataCacheReferenceTest('MultiCacheDataCache');
        $cache1 = new InMemoryDataCache();
        $cache2 = new InMemoryDataCache();
        $multiCache =  new MultiCacheDataCache(
            [$cache1, $cache2, function () {
                return new InMemoryDataCache();
            }],
            ['c1', null, 'c3']
        );
        $tester->runAllTests($multiCache, 'MultiCacheDataCache');
    }

    public function testMissingCaches() : void
    {

        $numCaches = 10;
        $numKeys = 200;
        $numTestIterations = 50;
        $ttl = 1000;

        /**
         * @var InMemoryDataCache[] $caches
         */
        $caches = [];
        for ($i = 0; $i < $numCaches; $i++) {
            $caches[] = new InMemoryDataCache();
        }
        $multiCache = new MultiCacheDataCache($caches);
        $multiCache->setDefaultTtl($ttl);

        $testSet = DataCacheReferenceTest::buildTestSet("missingCacheTestSet", 'value', $numKeys);
        foreach ($testSet as $testCase) {
            $multiCache->set($testCase['key'], $testCase['value']);
        }

        for ($i = 0; $i < $numTestIterations; $i++) {
            $testCase = $testSet[rand(0, count($testSet) - 1)];
            for ($j = 0; $j < $numCaches; $j++) {
                $this->assertTrue($caches[$j]->isInCache($testCase['key']));
            }

            $randomCache = $caches[rand(0, count($caches) - 1)];
            $key = $testCase['key'];
            $randomCache->delete($key);
            try {
                $this->assertEquals($multiCache->get($key), $testCase['value']);
            } catch (ItemNotInCacheException) { // @codeCoverageIgnore
                // @codeCoverageIgnoreStart
                $this->fail("Item '$key' not in multi-cache after being 
                   deleted in one of the caches");
                // @codeCoverageIgnoreEnd
            }
            // set it again
            $multiCache->set($testCase['key'], $testCase['value']);
        }
    }

    /**
     * @throws ItemNotInCacheException
     */
    public function testBadCallablesInConstructor() : void
    {
        $testCases = [
            [ 'callable' => function () {
                return new DataCacheReferenceTest('bogus');
            },'description' => 'Callable returns non-DataCache'],
            [ 'callable' => function () {
                return 123;
            },'description' => 'Callable returns non-object'],
        ];

        foreach ($testCases as $testCase) {
            $callable = $testCase['callable'];
            $description = $testCase['description'];
            $multiCache = new MultiCacheDataCache([$callable, new InMemoryDataCache()], [], false);
            $multiCache->set("test", "value");
            $this->assertEquals("value", $multiCache->get("test"), $description);
            $multiCache = new MultiCacheDataCache([$callable, new InMemoryDataCache()], [], true);
            $exceptionThrown = false;
            try {
                $multiCache->set("test", "value");
            } catch (RuntimeException) {
                $exceptionThrown = true;
            }
            $this->assertTrue($exceptionThrown, $description);
        }
    }

    public function testBadArrayInConstructor() : void
    {
        $this->expectException(InvalidArgumentException::class);
        new MultiCacheDataCache([ 123, new InMemoryDataCache() ], [], true);
    }
}
