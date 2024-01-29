<?php

/**
 * The function calculates the number of days between two given dates.
 * 
 * @param string The initial date from which you want to calculate the difference.
 * @param string The final_date parameter is the date that you want to calculate the difference
 * from the initial date.
 * 
 * @return int number of days between the initial date and the final date.
 */
function date_diff($init_date, $final_date) {
    $start = new DateTime($init_date);
    $end = new DateTime($final_date);
    $diff = $start->diff($end);
    return $diff->days;
}