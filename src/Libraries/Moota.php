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
use Illuminate\Support\Collection;

class Moota
{
    /**
     * GuzzleHttp client
     *
     * @var Client
     */
    private $client;

    /**
     * Default headers for all request.
     *
     * @var array
     */
    private $headers = [];

    /**
     * Show error HTTP from Guzzle client.
     *
     * @var boolean
     */
    private $httpError = false;

    /**
     * Default bank id.
     *
     * @var string
     */
    private $bankId = '';

    public function __construct()
    {        
        abort_if(empty(config('moota.host')), 500, trans('moota::moota.no_host'));
        abort_if(empty(config('moota.token')), 500, trans('moota::moota.no_token'));

        $this->http = new Client([
            'base_uri' => config('moota.host'),
            'http_errors' => $this->httpError,
        ]);

        $this->headers = [
            'Authorization' => 'Bearer ' . config('moota.token'),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get registered profile based on provided token.
     *
     * @link https://app.moota.co/developer/docs#menampilkan-profil
     * @return Collection
     */
    public function profile(): Collection
    {
        $response = $this->http->get('profile', [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
    }

    /**
     * Show current balance represents as points.
     *
     * @link https://app.moota.co/developer/docs#menampilkan-saldo-user
     * @return Collection
     */
    public function balance(): Collection
    {
        $response = $this->http->get('balance', [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
    }

    /**
     * Get all registered banks.
     *
     * @link https://app.moota.co/developer/docs#daftar-akun-bank
     * @return Collection
     */
    public function banks(): Collection
    {
        $response = $this->http->get('bank', [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
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
        $response = $this->http->get("bank/{$bankId}", [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
    }

    /**
     * Prepare to get muation from single bank.
     *
     * @param string $bankId
     * @return Collection
     */
    public function mutation(string $bankId): Collection
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
        $response = $this->http->get("bank/{$this->bankId}/mutation", [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
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
        abort_if($limit < 10, 500, trans('moota::moota.min_limit'));
        abort_if($limit > 20, 500, trans('moota::moota.max_limit.'));

        $response = $this->http->get("bank/{$this->bankId}/mutation/recent/$limit", [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
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
        $response = $this->http->get("bank/{$this->bankId}/mutation/search/{$amount}", [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
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
        $response = $this->http->get("bank/{$this->bankId}/mutation/search/description/{$description}", [
            'headers' => $this->headers,
        ]);

        return collect(json_decode((string) $response->getBody()));
    }
}
