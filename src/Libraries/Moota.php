<?php

/**
 * ------------------------------------------------------------------------------------------------------
 * Unofficial Moota.co package for Laravel framework
 * ------------------------------------------------------------------------------------------------------
 *
 * Moota.co adalah layanan untuk mengelola mutasi bank dalam satu dasbor dan cek transaksi secara otomatis.
 * Moota.co mendukung berbagai bank lokal seperti Mandiri, BCA, BNI, Bank Muamalat, dan Bank BRI.
 *
 * Package (tidak resmi) ini ditujukan pada framework Laravel untuk kemudahan penggunaan layanan
 * yang disediakan oleh API Moota.co.
 */

namespace Yugo\Moota\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Moota
{
    /**
     * GuzzleHttp client
     *
     * @var Client
     */
    private $http;

    /**
     * Default headers for all request.
     *
     * @var array
     */
    private $httpHeaders = [];

    /**
     * Show error HTTP from Guzzle client.
     *
     * @var boolean
     */
    private $httpError = false;

    /**
     * Set default http timeout request.
     *
     * @var integer
     */
    private $httpTimeout = 30;

    /**
     * Default bank id.
     *
     * @var string
     */
    private $bankId = '';

    /**
     * Cache response data from API.
     *
     * @var boolean
     */
    private $cache = false;

    /**
     * Cahe time in minutes.
     *
     * @var integer
     */
    private $cacheTime = 60;

    /**
     * Default cache name for every method.
     *
     * @var string
     */
    private $cacheName = '';

    public function __construct()
    {
        abort_if(empty(config('moota.host')), 500, trans('moota::moota.no_host'));
        abort_if(empty(config('moota.token')), 500, trans('moota::moota.no_token'));

        // override default http timeout
        if (!empty(config('moota.http.timeout')) and is_integer(config('moota.http.timeout'))) {
            $this->httpTimeout = config('moota.http.timeout');
        }

        $this->http = new Client([
            'base_uri' => config('moota.host'),
            'http_errors' => $this->httpError,
            'timeout' => $this->httpTimeout,
        ]);

        $this->httpHeaders = [
            'Authorization' => 'Bearer ' . config('moota.token'),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Parse response from Guzzle, check status code, and return the value.
     *
     * @param Response $response
     * @return Collection
     */
    private function response(Response $response, string $url): Collection
    {
        $body = json_decode((string) $response->getBody());

        if (!empty($body->status) and $body->status === 'error') {
            Log::error(sprintf('Moota HTTP response: %s', $body->message), [
                'url' => $url,
                'headers' => $this->httpHeaders,
            ]);
        } else {
            if ($this->cache === true and !empty($this->cacheName)) {
                Cache::put($this->cacheName, collect($body), $this->cacheTime);
            }
        }

        return collect($body);
    }

    /**
     * Flag data to store in cache for x minute(s).
     *
     * @param integer $minutes
     * @return self
     */
    public function cache(int $minutes = 60): self
    {
        $this->cache = true;
        $this->cacheTime = $minutes;

        return $this;
    }

    /**
     * Get registered profile based on provided token.
     *
     * @link https://app.moota.co/developer/docs#menampilkan-profil
     * @return Collection
     */
    public function profile(): Collection
    {
        $this->cacheName = 'moota.profile';
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = $this->http->get('profile', [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Show current balance represents as points.
     *
     * @link https://app.moota.co/developer/docs#menampilkan-saldo-user
     * @return Collection
     */
    public function balance(): Collection
    {
        $this->cacheName = 'moota.balance';
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = $this->http->get('balance', [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Get all registered banks.
     *
     * @link https://app.moota.co/developer/docs#daftar-akun-bank
     * @return Collection
     */
    public function banks(): Collection
    {
        $this->cacheName = 'moota.banks';
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = $this->http->get('bank', [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Get detailed bank by bank id.
     *
     * @link https://app.moota.co/developer/docs#detail-akun-bank
     * @param string $bankId
     * @return Collection
     */
    public function bank(string $bankId): Collection
    {
        $this->cacheName = 'moota.bank.' . $bankId;
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = $this->http->get("bank/{$bankId}", [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Prepare to get muation from single bank.
     *
     * @param string $bankId
     * @return self
     */
    public function mutation(string $bankId): self
    {
        $this->bankId = $bankId;

        return $this;
    }

    /**
     * Get mutation from current month.
     *
     * @link https://app.moota.co/developer/docs#data-mutasi-bulan-ini
     * @return Collection
     */
    public function month(): Collection
    {
        $this->cacheName = 'moota.bank.mutation.month.' . $this->bankId;
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = $this->http->get("bank/{$this->bankId}/mutation", [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Get latest mutation.
     *
     * @link https://app.moota.co/developer/docs#data-mutasi-terakhir
     * @param integer $limit
     * @return Collection
     */
    public function latest(int $limit = 20): Collection
    {
        $this->cacheName = 'moota.bank.mutation.latest.' . $this->bankId;
        if ($this->cache === true and Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        abort_if($limit < 10, 500, trans('moota::moota.min_limit'));
        abort_if($limit > 20, 500, trans('moota::moota.max_limit.'));

        $response = $this->http->get("bank/{$this->bankId}/mutation/recent/$limit", [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Search mutation by amount from single bank.
     *
     * @link https://app.moota.co/developer/docs#mencari-data-mutasi-berdasarkan-nominal
     * @param float $amount
     * @return Collection
     */
    public function amount(float $amount): Collection
    {
        abort_if($this->cache === true, 500, trans('moota::moota.cache_no_supported', [
            'method' => __FUNCTION__,
        ]));

        $response = $this->http->get("bank/{$this->bankId}/mutation/search/{$amount}", [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }

    /**
     * Search mutation by description from single bank.
     *
     * @link https://app.moota.co/developer/docs#mencari-data-mutasi-berdasarkan-deskripsi
     * @param string $description
     * @return Collection
     */
    public function description(string $description): Collection
    {
        abort_if($this->cache === true, 500, trans('moota::moota.cache_no_supported', [
            'method' => __FUNCTION__,
        ]));
        
        $response = $this->http->get("bank/{$this->bankId}/mutation/search/description/{$description}", [
            'headers' => $this->httpHeaders,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            },
        ]);

        return $this->response($response, $url);
    }
}
