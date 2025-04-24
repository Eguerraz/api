<?php
declare(strict_types=1);

use App\MiddleWare\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {

    $app->put('/UpdateModelValide', function (Request $request, Response $response) {

        $data = $request->getParsedBody();
        $valeur = $data['valeur'] ?? null;

        if ($valeur === null) {
            $error = ["message" => "Le champ 'valeur' est obligatoire."];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            // Récupérer le dernier model_id
            $sqlGetLast = "SELECT idModelValide FROM `ModelValide` ORDER BY idModelValide DESC LIMIT 1";
            $stmt = $conn->query($sqlGetLast);
            $last = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$last) {
                $db = null;
                $response->getBody()->write(json_encode(["message" => "Aucun modèle trouvé dans ModelValide."]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $id = $last['idModelValide'];

            // Update de la valeur
            $sqlUpdate = "UPDATE `ModelValide` SET valeur = :valeur WHERE idModelValide = :id";
            $stmt = $conn->prepare($sqlUpdate);
            $stmt->bindParam(':valeur', $valeur, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $db = null;

            $response->getBody()->write(json_encode([
                "message" => "Valeur mise à jour avec succès.",
                "idModelValide" => $id,
                "valeur" => $valeur
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (PDOException $e) {
            $error = ["message" => "Erreur lors de la mise à jour : " . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

    })->add(new JwtMiddleware());

    $app->get('/getAllModelValide', function (Request $request, Response $response) {

        try {
            $db = new \App\config\dp();
            $conn = $db->connect();

            // Récupérer tous les modèles valides
            $sql = "SELECT * FROM `ModelValide`";
            $stmt = $conn->query($sql);
            $modelValides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;

            if ($modelValides) {
                $response->getBody()->write(json_encode($modelValides));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode(["message" => "Aucun modèle valide trouvé"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

        } catch (PDOException $e) {
            $error = ["message" => "Erreur lors de la récupération des modèles valides : " . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    })->add(new JwtMiddleware());


};
