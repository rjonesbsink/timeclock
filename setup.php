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
require_once __DIR__ . '/functions.php';

const CONFIG_FILE = __DIR__ . '/config.inc.php';
const CONFIG_TEMPLATE = __DIR__ . '/config.inc.php.dist';
const SCHEMA_FILE = __DIR__ . '/sql/create_tables.sql';

// Matches admin/usercreate.php's USERNAME_PATTERN, so an account created
// here is never rejected by the same check everywhere else in the app.
const USERNAME_PATTERN = "^([[:alnum:]]| |-|'|,)+$";
const MIN_ADMIN_PASSWORD_LENGTH = 8;
const ICAO_PATTERN = '^[a-zA-Z]{4}$';

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

function render_timezone_options($selected)
{
    $html = '';
    foreach (DateTimeZone::listIdentifiers() as $tz) {
        $sel = ($tz === $selected) ? ' selected' : '';
        $tzEscaped = htmlspecialchars($tz);
        $html .= "<option value=\"$tzEscaped\"$sel>$tzEscaped</option>\n";
    }

    return $html;
}

function render_form($values, $errorHtml = '')
{
    $host = htmlspecialchars($values['db_hostname']);
    $name = htmlspecialchars($values['db_name']);
    $user = htmlspecialchars($values['db_username']);
    $createTablesChecked = $values['create_tables'] ? 'checked' : '';

    $adminUsername = htmlspecialchars($values['admin_username']);
    $adminDisplayname = htmlspecialchars($values['admin_displayname']);
    $adminEmail = htmlspecialchars($values['admin_email']);

    $weatherChecked = $values['weather_enabled'] ? 'checked' : '';
    $weatherUnitsF = ($values['weather_units'] === 'f') ? 'checked' : '';
    $weatherUnitsC = ($values['weather_units'] === 'c') ? 'checked' : '';
    $weatherIcao = htmlspecialchars($values['weather_icao']);
    $weatherStation = htmlspecialchars($values['weather_station']);
    $weatherDistMi = htmlspecialchars($values['weather_dist_mi']);
    $weatherDistKm = htmlspecialchars($values['weather_dist_km']);
    $weatherDirection = htmlspecialchars($values['weather_direction']);
    $timezoneOptions = render_timezone_options($values['weather_timezone']);

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

    $html .= "<h2>Admin Account</h2>\n";
    $html .= "<p class='hint'>Required when creating the database tables above -- there's no other way to log in afterward."
        . " If you unchecked \"create the database tables\" because this database already has accounts, leave these blank to skip.</p>\n";
    $html .= "<label for='admin_username'>Admin Username</label>\n";
    $html .= "<input type='text' id='admin_username' name='admin_username' value=\"$adminUsername\">\n";
    $html .= "<label for='admin_displayname'>Admin Display Name</label>\n";
    $html .= "<input type='text' id='admin_displayname' name='admin_displayname' value=\"$adminDisplayname\">\n";
    $html .= "<label for='admin_email'>Admin Email</label>\n";
    $html .= "<input type='text' id='admin_email' name='admin_email' value=\"$adminEmail\">\n";
    $html .= "<label for='admin_password'>Admin Password</label>\n";
    $html .= "<input type='password' id='admin_password' name='admin_password'>\n";
    $html .= "<p class='hint'>At least " . MIN_ADMIN_PASSWORD_LENGTH . " characters.</p>\n";
    $html .= "<label for='admin_confirm_password'>Confirm Admin Password</label>\n";
    $html .= "<input type='password' id='admin_confirm_password' name='admin_confirm_password'>\n";

    $html .= "<h2>Weather Display (optional)</h2>\n";
    $html .= "<p class='hint'>Shows current conditions from a nearby METAR weather station in the sidebar."
        . " Leave \"Show weather\" unchecked to skip this entirely.</p>\n";
    $html .= "<label><input type='checkbox' id='weather_enabled' name='weather_enabled' value='1' $weatherChecked> Show weather</label>\n";
    $html .= "<label>Units</label>\n";
    $html .= "<label><input type='radio' name='weather_units' value='f' $weatherUnitsF> Fahrenheit</label>\n";
    $html .= "<label><input type='radio' name='weather_units' value='c' $weatherUnitsC> Celsius</label>\n";
    $html .= "<label for='weather_icao'>Station ICAO Code</label>\n";
    $html .= "<input type='text' id='weather_icao' name='weather_icao' value=\"$weatherIcao\" maxlength='4'>\n";
    $html .= "<p class='hint'>4-letter code, e.g. \"KTOP\". Find one near you at "
        . "<a href='https://pilotweb.nas.faa.gov/qryhtml/icao/' target='_blank'>pilotweb.nas.faa.gov</a>.</p>\n";
    $html .= "<label for='weather_station'>Station Name</label>\n";
    $html .= "<input type='text' id='weather_station' name='weather_station' value=\"$weatherStation\">\n";
    $html .= "<p class='hint'>e.g. \"Topeka, Kansas\"</p>\n";
    $html .= "<label for='weather_dist_mi'>Distance From You (miles)</label>\n";
    $html .= "<input type='text' id='weather_dist_mi' name='weather_dist_mi' value=\"$weatherDistMi\">\n";
    $html .= "<label for='weather_dist_km'>Distance From You (km)</label>\n";
    $html .= "<input type='text' id='weather_dist_km' name='weather_dist_km' value=\"$weatherDistKm\">\n";
    $html .= "<label for='weather_direction'>Direction From You</label>\n";
    $html .= "<input type='text' id='weather_direction' name='weather_direction' value=\"$weatherDirection\">\n";
    $html .= "<p class='hint'>e.g. \"NE\"</p>\n";
    $html .= "<label for='weather_timezone'>Timezone</label>\n";
    $html .= "<select id='weather_timezone' name='weather_timezone'>\n$timezoneOptions</select>\n";

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
function build_config($dbHostname, $dbName, $dbUsername, $dbPassword, $weather)
{
    $template = file_get_contents(CONFIG_TEMPLATE);

    $substitutions = [
        'db_hostname' => $dbHostname,
        'db_username' => $dbUsername,
        'db_password' => $dbPassword,
        'db_name' => $dbName,
        'display_weather' => $weather['enabled'] ? 'yes' : 'no',
        'weather_units' => $weather['units'],
    ];

    foreach ($substitutions as $variable => $value) {
        // preg_replace_callback, not preg_replace: the replacement here
        // embeds a submitted value (most importantly the DB password),
        // and preg_replace's replacement string treats $1/\1-style
        // sequences as backreferences -- a password containing a literal
        // "$1" would otherwise be silently mangled in the written file.
        $template = preg_replace_callback(
            '/^\$' . $variable . '\s*=\s*".*";\r?$/m',
            fn () => '$' . $variable . ' = ' . var_export($value, true) . ';',
            $template,
            1,
            $replacementCount
        );
        if ($replacementCount !== 1) {
            return ['ok' => false, 'error' => "Could not find \$$variable in config.inc.php.dist -- the template may have changed."];
        }
    }

    // $WxTimeZone is single-quoted (not double), so it needs its own
    // pattern; the commented-out example assignments in the template are
    // indented, so the ^\$-anchored pattern below only matches the real one.
    $template = preg_replace_callback(
        '/^\$WxTimeZone\s*=\s*\'.*\';[ \t]*\r?$/m',
        fn () => '$WxTimeZone = ' . var_export($weather['timezone'], true) . ';',
        $template,
        1,
        $tzCount
    );
    if ($tzCount !== 1) {
        return ['ok' => false, 'error' => "Could not find \$WxTimeZone in config.inc.php.dist -- the template may have changed."];
    }

    // The template shows $WxList assigned twice in a row (array(), then the
    // real example) to demonstrate overwriting it. Replacing only the first
    // occurrence would leave the second -- the one that actually takes
    // effect -- untouched, so every $WxList = array(...); line gets replaced
    // with the same submitted value here instead of limiting to one match.
    $wxListEntry = $weather['enabled']
        ? $weather['icao'] . '|' . $weather['station'] . '|' . $weather['dist_mi'] . '|' . $weather['dist_km'] . '|' . $weather['direction'] . '|'
        : null;
    $wxListLiteral = $wxListEntry === null ? 'array()' : 'array(' . var_export($wxListEntry, true) . ')';
    $template = preg_replace_callback(
        '/^\$WxList\s*=\s*array\(.*\);\r?$/m',
        fn () => '$WxList = ' . $wxListLiteral . ';',
        $template,
        -1,
        $wxListCount
    );
    if ($wxListCount < 1) {
        return ['ok' => false, 'error' => "Could not find \$WxList in config.inc.php.dist -- the template may have changed."];
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

// Returns ['ok' => true] on success, or ['ok' => false, 'error' => string]
// if the account could not be created (most likely a duplicate
// empfullname, if re-running setup against a database that already has
// this account).
function create_admin_account($connection, $username, $displayName, $email, $password)
{
    $hash = tc_hash_password($password);
    $stmt = null;

    try {
        $stmt = $connection->prepare(
            'INSERT INTO employees (empfullname, employee_passwd, displayname, email, `groups`, office, admin, reports, time_admin, disabled)'
            . " VALUES (?, ?, ?, ?, '', '', 1, 1, 1, 0)"
        );
        $stmt->bind_param('ssss', $username, $hash, $displayName, $email);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        if ($stmt) {
            $stmt->close();
        }

        return ['ok' => false, 'error' => $e->getMessage()];
    }

    $stmt->close();

    return ['ok' => true];
}

$values = [
    'db_hostname' => 'localhost',
    'db_name' => '',
    'db_username' => '',
    'create_tables' => true,
    'admin_username' => '',
    'admin_displayname' => '',
    'admin_email' => '',
    'weather_enabled' => false,
    'weather_units' => 'f',
    'weather_icao' => '',
    'weather_station' => '',
    'weather_dist_mi' => '',
    'weather_dist_km' => '',
    'weather_direction' => '',
    'weather_timezone' => 'America/Chicago',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['db_hostname'] = trim(post_string('db_hostname'));
    $values['db_name'] = trim(post_string('db_name'));
    $values['db_username'] = trim(post_string('db_username'));
    $dbPassword = post_string('db_password');
    $values['create_tables'] = !empty($_POST['create_tables']);

    $values['admin_username'] = trim(post_string('admin_username'));
    $values['admin_displayname'] = trim(post_string('admin_displayname'));
    $values['admin_email'] = trim(post_string('admin_email'));
    $adminPassword = post_string('admin_password');
    $adminConfirmPassword = post_string('admin_confirm_password');

    $values['weather_enabled'] = !empty($_POST['weather_enabled']);
    $postedWeatherUnits = post_string('weather_units');
    $values['weather_units'] = ($postedWeatherUnits === 'c') ? 'c' : 'f';
    $values['weather_icao'] = trim(post_string('weather_icao'));
    $values['weather_station'] = trim(post_string('weather_station'));
    $values['weather_dist_mi'] = trim(post_string('weather_dist_mi'));
    $values['weather_dist_km'] = trim(post_string('weather_dist_km'));
    $values['weather_direction'] = trim(post_string('weather_direction'));
    $values['weather_timezone'] = post_string('weather_timezone');

    if (!verify_csrf_token()) {
        render_page(render_form($values, "<p class='error'>Your session expired. Please try again.</p>"));
        exit;
    }

    if ($values['db_hostname'] === '' || $values['db_name'] === '' || $values['db_username'] === '') {
        render_page(render_form($values, "<p class='error'>Host, database name, and username are all required.</p>"));
        exit;
    }

    // An admin account is required when creating fresh tables (there's no
    // other way to log in afterward), and optional -- but still validated
    // the same way if any field was filled in -- otherwise.
    $wantsAdmin = $values['create_tables']
        || $values['admin_username'] !== ''
        || $values['admin_displayname'] !== ''
        || $values['admin_email'] !== ''
        || $adminPassword !== ''
        || $adminConfirmPassword !== '';

    if ($wantsAdmin) {
        if (
            $values['admin_username'] === '' || $values['admin_displayname'] === '' || $values['admin_email'] === ''
            || $adminPassword === '' || $adminConfirmPassword === ''
        ) {
            render_page(render_form($values, "<p class='error'>Admin username, display name, email, and password are all required.</p>"));
            exit;
        }

        if (!preg_match('/' . USERNAME_PATTERN . '/i', $values['admin_username'])) {
            render_page(render_form(
                $values,
                "<p class='error'>Admin username may only contain letters, numbers, spaces, hyphens, apostrophes, and commas.</p>"
            ));
            exit;
        }

        if (!preg_match('/' . USERNAME_PATTERN . '/i', $values['admin_displayname'])) {
            render_page(render_form(
                $values,
                "<p class='error'>Admin display name may only contain letters, numbers, spaces, hyphens, apostrophes, and commas.</p>"
            ));
            exit;
        }

        if (!filter_var($values['admin_email'], FILTER_VALIDATE_EMAIL)) {
            render_page(render_form($values, "<p class='error'>Admin email address is not valid.</p>"));
            exit;
        }

        if ($adminPassword !== $adminConfirmPassword) {
            render_page(render_form($values, "<p class='error'>Admin password and confirmation do not match.</p>"));
            exit;
        }

        if (strlen($adminPassword) < MIN_ADMIN_PASSWORD_LENGTH) {
            render_page(render_form($values, "<p class='error'>Admin password must be at least " . MIN_ADMIN_PASSWORD_LENGTH . " characters.</p>"));
            exit;
        }
    }

    if ($values['weather_enabled']) {
        if (!preg_match('/' . ICAO_PATTERN . '/', $values['weather_icao'])) {
            render_page(render_form($values, "<p class='error'>Station ICAO code must be exactly 4 letters.</p>"));
            exit;
        }

        if ($values['weather_station'] === '' || $values['weather_direction'] === '') {
            render_page(render_form($values, "<p class='error'>Station name and direction are required when weather display is enabled.</p>"));
            exit;
        }

        if (!is_numeric($values['weather_dist_mi']) || !is_numeric($values['weather_dist_km'])) {
            render_page(render_form($values, "<p class='error'>Distance from you must be a number.</p>"));
            exit;
        }

        // The pipe character is the $WxList field separator (see
        // include-metar-display.php); allowing it in a free-text field
        // would silently corrupt the entry's format.
        $weatherTextFields = [$values['weather_station'], $values['weather_direction']];
        foreach ($weatherTextFields as $field) {
            if (strpos($field, '|') !== false) {
                render_page(render_form($values, "<p class='error'>Station name and direction cannot contain the '|' character.</p>"));
                exit;
            }
        }

        if (!in_array($values['weather_timezone'], DateTimeZone::listIdentifiers(), true)) {
            render_page(render_form($values, "<p class='error'>Timezone is not valid.</p>"));
            exit;
        }
    }

    $configBuild = build_config($values['db_hostname'], $values['db_name'], $values['db_username'], $dbPassword, [
        'enabled' => $values['weather_enabled'],
        'units' => $values['weather_units'],
        'icao' => $values['weather_icao'],
        'station' => $values['weather_station'],
        'dist_mi' => $values['weather_dist_mi'],
        'dist_km' => $values['weather_dist_km'],
        'direction' => $values['weather_direction'],
        'timezone' => $values['weather_timezone'],
    ]);

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

    if ($wantsAdmin) {
        $adminResult = create_admin_account(
            $connection,
            $values['admin_username'],
            $values['admin_displayname'],
            $values['admin_email'],
            $adminPassword
        );
        if (!$adminResult['ok']) {
            mysqli_close($connection);
            $error = htmlspecialchars($adminResult['error']);
            render_page(render_form($values, "<p class='error'>Database is ready, but could not create the admin account: $error</p>"));
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
