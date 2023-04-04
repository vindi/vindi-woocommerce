<?php

namespace VindiPaymentGateways;

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
  public static function convert_interval($interval_count, $interval_type = 'month') {
    $interval_multiplier = array(
      "day" => 1,
      "week" => 7,
      "month" => 1,
      "year" => 12,
    );
    $interval_types = array(
      "day" => "days",
      "week" => "days",
      "month" => "months",
      "year" => "months",
    );

    $get_interval_multiplier = $interval_multiplier[$interval_type];
    $get_type = $interval_types[$interval_type];

    if(!$get_type || !$get_interval_multiplier) {
      return false;
    }

    return array(
      'interval' => $get_type,
      'interval_count' => intval($interval_count) * intval($get_interval_multiplier)
    );

  }
}

?>
