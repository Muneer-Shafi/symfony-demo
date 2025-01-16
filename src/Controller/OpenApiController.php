<?php
// src/Controller/OpenApiController.php
namespace App\Controller;

use App\Service\OpenApiService;
use App\Service\OllamaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OpenApiController extends AbstractController
{



    public function __construct(
        private OpenApiService $openApiService,
         private OllamaService $ollamaService
         )
    {
    }



     #[Route(path:'/api/spec/analyze', name:'analyze_spec', methods:['POST'])]
    public function analyzeSpec(): JsonResponse
    {
        try {
            $analysis = $this->openApiService->analyzeSpecification();
            return new JsonResponse($analysis);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }


    #[Route(path:'/api/spec/documentation', name:'generate_documentation', methods:['POST'])]
    public function generateDocumentation(): JsonResponse
    {
        try {
            $documentation = $this->openApiService->generateDocumentation();
            return new JsonResponse($documentation);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
