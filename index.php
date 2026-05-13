<?php

//GLOBAL VAR
$head_on_route = "";
$body_on_route = "";
$admin = "/admin";
$admin_login_route = "/adminlogin";
$create_admin_route = "/admin/create";
$edit_admin_route = "/admin/edit";

//DATABASE

//database credientials
// $database_url = "localhost:3306";
// $database_name = "coffee";
// $database_username = "root";
// $database_password = "";

// //database connections
// $connection = mysqli_connect(
//     $database_url,
//     $database_username,
//     $database_password,
//     $database_name,
// );

// if (!$connection) {
//     [$head_on_route, $body_on_route] = fiveHundred();
// }
//
$host = "localhost:3306";
$db = "coffee";
$user = "root";
$pass = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected successfully";
} catch (\PDOException $e) {
    //throw new \PDOException($e->getMessage(), (int) $e->getCode());
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

    case $admin_login_route:
        [$head_on_route, $body_on_route] = adminLogin();
        break;
    case $create_admin_route:
        [$head_on_route, $body_on_route] = create();
        break;
    case $edit_admin_route:
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
    global $create_admin_route;
    global $pdo;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $location = $_POST["location"];
        $city = $_POST["city"];
        $cover_image = $_POST["cover_image"];
        $excerpt = $_POST["excerpt"];
        $description = $_POST["description"];
        $conclusion = $_POST["conclusion"];
        $instagram = $_POST["instagram"];
        $website = $_POST["website"];
        $meta_title = $_POST["meta_title"];
        $meta_description = $_POST["meta_desc"];
        $meta_keywords = $_POST["meta_keywords"];

        $published = $_POST["published"] === "true" ? 1 : 0;

        $slug = createSlug($name);

        $sql = "
                   INSERT INTO drink_coffee (
                       name,
                       slug,
                       location,
                       city,
                       cover_image,
                       excerpt,
                       description,
                       conclusion,
                       instagram,
                       website,
                       meta_title,
                       meta_description,
                       meta_keywords,
                       published
                   )
                   VALUES (
                       :name,
                       :slug,
                       :location,
                       :city,
                       :cover_image,
                       :excerpt,
                       :description,
                       :conclusion,
                       :instagram,
                       :website,
                       :meta_title,
                       :meta_description,
                       :meta_keywords,
                       :published
                   )
               ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":name" => $name,
            ":slug" => $slug,
            ":location" => $location,
            ":city" => $city,
            ":cover_image" => $cover_image,
            ":excerpt" => $excerpt,
            ":description" => $description,
            ":conclusion" => $conclusion,
            ":instagram" => $instagram,
            ":website" => $website,
            ":meta_title" => $meta_title,
            ":meta_description" => $meta_description,
            ":meta_keywords" => $meta_keywords,
            ":published" => $published,
        ]);

        // Get the ID of the newly inserted row
        $newId = $pdo->lastInsertId();
        // save slug in JSON
        addCoffee($newId, $slug);

        echo "New coffee added successfully!";
        echo "<br>ID: " . $newId;
        echo "<br>Slug: " . $slug;
    }

    $head = <<<HTML
        <title>create </title>
    HTML;

    $body = <<<HTML

    <p>create</p>

    <form action="$create_admin_route" method="post">
        <label for="publish">Choose to publish</label>
        <select name="published" id="publish">
          <option value="true">Publish</option>
          <option value="false" selected>Draft</option>
        </select>
        <Input type="text" name="name" placeholder="name" required><br>
        <Input type="text" name="location" placeholder="location" required><br>
        <Input type="text" name="city" placeholder="city" required><br>
        <Input type="text" name="cover_image" placeholder="cover image" required><br>

        <textarea name="excerpt" rows="5" cols="66">
            ........
        </textarea><br>
        <textarea name="description" rows="5" cols="66">
            ........
        </textarea><br>
        <textarea name="conclusion" rows="5" cols="66">
            ........
        </textarea><br>

        <Input type="text" name="instagram" placeholder="instagram"><br>
        <Input type="text" name="website" placeholder="website"><br>
        <Input type="text" name="meta_title" placeholder="meta title"><br>

        <Input type="text" name="meta_desc" placeholder="meta description"><br>

        <Input type="text" name="meta_keywords" placeholder="meta keywords"><br>


        <Input type="submit" value="button">
    </form>

    HTML;

    return [$head, $body];
}

function createSlug($name)
{
    $slug = strtolower(trim($name));

    // replace non letters/numbers with -
    $slug = preg_replace("/[^a-z0-9]+/", "-", $slug);

    // remove extra -
    $slug = trim($slug, "-");

    $coffee = readCoffee();

    $existingSlugs = array_column($coffee, "coffee_slug");

    $originalSlug = $slug;

    // check uniqueness
    while (in_array($slug, $existingSlugs)) {
        $random = rand(100, 999);
        $slug = $originalSlug . "-" . $random;
    }

    return $slug;
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

//JSON caching
function readCoffee()
{
    if (!file_exists("coffee.json")) {
        file_put_contents("coffee.json", json_encode([]));
    }

    $data = file_get_contents("coffee.json");
    return json_decode($data, true) ?? [];
}

function addCoffee($id, $coffee_slug)
{
    $coffee = readCoffee();

    $coffee[] = [
        "id" => $id,
        "coffee_slug" => $coffee_slug,
    ];

    file_put_contents("coffee.json", json_encode($coffee, JSON_PRETTY_PRINT));
}
function updateCoffee($id, $coffee_slug)
{
    $coffee = readCoffee();
    $coffee_found = false;
    foreach ($coffee as $items) {
        if ($items["id"] == $id) {
            $items["coffee_slug"] == $coffee_slug;
            $coffee_found = true;
            break;
        }
    }
    if ($coffee_found) {
        file_put_contents(
            "coffee.json",
            json_decode($coffee, JSON_PRETTY_PRINT),
        );
    }
    return $coffee_found;
}

function deleteCoffee($id)
{
    $coffee = readCoffee();
    foreach ($coffee as $key => $item) {
        if ($item["id"] == $id) {
            unset($coffee[$key]); // Remove the item
            file_put_contents(
                "coffee.json",
                json_encode(array_values($coffee), JSON_PRETTY_PRINT),
            );
            return true; // Successfully deleted
        }
    }
    return false; // ID not found
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
