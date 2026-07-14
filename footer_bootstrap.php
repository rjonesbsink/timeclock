<?php

/**
 * Bootstrap-based drop-in replacement for footer.php. Used by pages migrated
 * to the new Bootstrap layout (see issue #40); footer.php itself is
 * untouched and still serves every page not yet migrated.
 */

echo "<footer class=\"text-center text-muted small py-3 notprint\">\n";

if ($email != "none") {
    echo "  <a href=\"mailto:$email\" class=\"text-muted\">$email</a>&nbsp;&bull;&nbsp;\n";
}

echo "  <a href=\"https://github.com/BoatrightTBC/timeclock\" class=\"text-muted\">$app_name $app_version</a>\n";
echo "</footer>\n";
echo "<script src=\"scripts/bootstrap.bundle.min.js\"></script>\n";
echo "</body>\n";
echo "</html>\n";
