<?php

/**
 * Bootstrap-based drop-in replacement for the footer include used by
 * admin/*.php (currently '../footer.php'). Same content as the root
 * footer_bootstrap.php, but with paths adjusted for admin/'s directory
 * depth. Used by admin pages migrated to the new Bootstrap layout (see
 * issue #40); footer.php itself is untouched and still serves every admin
 * page not yet migrated.
 */

echo "<footer class=\"text-center text-muted small py-3 notprint\">\n";

if ($email != "none") {
    echo "  <a href=\"mailto:$email\" class=\"text-muted\">$email</a>&nbsp;&bull;&nbsp;\n";
}

echo "  <a href=\"https://github.com/BoatrightTBC/timeclock\" class=\"text-muted\">$app_name $app_version</a>\n";
echo "</footer>\n";
echo "<script src=\"../scripts/bootstrap.bundle.min.js\"></script>\n";
echo "</body>\n";
echo "</html>\n";
