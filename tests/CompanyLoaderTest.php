<?php
/**
 * This file is part of the ua-result-company package.
 *
 * Copyright (c) 2015-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace UaResultTest\Company;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use UaResult\Company\CompanyLoader;

/**
 * Test class for \BrowserDetector\Loader\CompanyLoader
 */
class CompanyLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \UaResult\Company\CompanyLoader
     */
    private $object;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $cache        = new FilesystemAdapter('', 0, __DIR__ . '/../cache/');
        $this->object = new CompanyLoader($cache);
    }

    /**
     * @dataProvider providerLoad
     *
     * @param string $companyKey
     * @param string $companyName
     * @param string $brand
     */
    public function testLoadAvailable($companyKey, $companyName, $brand): void
    {
        /** @var \UaResult\Company\CompanyInterface $result */
        $result = $this->object->load($companyKey);

        self::assertInstanceOf('\UaResult\Company\CompanyInterface', $result);
        self::assertInstanceOf('\UaResult\Company\Company', $result);

        self::assertSame(
            $companyName,
            $result->getName(),
            'Expected Company name to be "' . $companyName . '" (was "' . $result->getName() . '")'
        );
        self::assertSame(
            $brand,
            $result->getBrandName(),
            'Expected brand name to be "' . $brand . '" (was "' . $result->getBrandName() . '")'
        );
    }

    /**
     * @return array[]
     */
    public function providerLoad()
    {
        return [
            [
                'A6Corp',
                'A6 Corp',
                'A6 Corp',
            ],
        ];
    }

    /**
     * @dataProvider providerLoadByName
     *
     * @param string $nameToSearch
     * @param string $companyName
     * @param string $brand
     */
    public function testLoadByName($nameToSearch, $companyName, $brand): void
    {
        /** @var \UaResult\Company\CompanyInterface $result */
        $result = $this->object->loadByName($nameToSearch);

        self::assertInstanceOf('\UaResult\Company\CompanyInterface', $result);
        self::assertInstanceOf('\UaResult\Company\Company', $result);

        self::assertSame(
            $companyName,
            $result->getName(),
            'Expected Company name to be "' . $companyName . '" (was "' . $result->getName() . '")'
        );
        self::assertSame(
            $brand,
            $result->getBrandName(),
            'Expected brand name to be "' . $brand . '" (was "' . $result->getBrandName() . '")'
        );
    }

    /**
     * @return array[]
     */
    public function providerLoadByName()
    {
        return [
            [
                'Google Inc',
                'Google Inc',
                'Google',
            ],
            [
                'This company does not exist',
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider providerLoadByBrandName
     *
     * @param string $brandnameToSearch
     * @param string $companyName
     * @param string $brand
     */
    public function testLoadByBrandName($brandnameToSearch, $companyName, $brand): void
    {
        /** @var \UaResult\Company\CompanyInterface $result */
        $result = $this->object->loadByBrandName($brandnameToSearch);

        self::assertInstanceOf('\UaResult\Company\CompanyInterface', $result);
        self::assertInstanceOf('\UaResult\Company\Company', $result);

        self::assertSame(
            $companyName,
            $result->getName(),
            'Expected Company name to be "' . $companyName . '" (was "' . $result->getName() . '")'
        );
        self::assertSame(
            $brand,
            $result->getBrandName(),
            'Expected brand name to be "' . $brand . '" (was "' . $result->getBrandName() . '")'
        );
    }

    /**
     * @return array[]
     */
    public function providerLoadByBrandName()
    {
        return [
            [
                'Google',
                'Google Inc',
                'Google',
            ],
            [
                'This company does not exist',
                null,
                null,
            ],
        ];
    }

    public function testLoadNotAvailable(): void
    {
        $this->expectException('\BrowserDetector\Loader\NotFoundException');
        $this->expectExceptionMessage('the company with key "does not exist" was not found');

        $this->object->load('does not exist');
    }
}
