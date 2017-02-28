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
namespace UaResult\Company;

use BrowserDetector\Loader\LoaderInterface;
use BrowserDetector\Loader\NotFoundException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Browser detection class
 *
 * @category  BrowserDetector
 *
 * @author    Thomas Mueller <mimmi20@live.de>
 * @copyright 2012-2017 Thomas Mueller
 * @license   http://www.opensource.org/licenses/MIT MIT License
 */
class CompanyLoader implements LoaderInterface
{
    /**
     * @var \Psr\Cache\CacheItemPoolInterface|null
     */
    private $cache = null;

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * detects if the company is available
     *
     * @param string $companyKey
     *
     * @return bool
     */
    public function has($companyKey)
    {
        $this->init();

        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $companyKey));

        return $cacheItem->isHit();
    }

    /**
     * Gets the information about the company
     *
     * @param string $companyKey
     *
     * @throws \BrowserDetector\Loader\NotFoundException
     *
     * @return \UaResult\Company\Company
     */
    public function load($companyKey)
    {
        $this->init();

        if (!$this->has($companyKey)) {
            throw new NotFoundException('the company with key "' . $companyKey . '" was not found');
        }

        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $companyKey));

        $company = $cacheItem->get();

        return new Company(
            $company->type,
            $company->name,
            $company->brandname
        );
    }

    /**
     * initializes cache
     */
    private function init()
    {
        $cacheInitializedId = hash('sha512', 'company-cache is initialized');
        $cacheInitialized   = $this->cache->getItem($cacheInitializedId);

        if (!$cacheInitialized->isHit() || !$cacheInitialized->get()) {
            foreach ($this->getCompanies() as $companyKey => $companyData) {
                $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $companyKey));
                $cacheItem->set($companyData);

                $this->cache->save($cacheItem);
            }

            $cacheInitialized->set(true);
            $this->cache->save($cacheInitialized);
        }
    }

    /**
     * @return array[]
     */
    private function getCompanies()
    {
        static $companies = null;

        if (null === $companies) {
            $companies = json_decode(file_get_contents(__DIR__ . '/../data/companies.json'));
        }

        foreach ($companies as $key => $data) {
            yield $key => $data;
        }
    }
}
