<?php

require_once __DIR__ . '/../core/Database.php';

echo "=====================================\n";
echo "PROJECT AUDIT\n";
echo "ROOT: " . realpath(__DIR__ . '/..') . "\n";
echo "=====================================\n\n";

$root = realpath(__DIR__ . '/..');

$phpFiles = [];
$allFiles = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root)
);

foreach ($rii as $file) {

    if ($file->isDir()) continue;

    $path = $file->getPathname();

    $allFiles[] = $path;

    if (substr($path, -4) === ".php") {
        $phpFiles[] = $path;
    }

}

echo "PHP files scanned: " . count($phpFiles) . "\n";
echo "All files scanned: " . count($allFiles) . "\n\n";

$allCode = "";

foreach ($phpFiles as $f) {
    $allCode .= file_get_contents($f) . "\n";
}

################################################
# ROUTES
################################################

echo "-------------------------------------\n";
echo "ROUTES\n";
echo "-------------------------------------\n";

$routes = [];

foreach ($phpFiles as $file) {

    $content = file_get_contents($file);

    preg_match_all('/\$router->(get|post)\([\'"](.+?)[\'"],\s*[\'"](.+?)[\'"]\)/', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {

        $method = strtoupper($m[1]);
        $path = $m[2];
        $handler = $m[3];

        $routes[] = [$method, $path, $handler];

    }

}

echo "Routes found: " . count($routes) . "\n";

foreach ($routes as $r) {
    echo $r[0] . " " . $r[1] . " -> " . $r[2] . "\n";
}

echo "\n";

################################################
# CONTROLLERS
################################################

echo "-------------------------------------\n";
echo "CONTROLLERS\n";
echo "-------------------------------------\n";

$controllerFiles = glob($root . "/app/Controllers/*.php");

$controllers = [];

foreach ($controllerFiles as $c) {

    $name = basename($c, ".php");

    $controllers[$name] = $c;

}

echo "Controllers found: " . count($controllers) . "\n\n";

$controllersWithRoute = [];

foreach ($routes as $r) {

    list($controller,) = explode("@", $r[2]);

    $controllersWithRoute[$controller] = true;

}

echo "Controllers without route:\n";

foreach ($controllers as $c => $path) {

    if (!isset($controllersWithRoute[$c])) {
        echo "  - $c\n";
    }

}

echo "\n";

################################################
# VIEWS
################################################

echo "-------------------------------------\n";
echo "VIEWS\n";
echo "-------------------------------------\n";

$views = [];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . "/resources/views")
);

foreach ($rii as $file) {

    if ($file->isDir()) continue;

    if (substr($file->getFilename(), -4) === ".php") {

        $views[] = $file->getPathname();

    }

}

echo "Views found: " . count($views) . "\n\n";

echo "Unused views:\n";

foreach ($views as $v) {

    $name = basename($v);

    if (strpos($allCode, $name) === false) {

        echo "  - " . str_replace($root . "/", "", $v) . "\n";

    }

}

echo "\n";

################################################
# VIEW STRUCTURE CHECK
################################################

echo "-------------------------------------\n";
echo "VIEW STRUCTURE CHECK\n";
echo "-------------------------------------\n";

foreach ($views as $v) {

    $content = file_get_contents($v);

    $relative = str_replace($root . "/", "", $v);

    if (strpos($content, "<html") !== false) {

        echo "HTML inside view (should be layout): $relative\n";

    }

    if (strpos($content, "/duelo/v2/public") !== false) {

        echo "Hardcoded base_url: $relative\n";

    }

    if (strpos($content, "layout/header.php") !== false) {

        echo "Old header include: $relative\n";

    }

    if (strpos($content, "layout/footer.php") !== false) {

        echo "Old footer include: $relative\n";

    }

}

echo "\n";

################################################
# TABLES _v2
################################################

echo "-------------------------------------\n";
echo "DATABASE TABLES (_v2)\n";
echo "-------------------------------------\n";

$db = Database::getConnection();

$res = $db->query("SHOW TABLES LIKE '%_v2'");

$tables = [];

while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

echo "Tables found: " . count($tables) . "\n\n";

$unusedTables = [];

foreach ($tables as $t) {

    if (strpos($allCode, $t) === false) {

        $unusedTables[] = $t;

    }

}

if ($unusedTables) {

    echo "Tables not used in code:\n";

    foreach ($unusedTables as $t) {
        echo "  - $t\n";
    }

} else {

    echo "All tables used.\n";

}

echo "\n";

################################################
# SCRIPTS
################################################

echo "-------------------------------------\n";
echo "SCRIPTS\n";
echo "-------------------------------------\n";

$scripts = glob($root . "/scripts/*.php");

echo "Scripts found: " . count($scripts) . "\n\n";

echo "Scripts that look standalone:\n";

foreach ($scripts as $s) {

    if (strpos($allCode, basename($s)) === false) {

        echo "  - scripts/" . basename($s) . "\n";

    }

}

echo "\n";

################################################
# CRON SUGGESTION
################################################

echo "-------------------------------------\n";
echo "CRON SUGGESTIONS\n";
echo "-------------------------------------\n";

foreach ($scripts as $s) {

    $name = basename($s);

    if (
        strpos($name, "sync") !== false ||
        strpos($name, "process") !== false ||
        strpos($name, "settle") !== false ||
        strpos($name, "expire") !== false
    ) {

        echo "Possible cron: $name\n";

    }

}

echo "\n";

echo "=====================================\n";
echo "AUDIT COMPLETE\n";
echo "=====================================\n";