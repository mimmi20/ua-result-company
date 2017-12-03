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

use Assert\Assertion;
use Assert\AssertionFailedException;
use BrowserDetector\Loader\LoaderInterface;
use BrowserDetector\Loader\NotFoundException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

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
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var self|null
     */
    private static $instance;

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param \Psr\Log\LoggerInterface          $logger
     */
    private function __construct(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->cache  = $cache;
        $this->logger = $logger;
    }

    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param \Psr\Log\LoggerInterface          $logger
     *
     * @return self
     */
    public static function getInstance(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        if (null === self::$instance) {
            self::$instance = new self($cache, $logger);
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
        try {
            $this->init();
        } catch (InvalidArgumentException | ParsingException $e) {
            $this->logger->error($e);

            return false;
        }

        try {
            $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));

            return $cacheItem->isHit();
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
        }

        return false;
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
        try {
            $this->init();
        } catch (InvalidArgumentException | ParsingException $e) {
            throw new NotFoundException('the company with key "' . $key . '" was not found', 0, $e);
        }

        if (!$this->has($key)) {
            throw new NotFoundException('the company with key "' . $key . '" was not found');
        }

        try {
            $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));
            $company   = $cacheItem->get();
        } catch (InvalidArgumentException $e) {
            throw new NotFoundException('the company with key "' . $key . '" was not found', 0, $e);
        }

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
        try {
            $this->init();
        } catch (InvalidArgumentException | ParsingException $e) {
            throw new NotFoundException('the company with name "' . $name . '" was not found', 0, $e);
        }

        try {
            foreach ($this->getCompanies() as $key => $companyData) {
                if ($name !== $companyData->name) {
                    continue;
                }

                return $this->load($key);
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
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
        try {
            $this->init();
        } catch (InvalidArgumentException | ParsingException $e) {
            throw new NotFoundException('the company with brand name "' . $name . '" was not found', 0, $e);
        }

        try {
            foreach ($this->getCompanies() as $key => $companyData) {
                if ($name !== $companyData->brandname) {
                    continue;
                }

                return $this->load($key);
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
        }

        throw new NotFoundException('the company with brand name "' . $name . '" was not found');
    }

    /**
     * initializes cache
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
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
                $key       = (string) $key;
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
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @return \Generator|\stdClass[]
     */
    private function getCompanies(): \Generator
    {
        $cacheItem = $this->cache->getItem(hash('sha512', 'company-cache'));

        if (!$cacheItem->isHit() || !$cacheItem->get()) {
            return;
        }

        $companies = $cacheItem->get();

        foreach ($companies as $key) {
            $cacheItem  = $this->cache->getItem(hash('sha512', 'company-cache-' . $key));
            $cacheValue = $cacheItem->get();

            try {
                Assertion::isInstanceOf($cacheValue, '\stdClass');
            } catch (AssertionFailedException | \Assert\InvalidArgumentException $e) {
                $this->logger->error('a company with key "' . $key . '" was not found in the cache');

                continue;
            }

            yield $key => $cacheValue;
        }
    }
}
