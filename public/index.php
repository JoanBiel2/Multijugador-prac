<?php

// Iniciem una nova sessió o continuem la que hi ha
session_start();


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

// Si ja hi ha una sessió iniciada, canviar els textos
if (isset($_SESSION['user_name'])) {
    $configuration['{FEEDBACK}'] = 'Sessió iniciada com <b>' . htmlentities($_SESSION['user_name']) . '</b>';
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
    $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
}

// parameter processing
$parameters = array_merge($_GET, $_POST);

// Primer, processar intents de registre o login (POST)
if (isset($parameters['register'])) {
    // Connexió a la base de dades per registrar nou usuari
    $db = new PDO($db_connection);

    // Comprovar si l'usuari ja existeix
    $check = 'SELECT COUNT(*) FROM users WHERE user_name = :user_name';
    $query_check = $db->prepare($check);
    $query_check->bindValue(':user_name', $parameters['user_name']);
    $query_check->execute();
    $exists = $query_check->fetchColumn();

    // Si l'usuari ja existeix, mostrar error
    if ($exists) {
        $configuration['{FEEDBACK}'] = "<mark>ERROR: Usuari ja existeix <b>"
                . htmlentities($parameters['user_name']) . '</b></mark>';
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = htmlentities($parameters['user_name']);
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
    } else {// Si l'usuari no existeix, el crea
        // Guardem contrasenya com a hash
        $passwordHash = password_hash($parameters['user_password'], PASSWORD_DEFAULT);

        $sql = 'INSERT INTO users (user_name, user_password) VALUES (:user_name, :user_password)';
        $query = $db->prepare($sql);
        $query->bindValue(':user_name', $parameters['user_name']);
        $query->bindValue(':user_password', $passwordHash); // Recomanat: guardar hash
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
    // Connexió a la base de dades per validar usuari i contrasenya
    $db = new PDO($db_connection);

    // Consultar només per nom d'usuari
    $sql = 'SELECT * FROM users WHERE user_name = :user_name';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $parameters['user_name']);
    $query->execute();
    $result_row = $query->fetchObject();

    if ($result_row && password_verify($parameters['user_password'], $result_row->user_password)) {
        // Si l'usuari és correcte, crear la sessió
        $_SESSION['user_name'] = $result_row->user_name;
        $configuration['{FEEDBACK}'] = '"Sessió" iniciada com <b>' . htmlentities($parameters['user_name']) . '</b>';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
        $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
    } else {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari o contrasenya incorrecta</mark>';
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = htmlentities($parameters['user_name']);
    }
} else {
    // NAVEGACIÓ NORMAL
    if (isset($parameters['page'])) {
        if ($parameters['page'] == 'register') {
            // Registrar-se
            $template = 'register';
            $configuration['{REGISTER_USERNAME}'] = '';
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        } else if ($parameters['page'] == 'login') {
            // Login
            $template = 'login';
            $configuration['{LOGIN_USERNAME}'] = '';
        } else if ($parameters['page'] == 'logout') {
            // Tancar sessió
            session_destroy();
            $configuration['{FEEDBACK}'] = 'Sessió tancada';
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Identificar-me';
            $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=login';
            $template = 'home';
        }
    }
}

// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;

// DEBUG: mostra el contingut de la sessió PHP
echo '<pre style="background:#eee; color:#333; padding:1em;">';
echo '$_SESSION = ';
var_dump($_SESSION);
echo '</pre>';
