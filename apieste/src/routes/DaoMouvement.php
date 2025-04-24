<?php
declare(strict_types=1);

use App\MiddleWare\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {

    $app->get('/getMouvementsByTest/{test_id}', function (Request $request, Response $response, array $args) {
        $test_id = $args['test_id'];
        $sql = "SELECT * FROM mouvement WHERE test_id = :test_id";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':test_id', $test_id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $db = null;
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());

    $app->post('/addMouvement', function (Request $request, Response $response) {
        $parsedBody = $request->getParsedBody();

        $distance = $parsedBody['distance'] ?? null;
        $temps = $parsedBody['temps'] ?? null;
        $direction_id = $parsedBody['direction_id'] ?? null;
        $test_id = $parsedBody['test_id'] ?? null;

        if (!isset($distance, $temps, $direction_id, $test_id)) {
            $error = ["message" => "Missing required fields"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "INSERT INTO `mouvement` (distance, temps, direction_id, test_id, test_model_id)
                SELECT :distance, :temps, :direction_id, :test_id, model_id
                FROM `test`
                WHERE id = :test_id";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':distance', $distance, PDO::PARAM_STR);
            $stmt->bindParam(':temps', $temps, PDO::PARAM_INT);
            $stmt->bindParam(':direction_id', $direction_id, PDO::PARAM_INT);
            $stmt->bindParam(':test_id', $test_id, PDO::PARAM_INT);

            $stmt->execute();

            $response->getBody()->write(json_encode(["message" => "Mouvement ajouté avec succès"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());
    
};
