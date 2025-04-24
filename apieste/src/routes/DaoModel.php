<?php
declare(strict_types=1);

use App\MiddleWare\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;


return function (App $app) {

    // Récupérer tous les noms des modèles
    $app->get('/getAllModelNames', function (Request $request, Response $response) {
        $sql = "SELECT model_name FROM `model`";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
            $modelNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $db = null;
            $response->getBody()->write(json_encode($modelNames));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());

    $app->get('/getModelByName/{model_name:.*}', function (Request $request, Response $response, array $args) {
        $model_name = $args['model_name'];
        $model_name = urldecode($model_name); // Décoder l'URL pour récupérer le bon format

        $sql = "SELECT * FROM `model` WHERE model_name = :model_name";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':model_name', $model_name, PDO::PARAM_STR);
            $stmt->execute();

            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            $db = null;

            $response->getBody()->write(json_encode($model ?: ["message" => "Modèle non trouvé"]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus($model ? 200 : 404);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());

    $app->get('/getModelById/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id']; // Récupérer l'ID passé dans l'URL

        $sql = "SELECT * FROM `model` WHERE id = :id"; // Requête SQL pour chercher le modèle par ID

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Liaison de l'ID avec la requête
            $stmt->execute();

            $model = $stmt->fetch(PDO::FETCH_ASSOC); // Récupérer le modèle trouvé
            $db = null;

            // Vérification si le modèle existe
            $response->getBody()->write(json_encode($model ?: ["message" => "Modèle non trouvé"]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus($model ? 200 : 404);
        } catch (PDOException $e) {
            // En cas d'erreur de connexion ou autre
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());



    $app->post('/AddModel', function (Request $request, Response $response) {
        $data = $request->getParsedBody();

        $model_name = $data['model_name'] ?? null;
        $weights_ih = $data['weights_ih'] ?? null;
        $weights_ho = $data['weights_ho'] ?? null;

        if ($model_name === null || $weights_ih === null || $weights_ho === null) {
            $error = ["message" => "Les champs 'model_name', 'weights_ih' et 'weights_ho' sont obligatoires."];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Conversion en JSON pour stockage correct
        $weights_ih = json_encode($weights_ih);
        $weights_ho = json_encode($weights_ho);

        $sql = "INSERT INTO `model` (`model_name`, `weights_ih`, `weights_ho`) 
            VALUES (:model_name, :weights_ih, :weights_ho)";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();
            $stmt = $conn->prepare($sql);

            // Utiliser LOB pour stocker les données JSON si elles sont volumineuses
            $stmt->bindParam(':model_name', $model_name, PDO::PARAM_STR);
            $stmt->bindParam(':weights_ih', $weights_ih, PDO::PARAM_LOB);
            $stmt->bindParam(':weights_ho', $weights_ho, PDO::PARAM_LOB);

            $stmt->execute();

            $modelId = $conn->lastInsertId();
            $db = null;

            $response->getBody()->write(json_encode([
                "message" => "Modèle ajouté avec succès.",
                "model_id" => $modelId
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (PDOException $e) {
            $error = ["message" => "Erreur lors de l'ajout du modèle : " . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());


};
