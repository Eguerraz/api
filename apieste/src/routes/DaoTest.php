<?php
declare(strict_types=1);

use App\MiddleWare\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;


return function (App $app) {

    $app->get('/getAllTest', function (Request $request, Response $response) {
        $sql = "SELECT * FROM `test`";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
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
    $app->get('/getIdTest', function (Request $request, Response $response) {
        $sql = "SELECT id FROM `test`";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->query($sql);
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
    $app->get('/getTest/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id']; // Récupère l'ID depuis l'URL
        $sql = "SELECT * FROM `test` WHERE id = :id"; // Requête avec un paramètre

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Sécurisation contre les injections SQL
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            $db = null;
            if ($data) {
                $response->getBody()->write(json_encode($data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $error = ["message" => "Test not found"];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());
    $app->put('/updateTest/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id']; // Récupère l'ID depuis l'URL
        $parsedBody = $request->getParsedBody(); // Récupère les données envoyées

        // Récupère les valeurs à mettre à jour
        $n_mouvements = $parsedBody['n_mouvements'] ?? null;
        $distance_total = $parsedBody['distance_total'] ?? null;
        $t_deplacement = $parsedBody['t_deplacement'] ?? null;
        $model_id = $parsedBody['model_id'] ?? null;

        // Vérifie que toutes les valeurs sont présentes
        if (!isset($n_mouvements, $distance_total, $t_deplacement, $model_id)) {
            $error = ["message" => "Missing required fields"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "UPDATE `test` 
            SET n_mouvements = :n_mouvements, 
                distance_total = :distance_total, 
                t_deplacement = :t_deplacement, 
                model_id = :model_id 
            WHERE id = :id";

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':n_mouvements', $n_mouvements, PDO::PARAM_INT);
            $stmt->bindParam(':distance_total', $distance_total, PDO::PARAM_STR);
            $stmt->bindParam(':t_deplacement', $t_deplacement, PDO::PARAM_INT);
            $stmt->bindParam(':model_id', $model_id, PDO::PARAM_INT);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $response->getBody()->write(json_encode(["message" => "Test updated successfully"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode(["message" => "No changes made or test not found"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());
    $app->delete('/deleteTest/{id}', function (Request $request, Response $response, array $args) {
        $id = $args['id'];

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            // Supprimer d'abord les mouvements liés
            $sqlDeleteMouvements = "DELETE FROM mouvement WHERE test_id = :id";
            $stmtDeleteMouvements = $conn->prepare($sqlDeleteMouvements);
            $stmtDeleteMouvements->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDeleteMouvements->execute();

            // Ensuite, supprimer le test
            $sqlDeleteTest = "DELETE FROM test WHERE id = :id";
            $stmtDeleteTest = $conn->prepare($sqlDeleteTest);
            $stmtDeleteTest->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtDeleteTest->execute();

            $db = null;

            $response->getBody()->write(json_encode(["message" => "Test supprimé avec succès"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (PDOException $e) {
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());
    $app->post('/addTest', function (Request $request, Response $response) {
        // Récupère les données envoyées dans le corps de la requête
        $parsedBody = $request->getParsedBody();

        // Récupère les valeurs à insérer
        $n_mouvements = $parsedBody['n_mouvements'] ?? null;
        $distance_total = $parsedBody['distance_total'] ?? null;
        $t_deplacement = $parsedBody['t_deplacement'] ?? null;
        $model_id = $parsedBody['model_id'] ?? null;

        // Vérifie que toutes les valeurs sont présentes
        if (!isset($n_mouvements, $distance_total, $t_deplacement, $model_id)) {
            $error = ["message" => "Missing required fields"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Requête d'insertion
        $sql = "INSERT INTO `test` (n_mouvements, distance_total, t_deplacement, model_id) 
            VALUES (:n_mouvements, :distance_total, :t_deplacement, :model_id)";

        try {
            // Connexion à la base de données
            $db = new \App\config\dp();
            $conn = $db->connect();

            // Préparation et exécution de la requête
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':n_mouvements', $n_mouvements, PDO::PARAM_INT);
            $stmt->bindParam(':distance_total', $distance_total, PDO::PARAM_STR);
            $stmt->bindParam(':t_deplacement', $t_deplacement, PDO::PARAM_INT);
            $stmt->bindParam(':model_id', $model_id, PDO::PARAM_INT);

            $stmt->execute();

            // Vérification de l'insertion
            if ($stmt->rowCount() > 0) {
                $response->getBody()->write(json_encode(["message" => "Test added successfully"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            } else {
                $response->getBody()->write(json_encode(["message" => "No changes made"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } catch (PDOException $e) {
            // Gestion des erreurs
            $error = ["message" => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());



};


