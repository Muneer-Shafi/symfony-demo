<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    private $ollamaService;
    private $openApiService;
    private $httpClient;

    public function __construct(
        OllamaService $ollamaService,
        OpenApiService $openApiService,
        HttpClientInterface $httpClient = null
    ) {
        $this->ollamaService = $ollamaService;
        $this->openApiService = $openApiService;
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function handleQuery(string $userQuery): array
    {
        // First, analyze the user's intent using Ollama
        $intentAnalysis = $this->analyzeIntent($userQuery);

        if ($this->isOrderQuery($intentAnalysis)) {
            // Extract order ID if present in the query
            $orderId = $this->extractOrderId($userQuery);

            if ($orderId) {
                return $this->getOrderDetails($orderId);
            } else {
                return $this->getOrdersList();
            }
        }

        // Default response if not an order query
        return [
            'type' => 'general_response',
            'content' => $this->ollamaService->generateResponse($userQuery)
        ];
    }

    private function analyzeIntent(string $query): array
    {
        $prompt = <<<EOT
    Analyze this user query and determine if it's asking about orders:
    Query: $query

    Provide a JSON response with:
    1. is_order_query: boolean
    2. specific_order: boolean
    3. extracted_id: string or null
    4. confidence: number (0-1)
    EOT;

        return $this->ollamaService->generateResponse($prompt);
    }

    private function isOrderQuery(array $analysis): bool
    {
        return $analysis['is_order_query'] ?? false;
    }

    private function extractOrderId(string $query): ?string
    {
        // Use Ollama to extract order ID from natural language
        $prompt = <<<EOT
        Extract the order ID from this query if present:
        Query: $query

        Return just the order ID number or null if not found.
        EOT;

        $response = $this->ollamaService->generateResponse($prompt);
        return $response['order_id'] ?? null;
    }

    private function getOrderDetails(string $orderId): array
    {
        try {
            $baseUrl = $this->openApiService->getBaseUrl();
            $endpoint = "/orders/{$orderId}";
            $endpointInfo = $this->openApiService->getEndpointInfo($endpoint, 'get');

            $response = $this->httpClient->request('GET', $baseUrl . $endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);

            $orderData = $response->toArray();

            // Generate a natural language response using Ollama
            $prompt = <<<EOT
            Convert this order data to a natural language response:
            Order Data: " . json_encode($orderData) . "

            Format it in a user-friendly way, highlighting:
            1. Order ID
            2. Status
            3. Total amount
            4. Delivery details
            5. Items ordered
            EOT;

            $naturalResponse = $this->ollamaService->generateResponse($prompt);

            return [
                'type' => 'order_details',
                'raw_data' => $orderData,
                'natural_response' => $naturalResponse
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => "Sorry, I couldn't find that order. Error: " . $e->getMessage()
            ];
        }
    }

    private function getOrdersList(): array
    {
        try {
            $baseUrl = $this->openApiService->getBaseUrl();
            $response = $this->httpClient->request('GET', $baseUrl . '/orders');
            $ordersData = $response->toArray();

            // Generate a natural language response for the orders list
            $prompt = <<<EOT
                Convert this orders list to a natural language summary:
                Orders Data: " . json_encode($ordersData) . "

                Provide a summary including:
                1. Total number of orders
                2. Recent orders
                3. Any notable patterns or status distributions
                EOT;

            $naturalResponse = $this->ollamaService->generateResponse($prompt);

            return [
                'type' => 'orders_list',
                'raw_data' => $ordersData,
                'natural_response' => $naturalResponse
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'message' => "Sorry, I couldn't fetch the orders list. Error: " . $e->getMessage()
            ];
        }
    }
}
