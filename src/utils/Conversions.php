<?php


class VindiConversions {

/**
 * Converts the months, weeks and years of a Trial period into days.
 *
 * Used to send days in parameter  to Vindi.
 *
 *
 * @since 1.0.1
 *
 * @return number
 */
  public static function convertTriggerToDay($number, $type = 'month') {
    $types = array(
      "day" => 1,
      "month" => 30,
      "week" => 7,
      "year" => 365,
    );

    $verifyType = $types[$type];

    if(!$verifyType) {
      return false;
    }

    return intval($number) * intval($verifyType);

  }
}

?>
