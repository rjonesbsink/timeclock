<?php

$row_count = 0;
$page_count = 0;
$table_opened = false;

while ($row = mysqli_fetch_array($result)) {
    $display_stamp = "" . $row["timestamp"] . "";
    $time = date($timefmt, $display_stamp);
    $date = date($datefmt, $display_stamp);

    if ($row_count == 0) {
        $table_opened = true;

        if ($page_count == 0) {
            // display sortable column headings for main page //

            $column_count = 5
                + ($display_office_name == "yes" ? 1 : 0)
                + ($display_group_name == "yes" ? 1 : 0);

            echo "<table class=\"table table-sm align-middle\">\n";
            echo "  <thead>\n";

            if (!isset($_GET['printer_friendly'])) {
                echo "    <tr class=\"notprint\"><td colspan=$column_count class=\"text-end\">
                            <a href='timeclock.php?printer_friendly=true'>printer friendly page</a></td></tr>\n";
            }

            echo "    <tr class=\"notprint\">\n";
            echo "      <th><a href='$current_page?sortcolumn=empfullname&sortdirection=$sortnewdirection'>Name</a></th>\n";
            echo "      <th><a href='$current_page?sortcolumn=inout&sortdirection=$sortnewdirection'>In/Out</a></th>\n";
            echo "      <th><a href='$current_page?sortcolumn=tstamp&sortdirection=$sortnewdirection'>Time</a></th>\n";
            echo "      <th><a href='$current_page?sortcolumn=tstamp&sortdirection=$sortnewdirection'>Date</a></th>\n";

            if ($display_office_name == "yes") {
                echo "      <th><a href='$current_page?sortcolumn=office&sortdirection=$sortnewdirection'>Office</a></th>\n";
            }

            if ($display_group_name == "yes") {
                echo "      <th><a href='$current_page?sortcolumn=groups&sortdirection=$sortnewdirection'>Group</a></th>\n";
            }

            echo "      <th><a href='$current_page?sortcolumn=notes&sortdirection=$sortnewdirection'>Notes</a></th>\n";
            echo "    </tr>\n";
        } else {
            // display report name and page number of printed report above the column headings of each printed page //

            $temp_page_count = $page_count + 1;
        }

        echo "    <tr class=\"notdisplay\">\n";
        echo "      <th>Name</th>\n";
        echo "      <th>In/Out</th>\n";
        echo "      <th>Time</th>\n";
        echo "      <th>Date</th>\n";

        if ($display_office_name == "yes") {
            echo "      <th>Office</th>\n";
        }

        if ($display_group_name == "yes") {
            echo "      <th>Group</th>\n";
        }

        echo "      <th>Notes</th>\n";
        echo "    </tr>\n";
        echo "  </thead>\n";
        echo "  <tbody>\n";
    }

    // begin alternating row colors //

    $row_color = ($row_count % 2) ? $color1 : $color2;

    // display the query results //

    $display_stamp = $display_stamp + @$tzo;
    $time = date($timefmt, $display_stamp);
    $date = date($datefmt, $display_stamp);

    echo "    <tr style=\"background-color:$row_color;\">\n";

    if ($show_display_name == "yes") {
        echo stripslashes("      <td>" . htmlentities($row["displayname"]) . "</td>\n");
    } elseif ($show_display_name == "no") {
        echo stripslashes("      <td>" . htmlentities($row["empfullname"]) . "</td>\n");
    }

    echo "      <td style=\"color:" . htmlentities($row["color"]) . ";\">" . htmlentities($row["inout"]) . "</td>\n";
    echo "      <td>" . $time . "</td>\n";
    echo "      <td>" . $date . "</td>\n";

    if ($display_office_name == "yes") {
        echo "      <td>" . htmlentities($row["office"]) . "</td>\n";
    }

    if ($display_group_name == "yes") {
        echo "      <td>" . htmlentities($row["groups"]) . "</td>\n";
    }

    echo stripslashes("      <td>" . htmlentities((string) $row["notes"]) . "</td>\n");
    echo "    </tr>\n";

    $row_count++;

    // output 40 rows per printed page //

    if ($row_count == 40) {
        echo "    <tr style=\"page-break-before:always;\"></tr>\n";
        $row_count = 0;
        $page_count++;
    }
}

if ($table_opened) {
    echo "  </tbody>\n";
    echo "</table>\n";
}

((mysqli_free_result($result) || (is_object($result) && (get_class($result) == "mysqli_result"))) ? true : false);
