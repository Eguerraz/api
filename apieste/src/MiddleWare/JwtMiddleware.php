<?php

namespace App\MiddleWare;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\MiddleWare\JwtHelper; // Importation de la classe JwtHelper

class JwtMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader) {
// Extract the token from the header (assuming it's "Bearer <token>")
            list($jwt) = sscanf($authHeader, 'Bearer %s');
            if ($jwt) {
// Validate the token
                $decoded = JwtHelper::validateToken($jwt);
                if ($decoded) {
// Add user data to request (if needed)
                    $request = $request->withAttribute('user', $decoded->data);
                    return $handler->handle($request);
                }
            }
        }
        $response = new \Slim\Psr7\Response(); // If token is invalid or not present, return 401 Unauthorized
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

}