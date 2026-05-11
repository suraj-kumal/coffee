<?php

//GLOBAL VAR
$head_on_route = "";
$body_on_route = "";

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
    [$head_on_route, $body_on_route] = fiveHundred();
}

session_start();

//ROUTING

$uri = $_SERVER["REQUEST_URI"];

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
    case "/logout":
        logout();
        break;
    default:
        $slug = trim($uri, "/");
        [$head_on_route, $body_on_route] = coffee($slug);
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

function coffee($slug)
{
    $coffeeSlug = "test";

    if ($slug !== $coffeeSlug) {
        return fourZeroFour();
    }
    $head = <<<HTML
        <title> test</title>
    HTML;

    $body = <<<HTML
        <p>test</p>
    HTML;

    return [$head, $body];
}

function admin()
{
    if (
        !isset($_SESSION["admin_logged_in"], $_SESSION["admin_username"]) ||
        ($_SESSION["admin_logged_in"] == !true &&
            $_SESSION["admin_username"] != "SurajKumal")
    ) {
        header("Location: /adminlogin");
        exit();
    }
    $head = <<<HTML
    <title>Admin Page</title>
    HTML;

    $body = <<<HTML
    <h1>admin</h1>

    <form action="/logout" method="POST">
        <button>Logout</button>
    </form>
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
            $_SESSION["admin_username"] = "SurajKumal";
            header("Location: /admin");
            exit();
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

function logout()
{
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Destroy only the specific admin session variables
        unset($_SESSION["admin_logged_in"]);
        unset($_SESSION["admin_username"]);
        header("Location: /adminlogin");
        exit();
    }
}

function fourZeroFour()
{
    $head = "";
    $body = <<<HTML
        <h1>404 not found <h1>
    HTML;
    return [$head, $body];
}

function fiveHundred()
{
    $head = <<<HTML
        <title>Internal Server Error </title>
    HTML;

    $body = <<<HTML
        <h1>Internal Server Error </title>
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
        <style>
            body {
                font-family: 'AkzidenzGrotesk', sans-serif;
            }
        </style>
    </head>
    <body >
        <h1 class="text-3xl">COFFEEMANDU </h1>
        <?php echo $body_on_route; ?>
    </body>
</html>
