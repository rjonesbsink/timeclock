<?php

require_once 'lib/session.php';
start_secure_session();
if (isset($_SESSION['valid_user'])) {
    unset($_SESSION['valid_user']);
}
if (isset($_SESSION['valid_reports_user'])) {
    unset($_SESSION['valid_reports_user']);
}
if (isset($_SESSION['time_admin_valid_user'])) {
    unset($_SESSION['time_admin_valid_user']);
}

session_destroy();
echo "<script type='text/javascript' language='javascript'> window.location.href = 'index.php';</script>";
