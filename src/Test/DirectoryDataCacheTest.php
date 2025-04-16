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
use Random\RandomException;
use ThomasInstitut\DataCache\DirectoryDataCache;
use ThomasInstitut\DataCache\ItemNotInCacheException;
use ThomasInstitut\DataCache\Reference\DataCacheReferenceTest;

class DirectoryDataCacheTest extends TestCase
{

    public function testStandardTests()
    {
        $tester = new DataCacheReferenceTest('DirectoryDataCache');
        $tester->runAllTests(new DirectoryDataCache("/tmp", "DC"), 'DirectoryDataCache');
    }

    /**
     * @throws RandomException
     */
    public function testBadConstructors()
    {

        // bad cache names
        $badCacheNames = ['', 'my*cache', 'my/cache', 'my.cache', 'my-cache'];
        foreach ($badCacheNames as $badCacheName) {
            $exceptionCaught = false;
            try {
                new DirectoryDataCache("/tmp", $badCacheName);
            } catch (InvalidArgumentException) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, "Testing bad cache name '$badCacheName'");
        }


        // bad file extensions
        $badFileExtensions = ['.txt', '*txt', '/txt', '-txt'];
        foreach ($badFileExtensions as $badFileExtension) {
            $exceptionCaught = false;
            try {
                new DirectoryDataCache("/tmp", 'DC', $badFileExtension);
            } catch (InvalidArgumentException) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, "Testing bad file extension '$badFileExtension'");
        }


        // bad separators
        $badSeparators = ['', '*', '/', '.'];
        foreach ($badSeparators as $badSeparator) {
            $exceptionCaught = false;
            try {
                new DirectoryDataCache("/tmp", 'DC', 'txt', $badSeparator);
            } catch (InvalidArgumentException) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, "Testing bad separator '$badSeparator'");
        }

        // Test valid combinations (even if they don't make sense)
        $testCases = [
            [
                'Cache name with default separator',
                new DirectoryDataCache('/tmp', 'my-cache-1', 'txt', '_')
            ],
            [
                'File extension with default separator',
                new DirectoryDataCache('/tmp', 'my-cache-2', '-txt', '_')
            ],
            [
                'Dot as separator',
                new DirectoryDataCache('/tmp', 'my-cache3', '', '.')
            ]
        ];

        foreach ($testCases as $testCase) {
            [ $description, $cache] = $testCase;
            $value = random_bytes(512);
            $cache->set("MyKey:0", $value);
            $this->assertEquals($value, $cache->get("MyKey:0"), $description);
            $cache->delete("MyKey:0");
            $exceptionCaught = false;
            try {
                $cache->get("MyKey:0");
            } catch (ItemNotInCacheException) {
                $exceptionCaught = true;
            }
            $this->assertTrue($exceptionCaught, $description);
            $cache->set("MyKey:0", $value);
            $cache->set("MyKey:2", $value);
            $cache->set("MyKey:3", $value);
            $cache->flush();
            $cache->set("MyKey:1", $value); // just to see the files!
        }
    }
}
