<?php

// defaults
//DADES PREDETERMINADES-------------------------

$template = 'home';
$db_connection = 'sqlite:..\private\users.db';
$configuration = array(
    '{FEEDBACK}'          => '',
    '{LOGIN_LOGOUT_TEXT}' => 'Identificar-me',
    '{LOGIN_LOGOUT_URL}'  => '/?page=login',
    '{METHOD}'            => 'POST', // es veuen els paràmetres a l'URL i a la consola (???)
    '{REGISTER_URL}'      => '/?page=register',
    '{SITE_NAME}'         => 'La meva pàgina'
);

// parameter processing

$parameters = array_merge($_GET, $_POST);

// Primer, processar intents de registre o login (POST)
if (isset($parameters['register'])) {
    $db = new PDO($db_connection);
    // Comprovar si l'usuari ja existeix
    $check = 'SELECT COUNT(*) FROM users WHERE user_name = :user_name';
    $query_check = $db->prepare($check);
    $query_check->bindValue(':user_name', $parameters['user_name']);
    $query_check->execute();
    $exists = $query_check->fetchColumn();
    if ($exists) {
        $configuration['{FEEDBACK}'] = "<mark>ERROR: Usuari ja existeix <b>"
                . htmlentities($parameters['user_name']) . '</b></mark>';
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = htmlentities($parameters['user_name']);
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
    } else {
        $sql = 'INSERT INTO users (user_name, user_password) VALUES (:user_name, :user_password)';
        $query = $db->prepare($sql);
        $query->bindValue(':user_name', $parameters['user_name']);
        $query->bindValue(':user_password', $parameters['user_password']); // Recomanat: guardar hash
        if ($query->execute() && $query->rowCount() > 0) {
            $configuration['{FEEDBACK}'] = 'Creat el compte <b>' . htmlentities($parameters['user_name']) . '</b>';
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
        } else {
            $configuration['{FEEDBACK}'] = "<mark>ERROR: No s'ha pogut crear el compte <b>"
                . htmlentities($parameters['user_name']) . '</b></mark>';
        }
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = htmlentities($parameters['user_name']);
    }
} else if (isset($parameters['login'])) {
    $db = new PDO($db_connection);
    $sql = 'SELECT * FROM users WHERE user_name = :user_name and user_password = :user_password';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $parameters['user_name']);
    $query->bindValue(':user_password', $parameters['user_password']);
    $query->execute();
    $result_row = $query->fetchObject();
    if ($result_row) {
        $configuration['{FEEDBACK}'] = '"Sessió" iniciada com <b>' . htmlentities($parameters['user_name']) . '</b>';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
        $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
    } else {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = htmlentities($parameters['user_name']);
    }
} else {
    // NAVEGACIÓ NORMAL
    if (isset($parameters['page'])) {
        if ($parameters['page'] == 'register') {
            $template = 'register';
            $configuration['{REGISTER_USERNAME}'] = '';
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        } else if ($parameters['page'] == 'login') {
            $template = 'login';
            $configuration['{LOGIN_USERNAME}'] = '';
        }
    }
}

// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;
