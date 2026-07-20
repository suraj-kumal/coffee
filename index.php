<?php



//GLOBAL VAR
$head_on_route = "";
$body_on_route = "";
$admin = "/admin";
$admin_login_route = "/adminlogin";
$create_admin_route = "/admin/create";
$edit_admin_route = "/admin/edit";
$admin_user = "admin";
$admin_pass = "secret123";
//DATABASE


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
        $page = 1;
        [$head_on_route, $body_on_route] = home($page);
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
//todo change to match in future

//Middleware
function coffee($slug)
{
    $which_city = ["kathmandu", "pokhara"];
    $citypage = 1;


    // Handle pagination route like kathmandu/2
    // it is an array
    $parts = explode("/", trim($slug, "/"));


    if (count($parts) > 1) {
           $city = $parts[0];
           $citypage = $parts[1];

           if (!is_numeric($citypage) || (int)$citypage < 1) {
               return fourZeroFour();
           }

           $slug = $city;
       }
       if (in_array($slug, $which_city)) {
           return cityBased($slug,(int)$citypage);
       }


    if (is_numeric($slug)) {
        $page = (int) $slug;

        if ($page < 1) {
            return fourZeroFour();
        }

        return home($page);
    }

    // city route


    $coffee = readCoffee();

    // coffee route
    if (slugExists($slug) && (int) $coffee[$slug][1] === 1) {
        return slugBased($slug);
    }

    // fallback
    return fourZeroFour();
}

function authGuard()
{
    global $admin_user;
    if (
        !isset($_SESSION["admin_logged_in"], $_SESSION["admin_username"]) ||
        ($_SESSION["admin_logged_in"] == !true &&
            $_SESSION["admin_username"] != $admin_user)
    ) {
        header("Location: /adminlogin");
        exit();
    }
    return true;
}

//FUNCTIONS RENDERING HTML AND SERVER SIDE LOGIC
//


function home($page = 1)
{
    global $pdo;
    $perPage = 7;
    $page = max(1, (int) $page);
    $offset = ($page - 1) * $perPage;

    try {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM drink_coffee");
        $totalRows = (int) $countStmt->fetchColumn();
        $totalPages = (int) ceil($totalRows / $perPage);

        if ($totalPages > 0 && $page > $totalPages) {
            return fourZeroFour();
        }

        $stmt = $pdo->prepare("
            SELECT id, name, location, city, cover_image, slug, excerpt, priority
            FROM drink_coffee
            WHERE published = 1
            ORDER BY
                CASE WHEN priority IS NULL THEN 1 ELSE 0 END,
                priority DESC,
                RAND()
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coffees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return fourZeroFour();
    }

    // pass offset so renderList can number rows correctly across pages
    $coffeeHtml = renderList(coffees: $coffees, offset: $offset);

    // PAGINATION
    $pagination = "<nav class='max-w-6xl mx-auto px-6 md:px-12 py-12 flex items-center gap-6 font-mono text-sm'>";
    if ($page > 1) {
        $prevPage = $page - 1;
        $pagination .= "<a href='/{$prevPage}' class='uppercase tracking-widest text-[#2B1B12] hover:text-[#B23A2E] transition-colors'>&larr; Prev</a>";
    }
    $pagination .= "<div class='flex gap-3 mx-auto'>";
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $page) {
            $pagination .= "<span class='font-bold text-[#B23A2E] underline underline-offset-4'>{$i}</span>";
        } else {
            $pagination .= "<a href='/{$i}' class='text-[#8C7B6B] hover:text-[#2B1B12] transition-colors'>{$i}</a>";
        }
    }
    $pagination .= "</div>";
    if ($page < $totalPages) {
        $nextPage = $page + 1;
        $pagination .= "<a href='/{$nextPage}' class='uppercase tracking-widest text-[#2B1B12] hover:text-[#B23A2E] transition-colors'>Next &rarr;</a>";
    }
    $pagination .= "</nav>";

    $head = "
        <title>Hidden Beans</title>
    ";

    $body = "
        <section class='border-b border-[#2B1B12]/15 px-6 md:px-12 py-16 md:py-24 bg-[#EFEAE2]'>
            <div class='max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8 items-end'>
                <div class='md:col-span-8'>
                    <p class='uppercase tracking-[0.25em] text-xs font-bold text-[#B23A2E] mb-4'>Nepal Coffee Shop Directory</p>
                    <h1 class='text-6xl md:text-8xl font-black tracking-tight text-[#2B1B12] leading-[0.88]'>HIDDEN<br>BEANS</h1>
                </div>
                <div class='md:col-span-4 md:text-right'>
                    <p class='text-sm text-[#8C7B6B] leading-relaxed max-w-xs md:ml-auto'>A running index of independent coffee shops found, logged, and mapped one listing at a time.</p>
                    <p class='mt-6 font-mono text-xs text-[#2B1B12] tracking-widest'>N&deg; {$totalRows} LISTED</p>
                </div>
            </div>
        </section>

        <section class='px-6 md:px-12 pt-10'>
            <div class='max-w-6xl mx-auto'>
                <div class='flex items-baseline justify-between border-b border-[#2B1B12] pb-3 mb-2 font-mono text-xs uppercase tracking-widest text-[#8C7B6B]'>
                    <span>Listing</span>
                    <span>Page {$page} / {$totalPages}</span>
                </div>
                {$coffeeHtml}
            </div>
        </section>

        {$pagination}
    ";

    return [$head, $body];
}

function renderList(array $coffees, int $offset = 0)
{
    $coffeeHtml = "";
    $index = $offset;

    foreach ($coffees as $coffee) {
        $index++;
        $number = str_pad($index, 2, "0", STR_PAD_LEFT);

        $name = htmlspecialchars($coffee["name"]);
        $city = htmlspecialchars($coffee["city"]);
        $excerpt = htmlspecialchars($coffee["excerpt"]);
        $cover_image = htmlspecialchars($coffee["cover_image"]);
        $slug = htmlspecialchars($coffee["slug"]);

        $coffeeHtml .= "
            <a href='/{$slug}' class='group grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 items-center py-6 border-b border-[#2B1B12]/15 hover:bg-[#2B1B12]/[0.03] transition-colors'>
                <div class='md:col-span-1 font-mono text-xs text-[#B23A2E]'>{$number}</div>

                <div class='md:col-span-2'>
                    <img src='{$cover_image}' alt='{$name}' loading='lazy' width='400' height='218'
                         class='w-full h-20 md:h-16 object-cover grayscale group-hover:grayscale-0 transition-all duration-300'>
                </div>

                <div class='md:col-span-6'>
                    <h2 class='text-lg font-bold text-[#2B1B12] tracking-tight transition-colors group-hover:text-[#B23A2E]'>{$name}</h2>
                    <p class='text-sm text-[#8C7B6B] font-mono'>{$city}</p>
                </div>

                <div class='md:col-span-3'>
                    <p class='text-sm text-[#2B1B12]/70 line-clamp-2'>{$excerpt}</p>
                </div>
            </a>
        ";
    }

    return "<div class='coffee-list'>{$coffeeHtml}</div>";
}

function cityBased($slug, $cityPage)
{
    global $pdo;
    $perPage = 7;
    $page = max(1, (int) $cityPage);
    $offset = ($page - 1) * $perPage;

    try {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM drink_coffee
            WHERE LOWER(city) = LOWER(:slug)
        ");
        $countStmt->execute([':slug' => $slug]);
        $totalRows = $countStmt->fetchColumn();

        if ((int) $totalRows == 0) {
            return fourZeroFour();
        }

        $totalPages = (int) ceil($totalRows / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            return fourZeroFour();
        }

        $stmt = $pdo->prepare("
            SELECT id, name, location, city, cover_image, slug, excerpt, priority
            FROM drink_coffee
            WHERE published = 1
            AND LOWER(city) = LOWER(:slug)
            ORDER BY
                CASE WHEN priority IS NULL THEN 1 ELSE 0 END,
                priority DESC,
                RAND()
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(":slug", $slug, PDO::PARAM_STR);
        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coffees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return fourZeroFour();
    }

    $cityName = htmlspecialchars(ucwords($slug));
    $coffeeHtml = renderList(coffees: $coffees, offset: $offset);

    // PAGINATION — now slug-aware
    $pagination = "<nav class='max-w-6xl mx-auto px-6 md:px-12 py-12 flex items-center gap-6 font-mono text-sm'>";
    if ($page > 1) {
        $prevPage = $page - 1;
        $pagination .= "<a href='/city/{$slug}/{$prevPage}' class='uppercase tracking-widest text-[#2B1B12] hover:text-[#B23A2E] transition-colors'>&larr; Prev</a>";
    }
    $pagination .= "<div class='flex gap-3 mx-auto'>";
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $page) {
            $pagination .= "<span class='font-bold text-[#B23A2E] underline underline-offset-4'>{$i}</span>";
        } else {
            $pagination .= "<a href='/city/{$slug}/{$i}' class='text-[#8C7B6B] hover:text-[#2B1B12] transition-colors'>{$i}</a>";
        }
    }
    $pagination .= "</div>";
    if ($page < $totalPages) {
        $nextPage = $page + 1;
        $pagination .= "<a href='/city/{$slug}/{$nextPage}' class='uppercase tracking-widest text-[#2B1B12] hover:text-[#B23A2E] transition-colors'>Next &rarr;</a>";
    }
    $pagination .= "</nav>";

    $head = "
        <title>Hidden Beans in {$cityName}</title>
    ";

    $body = "
        <section class='border-b border-[#2B1B12]/15 px-6 md:px-12 py-16 md:py-20 bg-[#EFEAE2]'>
            <div class='max-w-6xl mx-auto'>
                <p class='uppercase tracking-[0.25em] text-xs font-bold text-[#B23A2E] mb-4'>
                    <a href='/' class='hover:underline'>Hidden Beans</a> &mdash; City Index
                </p>
                <h1 class='text-5xl md:text-7xl font-black tracking-tight text-[#2B1B12] leading-[0.9]'>{$cityName}</h1>
                <p class='mt-4 font-mono text-xs text-[#2B1B12] tracking-widest'>N&deg; {$totalRows} LISTED</p>
            </div>
        </section>

        <section class='px-6 md:px-12 pt-10'>
            <div class='max-w-6xl mx-auto'>
                <div class='flex items-baseline justify-between border-b border-[#2B1B12] pb-3 mb-2 font-mono text-xs uppercase tracking-widest text-[#8C7B6B]'>
                    <span>Listing</span>
                    <span>Page {$page} / {$totalPages}</span>
                </div>
                {$coffeeHtml}
            </div>
        </section>

        {$pagination}
    ";

    return [$head, $body];
}

function slugBased($slug)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare(
            "SELECT name, location, city, cover_image, description, conclusion, instagram, website, meta_title, meta_description, meta_keywords, updated_at
             FROM drink_coffee WHERE slug = :slug"
        );
        $stmt->execute([":slug" => $slug]);
        $coffee_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return fiveHundred();
    }

    if (!$coffee_data) {
        return fourZeroFour();
    }

    $name        = htmlspecialchars($coffee_data["name"]);
    $location    = htmlspecialchars($coffee_data["location"]);
    $city        = htmlspecialchars($coffee_data["city"]);
    $cover_image = htmlspecialchars($coffee_data["cover_image"]);
    $instagram   = htmlspecialchars($coffee_data["instagram"] ?? '');
    $website     = htmlspecialchars($coffee_data["website"] ?? '');
    $updatedTime = htmlspecialchars(date("F j, Y", strtotime($coffee_data["updated_at"])));

    // trusted rich-text HTML from TinyMCE admin — not escaped
    $description = $coffee_data["description"];
    $conclusion  = $coffee_data["conclusion"];

    $metaTitle       = htmlspecialchars($coffee_data["meta_title"] ?: "{$coffee_data['name']} — Hidden Beans");
    $metaDescription = htmlspecialchars($coffee_data["meta_description"] ?: strip_tags($description));
    $metaKeywords    = htmlspecialchars($coffee_data["meta_keywords"] ?: '');

    $head = <<<HTML
    <title>{$metaTitle}</title>
    <meta name="description" content="{$metaDescription}">
    <meta name="keywords" content="{$metaKeywords}">
    <meta property="og:title" content="{$metaTitle}">
    <meta property="og:description" content="{$metaDescription}">
    <meta property="og:image" content="{$cover_image}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="627">
    <meta property="og:type" content="article">
    <meta name="twitter:card" content="summary_large_image">
    <style>
    /* Swiss / International Typographic Style base */
    .hb-page {
      color: #2B1B12;
      background: #EFEAE2;
    }
    .hb-rule { background-color: #2B1B12; }
    .hb-mono {
      font-family: ui-monospace, "SF Mono", "Roboto Mono", Menlo, Consolas, monospace;
      letter-spacing: 0.14em;
    }

    /* Prose block for TinyMCE description/conclusion content */
    .hb-prose {
      font-size: 1.05rem;
      line-height: 1.75;
      color: #2B1B12;
    }
    .hb-prose p { margin-bottom: 1.25em; }
    .hb-prose h2 {
      font-weight: 800;
      font-size: 1.4rem;
      letter-spacing: -0.01em;
      text-transform: uppercase;
      margin: 2em 0 0.75em;
      padding-bottom: 0.4em;
      border-bottom: 2px solid #2B1B12;
      color: #2B1B12;
    }
    .hb-prose h3, .hb-prose h4, .hb-prose h5, .hb-prose h6 {
      font-weight: 700;
      margin: 1.5em 0 0.5em;
      color: #2B1B12;
    }
    .hb-prose ul, .hb-prose ol {
      margin: 0 0 1.25em 1.25em;
    }
    .hb-prose li { margin-bottom: 0.4em; }
    .hb-prose a {
      color: #B23A2E;
      text-decoration: underline;
      text-underline-offset: 2px;
    }
    .hb-prose img {
      width: 100%;
      height: auto;
      margin: 1.5em 0;
      filter: grayscale(1);
    }
    </style>
    HTML;

    $body = <<<HTML
        <div class="hb-page">

            <!-- Masthead: flat, ruled, no gradients — Swiss grid discipline -->
            <header class="border-b-2 border-[#2B1B12] px-6 md:px-12 py-4 flex items-center justify-between">
                <a href="/" class="hb-mono text-xs font-bold uppercase text-[#2B1B12]">Hidden Beans</a>
                <p class="hb-mono text-xs font-bold uppercase text-[#B23A2E]">{$city}</p>
            </header>

            <!-- Hero: image as object, title set below on grid baseline, flat (no overlay gradient) -->
            <section class="border-b-2 border-[#2B1B12]">
                <div class="h-[38vh] md:h-[48vh] w-full overflow-hidden">
                    <img src="{$cover_image}" alt="{$name}" width="1200" height="627" class="w-full h-full object-cover grayscale">
                </div>
                <div class="px-6 md:px-12 py-8 md:py-10">
                    <div class="max-w-6xl mx-auto">
                        <p class="hb-mono text-xs font-bold text-[#B23A2E] mb-3">Coffee Shop — {$city}</p>
                        <h1 class="text-5xl md:text-8xl font-black tracking-tight leading-[0.88] text-[#2B1B12]">{$name}</h1>
                    </div>
                </div>
            </section>

            <!-- Meta grid: strict columns, ruled dividers, numeric/mono labels -->
            <section class="px-6 md:px-12 border-b-2 border-[#2B1B12]">
                <div class="max-w-6xl mx-auto grid grid-cols-2 md:grid-cols-4">
                    <div class="py-6 md:pr-8 border-b md:border-b-0 md:border-r border-[#2B1B12]/20">
                        <p class="hb-mono text-[10px] font-bold text-[#8C7B6B] mb-2">01 — Location</p>
                        <p class="text-[#2B1B12] font-sans text-sm md:text-base">{$location}</p>
                    </div>
                    <div class="py-6 md:px-8 border-b md:border-b-0 md:border-r border-[#2B1B12]/20">
                        <p class="hb-mono text-[10px] font-bold text-[#8C7B6B] mb-2">02 — City</p>
                        <p class="text-[#2B1B12] font-sans text-sm md:text-base">{$city}</p>
                    </div>
                    <div class="py-6 md:px-8 border-b md:border-b-0 md:border-r border-[#2B1B12]/20">
                        <p class="hb-mono text-[10px] font-bold text-[#8C7B6B] mb-2">03 — Updated</p>
                        <p class="text-[#2B1B12] font-sans text-sm md:text-base">{$updatedTime}</p>
                    </div>
                    <div class="py-6 md:pl-8">
                        <p class="hb-mono text-[10px] font-bold text-[#8C7B6B] mb-2">04 — Links</p>
                        <div class="flex flex-col gap-1">
        HTML;

    if ($website) {
        $body .= "<a href='{$website}' target='_blank' rel='noopener' class='text-[#B23A2E] hover:underline font-sans text-sm md:text-base w-fit'>Website</a>";
    }
    if ($instagram) {
        $body .= "<a href='{$instagram}' target='_blank' rel='noopener' class='text-[#B23A2E] hover:underline font-sans text-sm md:text-base w-fit'>Instagram</a>";
    }
    if (!$website && !$instagram) {
        $body .= "<p class='text-[#8C7B6B] font-sans text-sm md:text-base'>—</p>";
    }

    $body .= <<<HTML
                        </div>
                    </div>
                </div>
            </section>

            <!-- Editorial body: single strict column, generous margins, ruled break -->
            <section class="px-6 md:px-12 py-14 md:py-20">
                <div class="max-w-3xl mx-auto">
                    <div class="hb-prose">
                        {$description}
                    </div>
                    <div class="hb-prose mt-10 pt-10 border-t-2 border-[#2B1B12]">
                        {$conclusion}
                    </div>
                </div>
            </section>

            <!-- Footer nav: flat rule, mono label -->
            <section class="px-6 md:px-12 pb-16">
                <div class="max-w-6xl mx-auto pt-8 border-t-2 border-[#2B1B12] flex items-center justify-between">
                    <a href="/" class="hb-mono text-xs font-bold text-[#2B1B12] hover:text-[#B23A2E] transition-colors">&larr; Back to Directory</a>
                    <p class="hb-mono text-xs font-bold text-[#8C7B6B]">Hidden Beans</p>
                </div>
            </section>

        </div>
    HTML;

    return [$head, $body];
}

//404
function fourZeroFour()
{
    $head = "";
    $body = <<<HTML
        <h1>404 not found <h1>
    HTML;
    return [$head, $body];
}

//500
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

function admin()
{
    authGuard();

    global $pdo;
    global $admin;
    global $create_admin_route;
    global $edit_admin_route;

    // Edit button clicked
    if (isset($_POST["editId"])) {
        $_SESSION["editId"] = $_POST["editId"];

        header("Location: $edit_admin_route");
        exit();
    }

    // Delete button clicked
    if (isset($_POST["deleteId"])) {
        $id = $_POST["deleteId"];

        try {
            // Get slug first
            $stmt = $pdo->prepare("
                SELECT slug
                FROM drink_coffee
                WHERE id = :id
            ");

            $stmt->execute([
                ":id" => $id,
            ]);

            $coffee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coffee) {
                throw new Exception("Coffee not found");
            }

            $slug = $coffee["slug"];

            // Delete DB row
            $stmt = $pdo->prepare("
                DELETE FROM drink_coffee
                WHERE id = :id
            ");

            $stmt->execute([
                ":id" => $id,
            ]);

            // Remove JSON
            deleteCoffee($slug);

            header("Location: $admin");
            exit();
        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // Fetch coffees
    $stmt = $pdo->query("
        SELECT id, name, city, slug, published
        FROM drink_coffee
        ORDER BY id DESC
    ");

    $coffees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rows = "";

    foreach ($coffees as $coffee) {
        $id = $coffee["id"];

        $name = htmlspecialchars($coffee["name"]);
        $city = htmlspecialchars($coffee["city"]);
        $slug = htmlspecialchars($coffee["slug"]);

        $published = $coffee["published"]
            ? "<span class='inline-flex items-center rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400'>Published</span>"
            : "<span class='inline-flex items-center rounded-full border border-yellow-500/30 bg-yellow-500/10 px-3 py-1 text-xs font-semibold text-yellow-400'>Draft</span>";

        $rows .= "
                <tr class='transition hover:bg-zinc-800/60'>

                    <td class='px-6 py-4 text-sm text-zinc-400'>$id</td>

                    <td class='px-6 py-4 font-medium text-white'>
                        $name
                    </td>

                    <td class='px-6 py-4 text-zinc-300'>
                        $city
                    </td>

                    <td class='px-6 py-4 text-zinc-500'>
                        $slug
                    </td>

                    <td class='px-6 py-4'>
                        $published
                    </td>

                    <td class='px-6 py-4'>

                        <form method='POST'>

                            <button
                                type='submit'
                                name='editId'
                                value='$id'
                                class='rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-500'
                            >
                                Edit
                            </button>

                        </form>

                    </td>

                    <td class='px-6 py-4'>

                        <form
                            method='POST'
                            onsubmit='return confirm(\"Delete coffee?\")'
                        >

                            <button
                                type='submit'
                                name='deleteId'
                                value='$id'
                                class='rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-500'
                            >
                                Delete
                            </button>

                        </form>

                    </td>

                </tr>
            ";
    }

    $head = <<<HTML
    <title>Admin Page</title>
    HTML;

    $body = <<<HTML

    <div class="min-h-screen bg-zinc-950 p-8 text-white">

        <div class="mx-auto max-w-7xl">

            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">

                <div>
                    <h1 class="text-4xl font-bold tracking-tight text-white">
                        Admin Dashboard
                    </h1>

                    <p class="mt-2 text-zinc-400">
                        Manage your coffee entries
                    </p>
                </div>

                <a
                    href="$create_admin_route"
                    class="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-black shadow-lg transition hover:bg-emerald-400"
                >
                    + Create Coffee
                </a>

            </div>

            <!-- Table Container -->
            <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900 shadow-2xl">

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <!-- Table Head -->
                        <thead class="border-b border-zinc-800 bg-zinc-950">

                            <tr>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    ID
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    Name
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    City
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    Slug
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    Status
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    Edit
                                </th>

                                <th class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wider text-zinc-400">
                                    Delete
                                </th>

                            </tr>

                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-zinc-800">

                            $rows

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- Logout -->
            <div class="mt-8">

                <form action="/logout" method="POST">

                    <button
                        class="rounded-xl border border-zinc-700 bg-zinc-900 px-5 py-3 text-sm font-semibold text-zinc-300 transition hover:border-red-500 hover:bg-red-500 hover:text-white"
                    >
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </div>

    HTML;

    return [$head, $body];
}

function adminLogin()
{
    global $admin_user;
    global $admin_pass;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user = $_POST["username"];
        $pass = $_POST["password"];

        if ($user == $admin_user && $pass == $admin_pass) {
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

    <div class="flex min-h-screen items-center justify-center bg-zinc-950 px-6">

        <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-2xl">

            <!-- Header -->
            <div class="mb-8 text-center">

                <h1 class="text-4xl font-bold tracking-tight text-white">
                    Admin Login
                </h1>

                <p class="mt-2 text-zinc-400">
                    Sign in to access the dashboard
                </p>

            </div>

            <!-- Form -->
            <form action="/adminlogin" method="POST" class="space-y-5">

                <!-- Username -->
                <div>

                    <label class="mb-2 block text-sm font-medium text-zinc-300">
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Enter username"
                        required
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                    >

                </div>

                <!-- Password -->
                <div>

                    <label class="mb-2 block text-sm font-medium text-zinc-300">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter password"
                        required
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                    >

                </div>

                <!-- Submit -->
                <div class="pt-2">

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-emerald-500 px-6 py-4 text-sm font-semibold text-black transition hover:bg-emerald-400"
                    >
                        Login
                    </button>

                </div>

            </form>

        </div>

    </div>

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

//create route for admin
function create()
{
    authGuard();
    global $create_admin_route;
    global $pdo;
    global $admin;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $location = $_POST["location"];
        $city = $_POST["city"];
        $cover_image = $_POST["cover_image"];
        $priority = is_numeric($_POST['priority'] ?? null)
            ? (int)$_POST['priority']
            : null;
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
                       priority
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
                       :priority
                   )
               ";
        try {
            $pdo->beginTransaction();

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
                ":priority" => $priority
            ]);

            $newId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "SELECT created_at, updated_at FROM drink_coffee WHERE id = :id",
            );
            $stmt->execute([":id" => $newId]);

            addCoffee($newId, $slug, $published);

            $pdo->commit();

            header("Location: $admin");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "Error: " . $e->getMessage();
        }
    }

    $head = <<<HTML
        <title>create </title>
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>
    HTML;

    $body = <<<HTML

    <div class="min-h-screen bg-zinc-950 px-6 py-10 text-white">

        <div class="mx-auto max-w-4xl">

            <!-- Header -->
            <div class="mb-8">

                <h1 class="text-4xl font-bold tracking-tight text-white">
                    Create Coffee
                </h1>

                <p class="mt-2 text-zinc-400">
                    Add a new coffee shop entry
                </p>

            </div>

            <!-- Form Card -->
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-2xl">

                <form action="$create_admin_route" method="POST" class="space-y-6">

                    <!-- Publish -->
                    <div>

                        <label
                            for="publish"
                            class="mb-2 block text-sm font-medium text-zinc-300"
                        >
                            Publish Status
                        </label>

                        <select
                            name="published"
                            id="publish"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >
                            <option value="true">
                                Publish
                            </option>

                            <option value="false" selected>
                                Draft
                            </option>

                        </select>

                    </div>

                    <!-- Name -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Coffee Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            placeholder="Coffee name"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Location -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            placeholder="Location"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- City -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            placeholder="City"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Cover Image -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Cover Image URL
                        </label>

                        <input
                            type="text"
                            name="cover_image"
                            placeholder="https://..."
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Priority
                        </label>

                        <select
                            name="priority"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >
                            <option value="" selected>None</option>
                            <option value="7">7</option>
                            <option value="6">6</option>
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                    </div>



                    <!-- Excerpt -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Excerpt
                        </label>

                        <textarea
                            name="excerpt"
                            rows="4"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                            placeholder="Short excerpt..."
                        ></textarea>

                    </div>

                    <!-- Description -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                            placeholder="Detailed description..."
                        ></textarea>
                        <script>
                        tinymce.init({
                          selector: '#description',
                          plugins: 'lists link image',
                          toolbar: 'formatselect | bold italic | bullist numlist | link image',
                          block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
                          forced_root_block: 'p',
                          image_uploadtab: false,
                          automatic_uploads: false,
                          valid_styles: {},
                        });

                        document.querySelector("form").addEventListener("submit", function () {
                          tinymce.triggerSave();
                        });
                        </script>

                    </div>

                    <!-- Conclusion -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Conclusion
                        </label>

                        <textarea
                            name="conclusion"
                            rows="4"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                            placeholder="Final thoughts..."
                        ></textarea>

                    </div>

                    <!-- Instagram -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Instagram
                        </label>

                        <input
                            type="text"
                            name="instagram"
                            placeholder="@instagram"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Website -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Website
                        </label>

                        <input
                            type="text"
                            name="website"
                            placeholder="https://website.com"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Title -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Title
                        </label>

                        <input
                            type="text"
                            name="meta_title"
                            placeholder="Meta title"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Description -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Description
                        </label>

                        <input
                            type="text"
                            name="meta_desc"
                            placeholder="Meta description"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Keywords -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Keywords
                        </label>

                        <input
                            type="text"
                            name="meta_keywords"
                            placeholder="coffee, cafe, espresso"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Submit -->
                    <div class="pt-4">

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-emerald-500 px-6 py-4 text-sm font-semibold text-black transition hover:bg-emerald-400"
                        >
                            Create Coffee
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

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

    $originalSlug = $slug;

    // keep generating until unique
    while (slugExists($slug)) {
        $slug = $originalSlug . "-" . rand(100, 999);
    }

    return $slug;
}

function slugExists($slug)
{
    $coffee = readCoffee();

    return isset($coffee[$slug]);
}

//EDIT route for admin
function edit()
{
    authGuard();

    global $pdo;
    global $admin;

    $id = $_SESSION["editId"];

    // Fetch existing coffee
    $stmt = $pdo->prepare("SELECT * FROM drink_coffee WHERE id = :id");
    $stmt->execute([":id" => $id]);

    $coffee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coffee) {
        die("Coffee not found");
    }

    // Handle update submit
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            $pdo->beginTransaction();

            $name = trim($_POST["name"]);
            $location = trim($_POST["location"]);
            $city = trim($_POST["city"]);
            $cover_image = trim($_POST["cover_image"]);
            $excerpt = $_POST["excerpt"];
            $description = $_POST["description"];
            $conclusion = $_POST["conclusion"];
            $instagram = $_POST["instagram"];
            $website = $_POST["website"];
            $meta_title = $_POST["meta_title"];
            $meta_description = $_POST["meta_desc"];
            $meta_keywords = $_POST["meta_keywords"];
            $published = (int) $_POST["published"];
            $priority = is_numeric($_POST['priority'] ?? null)
                ? (int)$_POST['priority']
                : null;

            $old_slug = $coffee["slug"];
            $old_published_state = (int) $coffee["published"];

            $new_slug = $old_slug;

            if ($coffee["name"] !== $name) {
                $new_slug = createSlug($name);
            }

            $sql = "
                    UPDATE drink_coffee
                    SET
                        name = :name,
                        slug = :slug,
                        location = :location,
                        city = :city,
                        cover_image = :cover_image,
                        excerpt = :excerpt,
                        description = :description,
                        conclusion = :conclusion,
                        instagram = :instagram,
                        website = :website,
                        meta_title = :meta_title,
                        meta_description = :meta_description,
                        meta_keywords = :meta_keywords,
                        published = :published,
                        priority = :priority
                    WHERE id = :id
                ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":name" => $name,
                ":slug" => $new_slug,
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
                ":priority" => $priority,
                ":id" => $id,
            ]);

            if (
                $coffee["name"] !== $name ||
                $old_published_state !== $published
            ) {
                updateCoffee($old_slug, $new_slug, $id, $published);
            }

            $pdo->commit();

            header("Location: $admin");
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo "Update failed: " . $e->getMessage();
        }
    }

    // Selected option
    $publishSelected = $coffee["published"] ? "selected" : "";
    $draftSelected = !$coffee["published"] ? "selected" : "";


    $prioritySelect = $coffee['priority'];

    $none = ($prioritySelect === null || $prioritySelect === '') ? 'selected' : '';
    $p7 = $prioritySelect == 7 ? 'selected' : '';
    $p6 = $prioritySelect == 6 ? 'selected' : '';
    $p5 = $prioritySelect == 5 ? 'selected' : '';
    $p4 = $prioritySelect == 4 ? 'selected' : '';
    $p3 = $prioritySelect == 3 ? 'selected' : '';
    $p2 = $prioritySelect == 2 ? 'selected' : '';
    $p1 = $prioritySelect == 1 ? 'selected' : '';

    $head = <<<HTML
        <title>Edit {$coffee["name"]}</title>
     <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js"></script>

    HTML;

    $body = <<<HTML

    <div class="min-h-screen bg-zinc-950 px-6 py-10 text-white">

        <div class="mx-auto max-w-4xl">

            <!-- Header -->
            <div class="mb-8">

                <h1 class="text-4xl font-bold tracking-tight text-white">
                    Edit Coffee
                </h1>

                <p class="mt-2 text-zinc-400">
                    Update your coffee shop information
                </p>

            </div>

            <!-- Form Card -->
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-8 shadow-2xl">

                <form method="POST" class="space-y-6">

                    <!-- Publish -->
                    <div>

                        <label
                            for="publish"
                            class="mb-2 block text-sm font-medium text-zinc-300"
                        >
                            Publish Status
                        </label>

                        <select
                            name="published"
                            id="publish"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >
                            <option value="1" $publishSelected>
                                Publish
                            </option>

                            <option value="0" $draftSelected>
                                Draft
                            </option>

                        </select>

                    </div>

                    <!-- Name -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Coffee Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{$coffee["name"]}"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white placeholder:text-zinc-500 outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Location -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            value="{$coffee["location"]}"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- City -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            value="{$coffee["city"]}"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Cover Image -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Cover Image URL
                        </label>

                        <input
                            type="text"
                            name="cover_image"
                            value="{$coffee["cover_image"]}"
                            required
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Priority
                        </label>

                        <select
                            name="priority"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >
                            <option value="" $none>None</option>
                                <option value="7" $p7>7</option>
                                <option value="6" $p6>6</option>
                                <option value="5" $p5>5</option>
                                <option value="4" $p4>4</option>
                                <option value="3" $p3>3</option>
                                <option value="2" $p2>2</option>
                                <option value="1" $p1>1</option>
                        </select>
                    </div>

                    <!-- Excerpt -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Excerpt
                        </label>

                        <textarea
                            name="excerpt"
                            rows="4"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >{$coffee["excerpt"]}</textarea>

                    </div>

                    <!-- Description -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >{$coffee["description"]}</textarea>


                        <script>
                        tinymce.init({
                          selector: '#description',
                          license_key: 'gpl',
                          plugins: 'lists link image',
                          toolbar: 'formatselect | bold italic | bullist numlist | link image',
                          block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
                          forced_root_block: 'p',
                          image_uploadtab: false,
                          automatic_uploads: false,
                          valid_styles: {},
                        });

                        document.querySelector("form").addEventListener("submit", function () {
                          tinymce.triggerSave();
                        });
                        </script>

                    </div>

                    <!-- Conclusion -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Conclusion
                        </label>

                        <textarea
                            name="conclusion"
                            rows="4"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >{$coffee["conclusion"]}</textarea>

                    </div>

                    <!-- Instagram -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Instagram
                        </label>

                        <input
                            type="text"
                            name="instagram"
                            value="{$coffee["instagram"]}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Website -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Website
                        </label>

                        <input
                            type="text"
                            name="website"
                            value="{$coffee["website"]}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Title -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Title
                        </label>

                        <input
                            type="text"
                            name="meta_title"
                            value="{$coffee["meta_title"]}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Description -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Description
                        </label>

                        <input
                            type="text"
                            name="meta_desc"
                            value="{$coffee["meta_description"]}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Meta Keywords -->
                    <div>

                        <label class="mb-2 block text-sm font-medium text-zinc-300">
                            Meta Keywords
                        </label>

                        <input
                            type="text"
                            name="meta_keywords"
                            value="{$coffee["meta_keywords"]}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-3 text-white outline-none transition focus:border-emerald-500"
                        >

                    </div>

                    <!-- Submit -->
                    <div class="pt-4">

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Update Coffee
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    HTML;

    return [$head, $body];
}

//JSON slugs
function readCoffee()
{
    if (!file_exists("coffee.json")) {
        file_put_contents("coffee.json", json_encode([]));
    }
    $data = file_get_contents("coffee.json");
    return json_decode($data, true) ?? [];
}

function addCoffee($id, $coffee_slug, $published)
{
    $coffee = readCoffee();
    $coffee[$coffee_slug] = [$id, $published]; // slug as key, id and published state as value
    $data = json_encode($coffee, JSON_THROW_ON_ERROR);
    if (file_put_contents("coffee.json", $data) === false) {
        throw new RuntimeException("Failed to update coffee.json");
    }
}

function updateCoffee($old_slug, $new_slug, $id, $published)
{
    $coffee = readCoffee();
    if (!isset($coffee[$old_slug])) {
        return false;
    }
    unset($coffee[$old_slug]);
    $coffee[$new_slug] = [$id, $published]; // add new slug
    file_put_contents("coffee.json", json_encode($coffee, JSON_THROW_ON_ERROR));
    return true;
}

function deleteCoffee($slug)
{
    $coffee = readCoffee();
    if (!isset($coffee[$slug])) {
        return false;
    }
    unset($coffee[$slug]);
    file_put_contents("coffee.json", json_encode($coffee, JSON_THROW_ON_ERROR));
    return true;
}
?>




<!-- RENDER LAYOUT -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php echo $body_on_route; ?>
    </body>
</html>
