<?php
require __DIR__ . '/../PHP/phpmailer/src/Exception.php';
require __DIR__ . '/../PHP/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../PHP/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Iniciem una nova sessió o continuem la que hi ha
session_start();


//DADES PREDETERMINADES-------------------------
$template = 'home';
$db_connection = 'sqlite:..\private\users.db';
$configuration = array(
    '{FEEDBACK}'          => '',
    '{LOGIN_LOGOUT_TEXT}' => 'Identificar-me',
    '{RECOVERY_URL}'       => '/?page=recovery',
    '{LOGIN_LOGOUT_URL}'  => '/?page=login',
    '{METHOD}'            => 'POST', // es veuen els paràmetres a l'URL i a la consola (???)
    '{REGISTER_URL}'      => '/?page=register',
    '{SITE_NAME}'         => 'La meva pàgina'
);

// Si ja hi ha una sessió iniciada, canviar els textos
if (isset($_SESSION['user_mail'])) {
    $configuration['{FEEDBACK}'] = 'Sessió iniciada com <b>' . htmlentities($_SESSION['user_mail']) . '</b>';
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
    $configuration['{LOGIN_LOGOUT_URL}'] = '/?page=logout';
}

// parameter processing
$parameters = array_merge($_GET, $_POST);

// Primer, processar intents de registre o login (POST)
if (isset($parameters['recovery'])) {
    // Procesar recuperación de contraseña
    $db = new PDO($db_connection);
    $sql = 'SELECT * FROM users WHERE user_mail = :user_mail';
    $query = $db->prepare($sql);
    $query->bindValue(':user_mail', $parameters['user_mail']);
    $query->execute();
    $user_mail = $query->fetchObject();
    if ($user_mail) {
    $configuration['{FEEDBACK}'] = 'T&apos;hem enviat un correu amb instruccions per canviar la contrasenya.';

    // antes: $mail = new PHPMailer(true);
$mail = new PHPMailer(true);

// debug collector
$debug = '';

try {
    $mail->SMTPDebug = 3; // 0=off, 1=client, 2=client+server, 3/4 = muy verboso
    $mail->Debugoutput = function($str, $level) use (&$debug) {
        $debug .= nl2br(htmlspecialchars($str)) . "<br>\n";
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'brostortillamario@gmail.com';
    $mail->Password   = 'sehkqoxuheaphlzk';
    // prueba STARTTLS/587 primero:
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Si 587 falla, prueba 465 (SMTPS)
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    // $mail->Port = 465;

    // Opcional (solo para desarrollo local si tienes problemas con certificados)
    // NO usar en producción:
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom('brostortillamario@gmail.com', 'La meva pàgina');
    $mail->addAddress($parameters['user_mail']);
    $mail->isHTML(true);
    $mail->Subject = 'Canvi de contrasenya';
    $mail->Body    = "Hola,<br><br>Per canviar la teva contrasenya, visita aquest enllaç:<br>
        <a href='http://localhost:8080/reset_password.php?mail=" . urlencode($parameters['user_mail']) . "'>Canviar contrasenya</a>";

    $mail->send();
    $configuration['{FEEDBACK}'] = 'Correu enviat correctament.';
} catch (Exception $e) {
    // Muestra tanto ErrorInfo como la excepción y el debug SMTP
    $msg = 'No s\'ha pogut enviar el correu. PHPMailer Error: ' . $mail->ErrorInfo;
    $msg .= '<br>Exception: ' . htmlspecialchars($e->getMessage());
    $msg .= '<br>Debug:<br>' . $debug;
    $configuration['{FEEDBACK}'] = $msg;
}

} else {
        $configuration['{FEEDBACK}'] = '<mark>ERROR: No existeix cap compte amb aquest correu</mark>';
    }
    $template = 'recovery';
    $configuration['{RECOVERY_MAIL}'] = htmlentities($parameters['user_mail']);
    $configuration['{RECOVERY_URL}'] = '/?page=recovery';
    $configuration['{LOGIN_URL}'] = '/?page=login';
    $configuration['{REGISTER_URL}'] = '/?page=register';
} else if (isset($parameters['register'])) {
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
        $checkmail = 'SELECT COUNT(*) FROM users WHERE user_mail = :user_mail';
        $query_check_mail = $db->prepare($checkmail);
        $query_check_mail->bindValue(':user_mail', $parameters['user_mail']);
        $query_check_mail->execute();
        $mail_exists = $query_check_mail->fetchColumn();

        if ($mail_exists) {
            $configuration['{FEEDBACK}'] = "<mark>ERROR: Ja existeix un compte amb el correu <b>"
                . htmlentities($parameters['user_mail']) . '</b></mark>';
        } else {
            // Guardem contrasenya com a hash
            $passwordHash = password_hash($parameters['user_password'], PASSWORD_DEFAULT);

            $sql = 'INSERT INTO users (user_name, user_password, user_mail) VALUES (:user_name, :user_password, :user_mail)';
            $query = $db->prepare($sql);
            $query->bindValue(':user_name', $parameters['user_name']);
            $query->bindValue(':user_password', $passwordHash);
            $query->bindValue(':user_mail', $parameters['user_mail']);
        

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
            $configuration['{REGISTER_MAIL}'] = '';
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
        } else if ($parameters['page'] == 'recovery') {
            // Mostrar formulario de recuperación
            $template = 'recovery';
            $configuration['{RECOVERY_MAIL}'] = '';
            $configuration['{RECOVERY_URL}'] = '/?page=recovery';
            $configuration['{LOGIN_URL}'] = '/?page=login';
            $configuration['{REGISTER_URL}'] = '/?page=register';
        }
    }
}

// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;