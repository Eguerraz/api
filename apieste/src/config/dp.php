<?php
// dp.php
namespace App\Config;

use PDO;

class dp
{
    private $dbhost = 'mysql-guerraz.alwaysdata.net'; // Host
    private $dbuser = 'guerraz';                      // User
    private $dbpass = 'Este2003.';       // Password
    private $dbname = 'guerraz_robot';        // Database name

    public function connect() {
        // Préparation de la chaîne de connexion
        $prepare_conn_str = "mysql:host={$this->dbhost};dbname={$this->dbname}";

        // Création de l'objet PDO
        $dbConn = new PDO($prepare_conn_str, $this->dbuser, $this->dbpass);

        // Configuration des attributs PDO
        $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retourne la connexion
        return $dbConn;
    }
}
?>
