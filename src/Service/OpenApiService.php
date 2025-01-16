<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

class OpenApiService
{
    private $schema;
    private $ollamaService;
    private $schemaPath;

    public function __construct(
        string $schemaPath,
        OllamaService $ollamaService
    ) {
        $this->schemaPath = $schemaPath;
        $this->ollamaService = $ollamaService;
        $this->loadSchema();
    }
    private function loadSchema(): void
    {
        if (!file_exists($this->schemaPath)) {
            throw new \RuntimeException("OpenAPI schema file not found at: {$this->schemaPath}");
        }
        $this->schema = Yaml::parseFile($this->schemaPath);
    }


    public function analyzeSpecification(): array
    {
        $schemaContent = Yaml::dump($this->schema, 10, 2);

        $prompt = <<<EOT
                Analyze this OpenAPI specification and provide a detailed analysis:

                $schemaContent

                Please analyze and return a JSON response covering:
                1. All available endpoints and their methods
                2. Data models and their relationships
                3. Security requirements
                4. Validation rules
                5. Potential improvements or issues
                6. API versioning information
                7. Required headers and authentication methods

                Format the response in a structured JSON format.
                EOT;

        return $this->ollamaService->generateResponse($prompt);
    }

    public function generateDocumentation(): array
    {
        $schemaContent = Yaml::dump($this->schema, 10, 2);

        $prompt = <<<EOT
                Generate comprehensive API documentation for this OpenAPI specification:

                $schemaContent

                Please include in JSON format:
                1. For each endpoint:
                - Full URL
                - HTTP method
                - Description
                - Request parameters (path, query, body)
                - Request headers
                - Response format
                - Example requests and responses
                - Authentication requirements

                2. For each model:
                - All properties and their types
                - Required fields
                - Validation rules
                - Relationships with other models

                3. General information:
                - Base URL
                - Authentication methods
                - Common headers
                - Error responses
                - Rate limiting

                4. Getting started guide:
                - Authentication setup
                - Basic request example
                - Common use cases

                Format as detailed, well-structured JSON documentation.
                EOT;

        return $this->ollamaService->generateResponse($prompt);
    }

    public function getEndpointInfo(string $path, string $method = 'get'): ?array
    {
        return $this->schema['paths'][$path][$method] ?? null;
    }

    public function getBaseUrl(): string
    {
        return $this->schema['servers'][0]['url'] ?? 'http://localhost:8000';
    }
}
