<?php

//DATABASE

//database credientials
$database_url = "localhost:3306";
$database_name = "coffee";
$database_username = "root";
$database_password = "";

//database connections
$connection = mysqli_connect(
    $database_url,
    $database_username,
    $database_password,
    $database_name,
);

if (!$connection) {
    die("Connection Failed :" . mysqli_connect_error());
}

//ROUTING
//base dir and removing trailing slash
$base = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/");

//request uri
$uri = substr($_SERVER["REQUEST_URI"], strlen($base));
$uri = parse_url($uri, PHP_URL_PATH);

// if ($uri == "/" || $uri == "") {
//     echo "I love coffee";
// } elseif ($uri == "/admin") {
//     echo "admin";
// } else {
//     echo "404 Not Found";
// }

//no comparison so switch
//
//
//

session_start();

$head_on_route = "";
$body_on_route = "";

switch ($uri) {
    case "":
    case "/":
        [$head_on_route, $body_on_route] = home();
        break;

    case "/admin":
        [$head_on_route, $body_on_route] = admin();
        break;

    case "/adminlogin":
        [$head_on_route, $body_on_route] = adminLogin();
        break;
    default:
        http_response_code(404);
        $body_on_route = "<h1>404 Not Found</h1>";
}

//todo change to switch in future

//FUNCTIONS RENDERING HTML AND SERVER SIDE LOGIC
//

function home()
{
    $head = <<<HTML
    <title>Best Coffee shops in Kathmandu</title>

    HTML;

    $body = <<<HTML
    <h1> this is body of main page </h1>
    HTML;

    return [$head, $body];
}

function admin()
{
    $head = <<<HTML
    <title>Admin Page</title>
    HTML;

    $body = <<<HTML
    <h1>admin</h1>
    HTML;

    return [$head, $body];
}

function adminLogin()
{
    $adminLogin = "admin";
    $adminPassword = "secret123";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user = $_POST["username"];
        $pass = $_POST["password"];

        if ($user == $adminLogin && $pass == $adminPassword) {
            $_SESSION["admin_logged_in"] = true;
            $_SESSION["admin_username"] = $user;
        } else {
            echo "wrong credientials";
        }
    }

    $head = <<<HTML
        <title> admin only</title>
    HTML;

    $body = <<<HTML
            <h1>Admin Login</h1>
            <form action="/adminlogin" method="post">
                <Input type="text" placeholder="username" name="username" required>
                <Input type="password" placeholder="password" name="password" required>
                <Input type="submit" value="login">
            </form>
    HTML;

    return [$head, $body];
}
?>

<!-- RENDER LAYOUT -->

<html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        <link href="https://fonts.cdnfonts.com/css/akzidenzgrotesk" rel="stylesheet">
        <?php echo $head_on_route; ?>
    </head>
    <body>
        <h1>coffee shop </h1>
        <?php echo $body_on_route; ?>
    </body>
</html>
