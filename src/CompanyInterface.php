<?php
/**
 * This file is part of the ua-result-company package.
 *
 * Copyright (c) 2015-2018, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace UaResult\Company;

/**
 * @category  ua-result
 *
 * @copyright 2015, 2016 Thomas Mueller
 * @license   http://www.opensource.org/licenses/MIT MIT License
 */
interface CompanyInterface
{
    /**
     * Returns the type name of the company
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Returns the name of the company
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Returns the brand name of the company
     *
     * @return string|null
     */
    public function getBrandName(): ?string;
}
