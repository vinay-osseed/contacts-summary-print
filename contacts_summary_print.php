<?php

/**
 * @file
 * contacts_summary_print main.
 */

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
  contacts_summary_print_add_message_template();
  contacts_summary_print_modify_address_phone_schema();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function contacts_summary_print_civicrm_enable(): void {
  _contacts_summary_print_civix_civicrm_enable();
  contacts_summary_print_modify_address_phone_schema();
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function contacts_summary_print_civicrm_xmlMenu(&$files) {
  $files[] = dirname(__FILE__) . '/xml/Menu/CustomActions.xml';
}

/**
 * Implements hook_civicrm_searchTasks().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchTasks
 */
function contacts_summary_print_civicrm_searchTasks($objectName, &$tasks) {
  if ($objectName == 'contact') {
    $tasks[] = [
      'title' => ts('Print Contacts Summary'),
      'class' => 'CRM_ContactsSummaryPrint_Form_Task_PrintSummary',
      'result' => TRUE,
      'url' => 'civicrm/task/print-document-summary',
      'icon' => 'fa-file-pdf-o',
    ];
  }
}

define("TPL_TITLE", "Contacts Summary Print Template");

/**
 * Adds a custom message template.
 */
function contacts_summary_print_add_message_template() {
  $templatePath = __DIR__ . '/templates/default.tpl';
  $templateContent = file_get_contents($templatePath);

  if ($templateContent !== FALSE) {
    try {
      civicrm_api3('MessageTemplate', 'create', [
        'msg_title' => TPL_TITLE,
        'msg_subject' => 'Contacts Summary Print',
        'msg_text' => $templateContent,
        'msg_html' => $templateContent,
        'is_active' => 1,
        'workflow_id' => 1,
        // Assuming 'User-Selectable' type.
        'msg_template_type_id' => 3,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Error::debug_log_message('API Error: ' . $e->getMessage());
    }
  }
}

/**
 * Modify the address schema to change the length of certain fields.
 */
function contacts_summary_print_modify_address_phone_schema() {
  // Modify the address table to increase the size of the address fields
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY street_address VARCHAR(255)");
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_1 VARCHAR(255)");
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_2 VARCHAR(255)");
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_3 VARCHAR(255)");
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_phone MODIFY phone VARCHAR(50)");
  CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_phone MODIFY phone_numeric VARCHAR(50)");
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function contacts_summary_print_civicrm_uninstall(): void {
  // Remove the message template.
  try {
    // Fetch the message template by title.
    $result = civicrm_api3('MessageTemplate', 'get', [
      'msg_title' => TPL_TITLE,
    ]);

    // Check if the template exists and delete it.
    if (!empty($result['values'])) {
      foreach ($result['values'] as $template) {
        civicrm_api3('MessageTemplate', 'delete', ['id' => $template['id']]);
      }
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    CRM_Core_Error::debug_log_message('API Error: ' . $e->getMessage());
  }

  // Revert the address & phone table changes, if fields are empty/deleted.
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY street_address VARCHAR(96)");
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_1 VARCHAR(96)");
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_2 VARCHAR(96)");
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_address MODIFY supplemental_address_3 VARCHAR(96)");
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_phone MODIFY phone VARCHAR(32)");
  // CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_phone MODIFY phone_numeric VARCHAR(32)");
}
