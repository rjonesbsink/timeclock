<?php

require_once '../lib/session.php';
start_secure_session();

include '../config.inc.php';
include 'header_bootstrap.php';
include 'topmain_bootstrap.php';
echo "<title>$title - Administration</title>\n";

$self = htmlentities($_SERVER['PHP_SELF']);
$request = $_SERVER['REQUEST_METHOD'];
$row_count = '0';
$row_color = ($row_count % 2) ? $color2 : $color1;

require_once '../lib/auth.php';
require_valid_user();

echo "<div class=\"container-fluid mt-3\">\n";
echo "  <div class=\"row\">\n";
include_once 'leftnav_bootstrap.php';
echo "    <div class=\"col-md-9\">\n";

include '../templates/admin_index_tpl.php';

echo "    </div>\n";
echo "  </div>\n";
echo "</div>\n";
include_once 'footer_bootstrap.php';
