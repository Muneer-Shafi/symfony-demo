<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    private $chatbotService;

    // public function __construct(ChatbotService $chatbotService)
    // {
    //     $this->chatbotService = $chatbotService;
    // }


    #[Route(path:'api/chat', name:'chat_endpoint', methods:['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userQuery = $data['message'] ?? '';

        $response = $this->chatbotService->handleQuery($userQuery);

        return new JsonResponse($response);
    }
}
