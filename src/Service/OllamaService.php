<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class OllamaService
{
    private $client;
    private $baseUrl;
    private $model;
    private $contextSize;

    public function __construct(
        string $baseUrl = 'http://localhost:11434',
        string $model = 'llama2',
        HttpClientInterface $client = null,
        int $contextSize = 4096
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
        $this->client = $client ?? HttpClient::create();
        $this->contextSize = $contextSize;
    }

    /**
     * Generate a response using Ollama
     *
     * @param string $prompt The input prompt
     * @param array $options Additional options for the model
     * @return array The parsed response
     * @throws \Exception If the API call fails
     */
    public function generateResponse(string $prompt, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'context_size' => $this->contextSize,
            'temperature' => 0.7,
        ], $options);

        try {
            $response = $this->client->request('POST', "{$this->baseUrl}/api/generate", [
                'json' => $payload,
                'timeout' => 7000,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new BadRequestException('Ollama API request failed: ' . $response->getContent(false));
            }

            $result = $response->toArray();

            // Try to parse the response as JSON if it looks like JSON
            if (
                str_starts_with(trim($result['response']), '{') &&
                str_ends_with(trim($result['response']), '}')
            ) {
                try {
                    return json_decode($result['response'], true) ?? ['response' => $result['response']];
                } catch (\JsonException $e) {
                    return ['response' => $result['response']];
                }
            }

            return ['response' => $result['response']];
        } catch (\Exception $e) {
            throw new \Exception('Failed to communicate with Ollama: ' . $e->getMessage());
        }
    }

    /**
     * Analyze text for specific information
     *
     * @param string $text Text to analyze
     * @param string $instruction Specific instruction for analysis
     * @return array Analysis results
     */
    public function analyzeText(string $text, string $instruction): array
    {
        $prompt = <<<EOT
                Analyze the following text according to these instructions:
                Instructions: $instruction

                Text to analyze:
                $text

                Provide your analysis in JSON format.
                EOT;

        return $this->generateResponse($prompt);
    }

    /**
     * Extract specific entities from text
     *
     * @param string $text Text to analyze
     * @param array $entities List of entities to extract
     * @return array Extracted entities
     */
    public function extractEntities(string $text, array $entities): array
    {
        $entitiesList = implode(', ', $entities);
        $prompt = <<<EOT
                Extract the following entities from the text:
                Entities to find: $entitiesList

                Text:
                $text

                Return a JSON object with the found entities and their values.
                EOT;

        return $this->generateResponse($prompt);
    }

    /**
     * Enhance API response with natural language
     *
     * @param array $apiData Raw API data
     * @param string $context Context about what to emphasize
     * @return array Enhanced response
     */
    public function enhanceApiResponse(array $apiData, string $context): array
    {
        $prompt = <<<EOT
                Convert this API data to a natural language response.
                Context: $context

                API Data:
                {$this->arrayToString($apiData)}

                Provide a user-friendly response highlighting the most important information.
                EOT;

        $response = $this->generateResponse($prompt);

        return [
            'natural_response' => $response['response'],
            'raw_data' => $apiData
        ];
    }

    /**
     * Set the model to use
     *
     * @param string $model Model name
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get currently used model
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Helper method to convert array to string representation
     *
     * @param array $array
     * @return string
     */
    private function arrayToString(array $array): string
    {
        return json_encode($array, JSON_PRETTY_PRINT);
    }
}
