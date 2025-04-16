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

namespace ThomasInstitut\DataCache\Reference;

use PHPUnit\Framework\TestCase;
use Random\RandomException;
use ThomasInstitut\DataCache\DataCache;
use ThomasInstitut\DataCache\ItemNotInCacheException;
use ThomasInstitut\TimeString\TimeString;

/**
 * Class DataCacheTest
 *
 * Reference tests for any implementation of a DataCache
 *
 * @package ThomasInstitut\DataCache
 */
class DataCacheReferenceTest extends TestCase
{

    const int NUM_KEYS_TO_TEST = 50;
    const int NUM_READ_ITERATIONS = 5;
    const int REFERENCE_MAX_KEY_LENGTH = 2048;
    const int REFERENCE_MAX_VALUE_LENGTH = 32 * 1024 * 1024;  // 32 MB

    const int MAX_VALUE_LENGTH_FOR_TESTING = 64 * 1024;

    private DataCache $dataCache;
    private string $testClassName;
    private string $keyPrefix;

    /**
     * @param DataCache $dc
     * @param string $testClassName
     */
    public function runAllTests(DataCache $dc, string $testClassName): void
    {
        try {
            $this->setDataCache($dc, $testClassName);
            $this->basicTest();
            $this->deleteTest();
            $this->extremeKeysTest();
            $this->bigValueTest();
            $this->expirationTest();
        } catch (RandomException $e) { // @codeCoverageIgnore
            $this->fail("Random number generator exception: " . $e->getMessage()); // @codeCoverageIgnore
        }
    }

    /**
     * @param DataCache $dc
     * @param string $testClassName
     */
    protected function setDataCache(DataCache $dc, string $testClassName): void
    {
        $this->dataCache = $dc;
        $this->testClassName = $testClassName;
        $this->keyPrefix = 'DataCacheTest:' . $testClassName . ':' . TimeString::now()->toCompactString() . ':'
            . rand(1, 1000) . ':';
    }

    public function basicTest(): void
    {
        // Set default to no expiration
        $this->dataCache->setDefaultTtl(0);

        // try to get a value for a non-existent key
        $exceptionCaught = false;
        try {
            $this->dataCache->get($this->keyPrefix . 'someKey');
        } catch (ItemNotInCacheException) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, $this->testClassName);

        // try to delete a non-existent key (nothing should happen!)
        $this->dataCache->delete($this->keyPrefix . 'someKey');

        // build the two test sets
        $testSet1 = $this->buildTestSet('set1', 'value', self::NUM_KEYS_TO_TEST);
        $testSet2 = $this->buildTestSet('set2', 'newValue', self::NUM_KEYS_TO_TEST);
        $completeSet = [ ...$testSet1, ...$testSet2 ];

        // add first test set to cache
        foreach ($testSet1 as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }
        $this->randomRead($testSet1, true);
        $this->randomRead($testSet2, false);
        // test the "isInCache" method as well
        $this->randomTestInCache($testSet1, true);
        $this->randomTestInCache($testSet2, false);

        // add the second test set
        foreach ($testSet2 as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }
        // old and new values should be in the cache
        $this->randomRead($completeSet, true);
        $this->randomTestInCache($completeSet, true);

        // clean the cache (must not do anything since all items are set not to expire)
        $this->dataCache->clean();
        $this->randomRead($completeSet, true);
        $this->randomTestInCache($completeSet, true);

        // Remaining TTLs should all be zero if the cache can determine it
        foreach ($completeSet as $i => $testCase) {
            try {
                $remainingTtl = $this->dataCache->getRemainingTtl($testCase['key']);
                if ($remainingTtl !== -1) {
                    $this->assertEquals(0, $remainingTtl);
                }
            } catch (ItemNotInCacheException) { // @codeCoverageIgnore
                $this->fail("Item {$testCase['key']} not in cache, iteration $i"); // @codeCoverageIgnore
            }
        }

        // empty cache
        $this->dataCache->flush();

        // read the cache randomly again, the cache should be empty
        $this->randomRead($completeSet, false);
        $this->randomTestInCache($completeSet, false);
    }

    public function deleteTest() : void
    {
        $this->dataCache->flush();
        $this->dataCache->setDefaultTtl(0);

        $testSet = $this->buildTestSet("delete", "valueToDelete", self::NUM_KEYS_TO_TEST);
        foreach ($testSet as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }
        for ($i = 0; $i < self::NUM_READ_ITERATIONS; $i++) {
            $testCase = $testSet[rand(0, count($testSet) - 1)];
            $this->dataCache->delete($testCase['key']);
            $this->assertFalse($this->dataCache->isInCache($testCase['key']));
        }
    }

    public static function buildTestSet(string $keyPrefix, string $valuePrefix, int $numKeys): array
    {
        $valuesTestSet = [];
        for ($i = 0; $i < $numKeys; $i++) {
            $valuesTestSet[] = ['key' => $keyPrefix . '_' . $i . '_' . rand(1, 10000), 'value' => $valuePrefix . '_'
                . rand(1, 100000000)];
        }
        return $valuesTestSet;
    }

    protected function randomRead(array $testSet, $itemsShouldBeInCache): void
    {
        // read the cache randomly
        $numKeys = count($testSet);
        for ($i = 0; $i < self::NUM_READ_ITERATIONS; $i++) {
            $testCase = $testSet[rand(0, $numKeys - 1)];
            $exceptionCaught = false;
            try {
                $cachedValue = $this->dataCache->get($testCase['key']);
                if ($itemsShouldBeInCache) {
                    $this->assertEquals($testCase['value'], $cachedValue, $this->testClassName .
                        ", cache read, iteration $i");
                }
            } catch (ItemNotInCacheException) {
                $exceptionCaught = true;
            }
            if ($itemsShouldBeInCache) {
                $this->assertFalse($exceptionCaught, $this->testClassName . ", cache read, iteration $i");
            } else {
                $this->assertTrue($exceptionCaught, $this->testClassName . ", cache read, iteration $i");
            }
        }
    }

    protected function randomTestInCache(array $testSet, bool $expectedInCache) : void
    {
        $numKeys = count($testSet);
        for ($i = 0; $i < self::NUM_READ_ITERATIONS; $i++) {
            $testCase = $testSet[rand(0, $numKeys - 1)];
            $this->assertEquals($expectedInCache, $this->dataCache->isInCache($testCase['key']));
        }
    }

    /**
     * @throws RandomException
     */
    public function extremeKeysTest(): void
    {
        $testSet = [];

        for ($i = 0; $i < self::NUM_KEYS_TO_TEST; $i++) {
            $testSet[] = [
                'key' => random_bytes(rand(256, self::REFERENCE_MAX_KEY_LENGTH)),
                'value' => random_bytes(rand(256, self::MAX_VALUE_LENGTH_FOR_TESTING))
            ];
        }

        // fill the cache
        foreach ($testSet as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }

        $this->randomRead($testSet, true);
    }


    /**
     * @throws RandomException
     */
    public function bigValueTest(): void
    {
        //  set, get and delete one big item
        $key = 'BigItem' . rand(0, 1000000000);
        $value = random_bytes(self::REFERENCE_MAX_VALUE_LENGTH);
        $this->dataCache->set($key, $value);
        try {
            $this->assertEquals($value, $this->dataCache->get($key));
        } catch (ItemNotInCacheException) { // @codeCoverageIgnore
            $this->fail("Big item not in cache"); // @codeCoverageIgnore
        }
        $this->dataCache->delete($key);
        $this->assertFalse($this->dataCache->isInCache($key));
    }

    public function expirationTest(): void
    {

        $this->dataCache->flush();

        $shortTtl = 1;
        $longTtl = 200;
        $waitTime = 2;

        // Fill in the cache
        $this->dataCache->setDefaultTtl($longTtl);
        $longTtlTestCases = $this->buildTestSet('longTtl', 'value', self::NUM_KEYS_TO_TEST);
        foreach ($longTtlTestCases as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }

        $this->dataCache->setDefaultTtl($shortTtl);
        $shortTtlTestCases = $this->buildTestSet('shortTtl', 'value', self::NUM_KEYS_TO_TEST);
        foreach ($shortTtlTestCases as $testCase) {
            $this->dataCache->set($testCase['key'], $testCase['value']);
        }

        sleep($waitTime);

        $this->randomRead($shortTtlTestCases, false);
        $this->randomRead($longTtlTestCases, true);

        // clean and repeat
        $this->dataCache->clean();
        $this->randomRead($shortTtlTestCases, false);
        $this->randomRead($longTtlTestCases, true);

        // Check remaining TTL for the items still in the cache
        foreach ($longTtlTestCases as $i => $testCase) {
            try {
                $remainingTtl = $this->dataCache->getRemainingTtl($testCase['key']);
                if ($remainingTtl !== -1) {
                    $this->assertGreaterThan(0, $remainingTtl, "Iteration $i");
                    $this->assertLessThan($longTtl, $remainingTtl, "Iteration $i");
                }
            } catch (ItemNotInCacheException) { // @codeCoverageIgnore
                $this->fail("Item not in cache while determining remaining ttl, iteration $i"); // @codeCoverageIgnore
            }
        }
    }
}
