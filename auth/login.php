<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once(__DIR__ . '/../db/Database.php');
require_once(__DIR__ . '/../helper/helper.php');
require_once(__DIR__ . '/../helper/constant.php');

function login($email, $password) {
    $errors = array();
    $inputs = array(
        'email' => $email,
        'password' => $password
    );

    $errors = validateInputs($inputs, $errors);

    if (!empty($errors)) {
        sendJsonResponse(400, $errors);
        return;
    }
    try {
        $connBvn = new Database(DB_USER, DB_PASS, DB_SERVERNAME, DB_NAME);

        $loginSql = "SELECT * FROM APPLICATION_LOGIN WHERE email = :email AND password = :password AND application = 'BVN_PORTAL'";

        $params = array(
            ':email' => $email,
            ':password' => $password
        );
        $loginOracle = $connBvn->executeQuery($loginSql, $params);

        if ($loginOracle) {

            $_SESSION['login'] = 'yes';
            $_SESSION['email'] = $loginOracle[0]['EMAIL'];
            $_SESSION['app'] = $loginOracle[0]['APPLICATION'];
            sendJsonResponse(200, 'Success', $loginOracle);
        } else {
            sendJsonResponse(400, 'Invalid login credentials');
        }
    } catch (Exception $e) {
        sendJsonResponse(500, $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    login(cleanInput($input['email']), cleanInput($input['password']));
}
?>
