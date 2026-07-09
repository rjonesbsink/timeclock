<?php

if (file_exists("menu.php")) {
    header("Location: menu.php");
} elseif (file_exists("punchclock/menu.php")) {
    header("Location: punchclock/menu.php");
} else {
    header("Location: timeclock.php");
}
