<?php
declare(strict_types=1);

use App\MiddleWare\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;


return function (App $app) {

    $app->get('/getAllDirection', function (Request $request, Response $response) {
        $sql = "SELECT * FROM `direction`";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
            $entrainements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $db = null;

            $response->getBody()->write(json_encode($entrainements ?: ["message" => "Aucun entraînement trouvé"]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus($entrainements ? 200 : 404);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());

};


