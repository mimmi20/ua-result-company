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
use Seld\JsonLint\JsonParser;

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
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var self|null
     */
    private static $instance;

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     */
    private function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     *
     * @return self
     */
    public static function getInstance(CacheItemPoolInterface $cache)
    {
        if (null === self::$instance) {
            self::$instance = new self($cache);
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * detects if the company is available
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->init();

        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));

        return $cacheItem->isHit();
    }

    /**
     * Gets the information about the company
     *
     * @param string $key
     *
     * @throws \BrowserDetector\Loader\NotFoundException
     *
     * @return \UaResult\Company\CompanyInterface
     */
    public function load(string $key): CompanyInterface
    {
        $this->init();

        if (!$this->has($key)) {
            throw new NotFoundException('the company with key "' . $key . '" was not found');
        }

        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));

        $company = $cacheItem->get();

        return new Company(
            $company->type,
            $company->name,
            $company->brandname
        );
    }

    /**
     * @param string $name
     *
     * @throws \BrowserDetector\Loader\NotFoundException
     *
     * @return \UaResult\Company\CompanyInterface
     */
    public function loadByName(string $name): CompanyInterface
    {
        $this->init();

        foreach ($this->getCompanies() as $key => $companyData) {
            if ($name !== $companyData->name) {
                continue;
            }

            return $this->load($key);
        }

        throw new NotFoundException('the company with name "' . $name . '" was not found');
    }

    /**
     * @param string $name
     *
     * @throws \BrowserDetector\Loader\NotFoundException
     *
     * @return \UaResult\Company\CompanyInterface
     */
    public function loadByBrandName(string $name): CompanyInterface
    {
        $this->init();

        foreach ($this->getCompanies() as $key => $companyData) {
            if ($name !== $companyData->brandname) {
                continue;
            }

            return $this->load($key);
        }

        throw new NotFoundException('the company with brand name "' . $name . '" was not found');
    }

    /**
     * initializes cache
     *
     * @return void
     */
    private function init(): void
    {
        $cacheInitializedId = hash('sha512', 'company-cache is initialized');
        $cacheInitialized   = $this->cache->getItem($cacheInitializedId);

        if (!$cacheInitialized->isHit() || !$cacheInitialized->get()) {
            $jsonParser = new JsonParser();
            $companies  = $jsonParser->parse(
                file_get_contents(__DIR__ . '/../data/companies.json'),
                JsonParser::DETECT_KEY_CONFLICTS | JsonParser::PARSE_TO_ASSOC
            );

            $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache'));
            $cacheItem->set(array_keys($companies));

            $this->cache->save($cacheItem);

            foreach ($companies as $key => $data) {
                $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));

                $companyData            = new \stdClass();
                $companyData->type      = $key;
                $companyData->name      = $data['name'];
                $companyData->brandname = $data['brandname'];

                $cacheItem->set($companyData);

                $this->cache->save($cacheItem);
            }

            $cacheInitialized->set(true);
            $this->cache->save($cacheInitialized);
        }
    }

    /**
     * @throws \Seld\JsonLint\ParsingException
     *
     * @return array[]|\Generator
     */
    private function getCompanies(): \Generator
    {
        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache'));

        if (!$cacheItem->isHit() || !$cacheItem->get()) {
            return;
        }

        $companies = $cacheItem->get();

        foreach ($companies as $key) {
            $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));

            yield $key => $cacheItem->get();
        }
    }
}
