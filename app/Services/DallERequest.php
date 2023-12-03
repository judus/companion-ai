<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DallERequest
{
    protected string $apiKey;
    protected Client $client;

    protected string $size = '1024x1024';
    protected string $style = 'vivid';
    protected string $model = 'dall-e-3';
    protected string $quality = 'standard';

    protected string $format = 'url';

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 120.0,
        ]);
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * @param string $size
     *
     * @return DallERequest
     */
    public function setSize(string $size): DallERequest
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     *
     * @return DallERequest
     */
    public function setStyle(string $style): DallERequest
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     *
     * @return DallERequest
     */
    public function setModel(string $model): DallERequest
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuality(): string
    {
        return $this->quality;
    }

    /**
     * @param string $quality
     *
     * @return DallERequest
     */
    public function setQuality(string $quality): DallERequest
    {
        $this->quality = $quality;

        return $this;
    }

    public function sendRequest(string $prompt): mixed
    {
        try {
            $response = $this->client->post('/v1/images/generations', [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    "size" => $this->size,
                    "style" => $this->style,
                    "quality" => $this->quality,
                    "response_format" => $this->format,
                ],
            ]);

            if ($data = json_decode((string)$response->getBody(), true)) {
                $obj = new \stdClass();
                $obj->response = $data;
                $obj->url = $data['data'][0]['url'];
            }

            return $obj ?? null;
        } catch (\Exception $e) {
            Log::error('Request Exception: ', ['exception' => $e]);

            return collect([
                'code' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
        }
    }
}
