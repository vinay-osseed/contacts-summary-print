<?php

/**
 * @file
 * Print summary class.
 */

// phpcs:disable
use CRM_ContactsSummaryPrint_ExtensionUtil as E;
// phpcs:enable

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Print summary class.
 */
class CRM_ContactsSummaryPrint_Form_Task_PrintSummary extends CRM_Contact_Form_Task {
  public $pdf_name = "Contact-Summary-List";
  public $html;
  public $all_contacts;

  /**
   * Pre-process the form, setting up title, fetching contacts data, and generating PDF.
   */
  public function preProcess() {
    $this->setTitle('Print Contacts Summary');
    parent::preProcess();

    $this->fetchContactsData();
    $this->generatePDF();
  }

  /**
   * Fetch contacts data based on provided contact IDs.
   */
  private function fetchContactsData() {
    $contacts = [];
    foreach ($this->_contactIds as $contactId) {
      try {
        $contacts[] = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);
      }
      catch (CiviCRM_API3_Exception $e) {
        CRM_Core_Error::debug_log_message('API Error: ' . $e->getMessage());
      }
    }
    $this->all_contacts = $contacts;
  }

  /**
   * Generate the PDF content using a message template.
   */
  private function generatePDF() {
    $msg_tpl = civicrm_api3('MessageTemplate', 'getsingle', ['msg_title' => TPL_TITLE]);
    $send_tpl_params = [
      'messageTemplateID' => (int) $msg_tpl['id'],
      'tplParams' => [
        'contacts' => $this->all_contacts,
        'style' => 'page-break-before: auto',
      ],
      'tokenContext' => ['smarty' => TRUE],
      'PDFFilename' => $this->pdf_name,
    ];
    [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($send_tpl_params);
    $this->assign('templateContent', $html);
    $this->html = $html;
  }

  /**
   * Build the quick form with buttons for different actions.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addDefaultButtons(E::ts('Print Contacts Summary'));
    $this->addButtons([
      ['type' => 'next', 'name' => ts('Download DOCX')],
      ['type' => 'done', 'name' => ts('Download PDF')],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
  }

  /**
   * Process the form actions based on the button clicked.
   */
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

  /**
   * Handle invalid form actions.
   */
  private function handleInvalidAction() {
    CRM_Core_Session::setStatus(E::ts('Invalid action'), E::ts('Error'), 'error');
    $this->controller->setDestination(NULL);
    $this->controller->resetPage($this->getName());
  }

  /**
   * Generate and download the DOCX file.
   */
  private function downloadDOCX() {
    $file_name = $this->pdf_name . ".docx";
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    if (!empty($this->all_contacts)) {
      $this->addContactsToDOCX($section);
    }
    else {
      $section->addText("No contacts found.");
    }

    $tempFile = $this->saveTempFile($phpWord, 'Word2007', $file_name);
    $this->attachAndRedirect($tempFile, $file_name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
  }

  /**
   * Add contact details to the DOCX file.
   *
   * @param \PhpOffice\PhpWord\Element\Section $section
   */
  private function addContactsToDOCX($section) {
    foreach ($this->all_contacts as $contact) {
        $this->addContactDetailsToTable($section, $contact);
    }
  }

  /**
  * Add individual contact details to a table row.
  *
  * @param \PhpOffice\PhpWord\Element\Section $section
  * @param array $contact
  */
  private function addContactDetailsToTable($section, $contact) {
    $table = $section->addTable();

    // Add contact details row
    $table->addRow();
    $cell = $table->addCell(8000); // Full width cell

    $cell->addText("To,");
    if ($contact['contact_type'] == 'Individual') {
        $name = ($contact['prefix'] ?? '') . ' ' . ($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '');
        $cell->addText(trim($name) ?: 'N/A');
    } elseif ($contact['contact_type'] == 'Organization') {
        $cell->addText($contact['organization_name'] ?? 'N/A');
    }
    if (!empty($contact['job_title'])) {
        $cell->addText($contact['job_title']);
    }
    if (!empty($contact['supplemental_address_1'])) {
        $cell->addText($contact['supplemental_address_1']);
    }
    if (!empty($contact['supplemental_address_2'])) {
        $cell->addText($contact['supplemental_address_2']);
    }
    $cityState = ($contact['city'] ?? '') . (!empty($contact['city']) && !empty($contact['state_province']) ? ', ' : '') . ($contact['state_province'] ?? '');
    if (!empty($cityState)) {
        $cell->addText($cityState);
    }
    if (!empty($contact['postal_code'])) {
        $cell->addText($contact['postal_code']);
    }
    if (!empty($contact['phone'])) {
        $cell->addText("Ph - " . $contact['phone']);
    }

    // Add an empty row to create a gap between contacts
    $section->addTextBreak();
    $table->addRow();
    $table->addCell(8000, ['valign' => 'center'])->addText('');
    $table->addRow();
    $table->addCell(8000, ['valign' => 'center'])->addText('');
  }

  /**
   * Save the temporary file in the specified format.
   *
   * @param \PhpOffice\PhpWord\PhpWord $phpWord
   * @param string $format
   * @param string $file_name
   *
   * @return string Path to the temporary file
   */
  private function saveTempFile($phpWord, $format, $file_name) {
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    $objWriter = IOFactory::createWriter($phpWord, $format);
    $objWriter->save($tempFile);
    return $tempFile;
  }

  /**
   * Attach the temporary file to an activity and redirect the user to download it.
   *
   * @param string $tempFile
   * @param string $file_name
   * @param string $mime_type
   */
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

  /**
   * Redirect to download the attached file or handle errors.
   *
   * @param array $attachment
   */
  private function redirectAttachment($attachment) {
    if (!empty($attachment['id'])) {
      CRM_Utils_System::redirect($attachment['values'][0]['url']);
    }
    else {
      CRM_Core_Session::setStatus(E::ts('Failed to create attachment'), E::ts('Error'), 'error');
      $this->controller->setDestination(NULL);
      $this->controller->resetPage($this->getName());
    }
  }

  /**
   * Generate and download the PDF file with custom margins.
   */
  private function downloadPDF() {
    $file_name = $this->pdf_name . ".pdf";

    // Define the custom margins in CSS.
    $html_with_margins = '<style>
      @page {
        margin: 1cm 2cm;
      }
      .row-gap {
        padding: 1cm 0 0 0;
      }
    </style>' . $this->html;

    // Generate the PDF content with the specified margins.
    $pdf_contents = CRM_Utils_PDF_Utils::html2pdf($html_with_margins, $file_name, TRUE);
    $tempFile = $this->saveTempPDF($file_name, $pdf_contents);
    $this->attachAndRedirect($tempFile, $file_name, 'application/pdf');
  }

  /**
   * Save the temporary PDF file.
   *
   * @param string $file_name
   * @param string $pdf_contents
   *
   * @return string Path to the temporary PDF file
   */
  private function saveTempPDF($file_name, $pdf_contents) {
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    file_put_contents($tempFile, $pdf_contents);
    return $tempFile;
  }

}
