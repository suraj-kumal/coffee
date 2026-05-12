<?php

//GLOBAL VAR
$head_on_route = "";
$body_on_route = "";
$admin = "/admin";
$adminLogin = "/adminlogin";
$create = "/admin/create";
$edit = "/admin/edit";

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

    case $admin:
        [$head_on_route, $body_on_route] = admin();
        break;

    case $adminLogin:
        [$head_on_route, $body_on_route] = adminLogin();
        break;
    case $create:
        [$head_on_route, $body_on_route] = create();
        break;
    case $edit:
        [$head_on_route, $body_on_route] = edit();
        break;
    case "/logout":
        logout();
        break;
    default:
        $slug = trim($uri, "/");
        [$head_on_route, $body_on_route] = coffee($slug);
}
//todo change to switch in future

//Middleware
function coffee($slug)
{
    $coffeeSlug = "test";
    $which_city = "kathmandu";

    // city route
    if ($slug === $which_city) {
        return cityBased($slug);
    }

    // coffee route
    if ($slug === $coffeeSlug) {
        return slugBased($slug);
    }

    // fallback
    return fourZeroFour();
}

function authGuard()
{
    if (
        !isset($_SESSION["admin_logged_in"], $_SESSION["admin_username"]) ||
        ($_SESSION["admin_logged_in"] == !true &&
            $_SESSION["admin_username"] != "SurajKumal")
    ) {
        header("Location: /adminlogin");
        exit();
    }
    return true;
}
//FUNCTIONS RENDERING HTML AND SERVER SIDE LOGIC
//

function home()
{
    $head = <<<HTML
    <title>Coffeemandu - your goto coffee shop directory</title>

    HTML;

    $body = <<<HTML
    <h1> this is body of main page </h1>
    HTML;

    return [$head, $body];
}

function cityBased($slug)
{
    //city logic
    $head = <<<HTML
    <title>Best Coffee shops in $slug</title>

    HTML;

    $body = <<<HTML
    <h1> Kathmandu </h1>
    HTML;

    return [$head, $body];
}

function slugBased($slug)
{
    //slug logic

    $slug_to_db = $slug;
    $head = <<<HTML
    <title>slug test</title>

    HTML;

    $body = <<<HTML
    <h1> slug </h1>
    HTML;

    return [$head, $body];
}

function admin()
{
    authGuard();

    $_SESSION["edit"] = 4;
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

function create()
{
    authGuard();

    $head = <<<HTML
        <title>create </title>
    HTML;

    $body = <<<HTML

    <p>create</p>

    HTML;

    return [$head, $body];
}

function edit()
{
    authGuard();

    $id = $_SESSION["edit"];

    echo $id;
    $head = <<<HTML
        <title>edit </title>
    HTML;

    $body = <<<HTML
        <p> edit </p>
    HTML;

    return [$head, $body];
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
