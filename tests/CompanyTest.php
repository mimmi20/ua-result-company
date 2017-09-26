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

use UaResult\Company\Company;

class CompanyTest extends \PHPUnit\Framework\TestCase
{
    public function testSetterGetter(): void
    {
        $type      = 'CompanyType';
        $name      = 'TestCompany';
        $brandname = 'TestBrand';

        $object = new Company($type, $name, $brandname);

        self::assertSame($type, $object->getType());
        self::assertSame($name, $object->getName());
        self::assertSame($brandname, $object->getBrandName());
    }
}
