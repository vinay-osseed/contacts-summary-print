<?php

require_once 'contacts_summary_print.civix.php';
// phpcs:disable
use CRM_ContactsSummaryPrint_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function contacts_summary_print_civicrm_config(&$config): void {
  _contacts_summary_print_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function contacts_summary_print_civicrm_install(): void {
  _contacts_summary_print_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function contacts_summary_print_civicrm_enable(): void {
  _contacts_summary_print_civix_civicrm_enable();
}
