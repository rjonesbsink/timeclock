<?php

/*
 * First-run setup wizard. Refuses to do anything once config.inc.php
 * exists (below), but until an admin runs it, this is intentionally an
 * unauthenticated page that accepts arbitrary DB host/credentials and
 * reports back whether the connection succeeded -- the same shape every
 * install wizard has (WordPress, etc.). Operators should restrict access
 * to this file (IP allowlist, HTTP auth, or just deleting it) between
 * deploying the code and actually running setup.
 */

session_start();

require_once __DIR__ . '/lib/csrf.php';

const CONFIG_FILE = __DIR__ . '/config.inc.php';
const CONFIG_TEMPLATE = __DIR__ . '/config.inc.php.dist';
const SCHEMA_FILE = __DIR__ . '/sql/create_tables.sql';

if (file_exists(CONFIG_FILE)) {
    render_page("<p>PHP Timeclock is already configured. Delete <code>config.inc.php</code> on the server if you need to run setup again.</p>");
    exit;
}

function render_page($contentHtml)
{
    echo "<!doctype html>\n<html>\n<head>\n";
    echo "<title>PHP Timeclock Setup</title>\n";
    echo "<style>\n";
    echo "body { font-family: sans-serif; max-width: 640px; margin: 40px auto; color: #333; }\n";
    echo "label { display: block; margin-top: 12px; font-weight: bold; }\n";
    echo "input[type=text], input[type=password] { width: 100%; padding: 6px; box-sizing: border-box; }\n";
    echo ".error { background: #fdecea; border: 1px solid #f5c2c0; color: #611a15; padding: 10px; margin-bottom: 16px; }\n";
    echo ".success { background: #eafaf1; border: 1px solid #a3e4b8; color: #1e5631; padding: 10px; margin-bottom: 16px; }\n";
    echo ".hint { color: #666; font-size: 0.85em; margin: 2px 0 0; }\n";
    echo "button { margin-top: 20px; padding: 8px 20px; }\n";
    echo "</style>\n</head>\n<body>\n";
    echo "<h1>PHP Timeclock Setup</h1>\n";
    echo $contentHtml;
    echo "</body>\n</html>\n";
}

function render_form($values, $errorHtml = '')
{
    $host = htmlspecialchars($values['db_hostname']);
    $name = htmlspecialchars($values['db_name']);
    $user = htmlspecialchars($values['db_username']);
    $createTablesChecked = $values['create_tables'] ? 'checked' : '';

    $html = $errorHtml;
    $html .= "<p>Enter the connection details for the MySQL/MariaDB database this installation should use.</p>\n";
    $html .= "<form method='post'>\n";
    $html .= csrf_field() . "\n";
    $html .= "<label for='db_hostname'>Database Host</label>\n";
    $html .= "<input type='text' id='db_hostname' name='db_hostname' value=\"$host\" required>\n";
    $html .= "<p class='hint'>Usually \"localhost\".</p>\n";
    $html .= "<label for='db_name'>Database Name</label>\n";
    $html .= "<input type='text' id='db_name' name='db_name' value=\"$name\" required>\n";
    $html .= "<label for='db_username'>Database Username</label>\n";
    $html .= "<input type='text' id='db_username' name='db_username' value=\"$user\" required>\n";
    $html .= "<label for='db_password'>Database Password</label>\n";
    $html .= "<input type='password' id='db_password' name='db_password'>\n";
    $html .= "<label><input type='checkbox' name='create_tables' value='1' $createTablesChecked>\n";
    $html .= "Create the database tables now (uncheck this if the database already has PHP Timeclock's tables)</label>\n";
    $html .= "<button type='submit'>Save and Continue</button>\n";
    $html .= "</form>\n";

    return $html;
}

// Fills in config.inc.php.dist with the submitted DB values and returns the
// resulting file content, without touching the database or filesystem.
// Called before connecting so a template that doesn't match the expected
// shape is caught before any tables get created -- otherwise a failure
// here would leave the install half-finished (tables created, but no
// config.inc.php, and no way to retry without unchecking "create tables").
// Returns ['ok' => true, 'content' => string] or ['ok' => false, 'error' => string].
function build_config($dbHostname, $dbName, $dbUsername, $dbPassword)
{
    $template = file_get_contents(CONFIG_TEMPLATE);

    $substitutions = [
        'db_hostname' => $dbHostname,
        'db_username' => $dbUsername,
        'db_password' => $dbPassword,
        'db_name' => $dbName,
    ];

    foreach ($substitutions as $variable => $value) {
        // preg_replace_callback, not preg_replace: the replacement here
        // embeds a submitted value (most importantly the DB password),
        // and preg_replace's replacement string treats $1/\1-style
        // sequences as backreferences -- a password containing a literal
        // "$1" would otherwise be silently mangled in the written file.
        $template = preg_replace_callback(
            '/^\$' . $variable . '\s*=\s*".*";$/m',
            fn () => '$' . $variable . ' = ' . var_export($value, true) . ';',
            $template,
            1,
            $replacementCount
        );
        if ($replacementCount !== 1) {
            return ['ok' => false, 'error' => "Could not find \$$variable in config.inc.php.dist -- the template may have changed."];
        }
    }

    return ['ok' => true, 'content' => $template];
}

// Returns ['ok' => true, 'warning' => ?string] on success ('warning' is set
// if a non-fatal problem -- currently just a failed chmod -- means it's
// worth telling the admin about), or ['ok' => false, 'error' => string] if
// config.inc.php could not be written at all.
function save_config($content)
{
    if (file_put_contents(CONFIG_FILE, $content) === false) {
        return ['ok' => false, 'error' => 'Could not write config.inc.php. Check the web server has permission to create files here.'];
    }

    if (!chmod(CONFIG_FILE, 0640)) {
        return [
            'ok' => true,
            'warning' => 'config.inc.php was created, but its file permissions could not be restricted to 0640'
                . ' (it contains your database password). Please set this manually on the server.',
        ];
    }

    return ['ok' => true, 'warning' => null];
}

function run_schema($connection)
{
    $sql = file_get_contents(SCHEMA_FILE);

    try {
        mysqli_multi_query($connection, $sql);

        // Drain all result sets from the multi-query so the connection is
        // left in a clean state.
        do {
            if (mysqli_more_results($connection)) {
                mysqli_next_result($connection);
            } else {
                break;
            }
        } while (true);
    } catch (mysqli_sql_exception $e) {
        return $e->getMessage();
    }

    return null;
}

$values = [
    'db_hostname' => 'localhost',
    'db_name' => '',
    'db_username' => '',
    'create_tables' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ?? '' only guards a missing key, not one submitted as an array
    // (host[]=x etc.), which would otherwise reach trim()/mysqli_connect()
    // as a fatal TypeError under PHP 8.
    $values['db_hostname'] = trim(is_string($_POST['db_hostname'] ?? null) ? $_POST['db_hostname'] : '');
    $values['db_name'] = trim(is_string($_POST['db_name'] ?? null) ? $_POST['db_name'] : '');
    $values['db_username'] = trim(is_string($_POST['db_username'] ?? null) ? $_POST['db_username'] : '');
    $dbPassword = is_string($_POST['db_password'] ?? null) ? $_POST['db_password'] : '';
    $values['create_tables'] = !empty($_POST['create_tables']);

    if (!verify_csrf_token()) {
        render_page(render_form($values, "<p class='error'>Your session expired. Please try again.</p>"));
        exit;
    }

    if ($values['db_hostname'] === '' || $values['db_name'] === '' || $values['db_username'] === '') {
        render_page(render_form($values, "<p class='error'>Host, database name, and username are all required.</p>"));
        exit;
    }

    $configBuild = build_config($values['db_hostname'], $values['db_name'], $values['db_username'], $dbPassword);

    if (!$configBuild['ok']) {
        $error = htmlspecialchars($configBuild['error']);
        render_page(render_form($values, "<p class='error'>Could not finish setup: $error</p>"));
        exit;
    }

    try {
        $connection = mysqli_connect($values['db_hostname'], $values['db_username'], $dbPassword, $values['db_name']);
    } catch (mysqli_sql_exception $e) {
        $error = htmlspecialchars($e->getMessage());
        render_page(render_form($values, "<p class='error'>Could not connect: $error</p>"));
        exit;
    }

    if ($values['create_tables']) {
        $schemaError = run_schema($connection);
        if ($schemaError !== null) {
            mysqli_close($connection);
            $error = htmlspecialchars($schemaError);
            render_page(render_form($values, "<p class='error'>Connected, but creating the database tables failed: $error</p>"));
            exit;
        }
    }

    mysqli_close($connection);

    $configResult = save_config($configBuild['content']);

    if (!$configResult['ok']) {
        $error = htmlspecialchars($configResult['error']);
        render_page(render_form($values, "<p class='error'>Database is ready, but could not finish setup: $error</p>"));
        exit;
    }

    $warningHtml = $configResult['warning']
        ? "<p class='error'>" . htmlspecialchars($configResult['warning']) . "</p>"
        : '';

    render_page(
        $warningHtml
        . "<p class='success'>Setup complete. PHP Timeclock is now configured.</p>"
        . "<p><a href='login.php'>Continue to the admin login</a> or <a href='index.php'>go to the timeclock</a>.</p>"
    );
    exit;
}

render_page(render_form($values));
