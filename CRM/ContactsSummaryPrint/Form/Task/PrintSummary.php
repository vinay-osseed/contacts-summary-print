<?php

use CRM_ContactsSummaryPrint_ExtensionUtil as E;

class CRM_ContactsSummaryPrint_Form_Task_PrintSummary extends CRM_Contact_Form_Task {
  public $pdf_name = "Contact-Summary-List";
  public $html;
  public $all_contacts;

  public function preProcess() {
    $this->setTitle('Print Contacts Summary');
    parent::preProcess();

    $this->fetchContactsData();
    $this->generatePDF();
  }

  private function fetchContactsData() {
    $contacts = [];
    foreach ($this->_contactIds as $contactId) {
      try {
        $contacts[] = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);
      } catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Error::debug_log_message('API Error: ' . $e->getMessage());
      }
    }
    $this->all_contacts = $contacts;
  }

  private function generatePDF() {
    $msg_tpl = civicrm_api3('MessageTemplate', 'getsingle', ['msg_title' => TPL_TITLE]);
    $send_tpl_params = [
      'messageTemplateID' => (int)$msg_tpl['id'],
      'tplParams' => ['contacts' => $this->all_contacts],
      'tokenContext' => ['smarty' => TRUE],
      'PDFFilename' => $this->pdf_name,
    ];
    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($send_tpl_params);
    $this->assign('templateContent', $html);
    $this->html = $html;
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addDefaultButtons(E::ts('Print Contacts Summary'));
    $this->addButtons([
      ['type' => 'next', 'name' => ts('Download DOCX')],
      ['type' => 'done', 'name' => ts('Download PDF')],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
  }

  public function postProcess() {
    $action = $this->controller->getButtonName();
    switch ($action) {
      case '_qf_PrintSummary_next':
        $this->downloadDOCX();
        break;
      case '_qf_PrintSummary_done':
        $this->downloadPDF();
        break;
      default:
        $this->handleInvalidAction();
        break;
    }
  }

  private function handleInvalidAction() {
    CRM_Core_Session::setStatus(E::ts('Invalid action'), E::ts('Error'), 'error');
    $this->controller->setDestination(NULL);
    $this->controller->resetPage($this->getName());
  }

  private function downloadDOCX() {
    $file_name = $this->pdf_name . ".docx";
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();

    if (!empty($this->all_contacts)) {
      $this->addContactsToDOCX($section);
    } else {
      $section->addText("No contacts found.");
    }

    $tempFile = $this->saveTempFile($phpWord, 'Word2007', $file_name);
    $this->attachAndRedirect($tempFile, $file_name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
  }

  private function addContactsToDOCX($section) {
    $table = $section->addTable();
    $cellIndex = 0;
    foreach ($this->all_contacts as $contact) {
      if ($cellIndex % 2 == 0) {
        $table->addRow(1000);
      }
      $cell = $table->addCell(5000);
      $this->addContactDetailsToCell($cell, $contact);
      $cellIndex++;
      if ($cellIndex % 2 == 0) {
        $table->addRow(250);
        $table->addCell(250);
        $table->addCell(250);
      }
    }
  }

  private function addContactDetailsToCell($cell, $contact) {
    if ($contact['contact_type'] == 'Organization') {
      $cell->addText("Organization Name: " . ($contact['organization_name'] ?? 'N/A'));
    } elseif ($contact['contact_type'] == 'Individual') {
      $name = ($contact['prefix'] ?? '') . ' ' . ($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '');
      $cell->addText("Name: " . trim($name));
    }
    $cell->addText("Address 1: " . ($contact['street_address'] ?? 'N/A'));
    $cell->addText("Address 2: " . ($contact['supplemental_address_1'] ?? 'N/A'));
    $cell->addText("City: " . ($contact['city'] ?? 'N/A'));
    $cell->addText("State: " . ($contact['state_province'] ?? 'N/A'));
    $cell->addText("Zip Code: " . ($contact['postal_code'] ?? 'N/A'));
    $cell->addText("Mobile Number: " . ($contact['phone'] ?? 'N/A'));
  }

  private function saveTempFile($phpWord, $format, $file_name) {
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, $format);
    $objWriter->save($tempFile);
    return $tempFile;
  }

  private function attachAndRedirect($tempFile, $file_name, $mime_type) {
    $logged_contact_id = CRM_Core_Session::getLoggedInContactID();
    $activity_type = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Print PDF Letter');
    $activity = civicrm_api3('Activity', 'create', [
      'subject' => "Download $file_name",
      'source_contact_id' => $logged_contact_id,
      'activity_type_id' => $activity_type,
      'target_contact_id' => $logged_contact_id,
    ]);
    $attachment = civicrm_api3('Attachment', 'create', [
      'sequential' => 1,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
      'name' => basename($tempFile),
      'mime_type' => $mime_type,
      'content' => file_get_contents($tempFile),
    ]);
    $this->redirectAttachment($attachment);
    unlink($tempFile);
  }

  private function redirectAttachment($attachment) {
    if (!empty($attachment['id'])) {
      CRM_Utils_System::redirect($attachment['values'][0]['url']);
    } else {
      CRM_Core_Session::setStatus(E::ts('Failed to create attachment'), E::ts('Error'), 'error');
      $this->controller->setDestination(NULL);
      $this->controller->resetPage($this->getName());
    }
  }

  private function downloadPDF() {
    $file_name = $this->pdf_name . ".pdf";
    $pdf_contents = CRM_Utils_PDF_Utils::html2pdf($this->html, $file_name, true);
    $tempFile = $this->saveTempPDF($file_name, $pdf_contents);
    $this->attachAndRedirect($tempFile, $file_name, 'application/pdf');
  }

  private function saveTempPDF($file_name, $pdf_contents) {
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    file_put_contents($tempFile, $pdf_contents);
    return $tempFile;
  }
}