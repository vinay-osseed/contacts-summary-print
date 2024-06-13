<?php

use CRM_ContactsSummaryPrint_ExtensionUtil as E;

class CRM_ContactsSummaryPrint_Form_Task_PrintSummary extends CRM_Contact_Form_Task {
  public $pdf_name;
  public $html;
  public $all_contacts;

  public function preProcess() {
    $this->setTitle('Print Contacts Summary');
    parent::preProcess();

    $contactIds = $this->_contactIds;

    // Fetch contacts data based on $contactIds
    $contacts = [];
    foreach ($contactIds as $contactId) {
      $params = ['id' => $contactId];
      try {
        $result = civicrm_api3('Contact', 'getsingle', $params);
        $contacts[] = $result;
      } catch (CiviCRM_API3_Exception $e) {
        // Handle the error, possibly log it and continue
        CRM_Core_Error::debug_log_message('API Error: ' . $e->getMessage());
      }
    }
    $this->all_contacts = $contacts;

    // Load template
    $msg_tpl = civicrm_api3('MessageTemplate', 'getsingle', [
      'msg_title' => TPL_TITLE,
    ]);

    /* Generate the pdf file. */
    $this->pdf_name = "Contact-Summary-List";

    /* Send the message template parameters. */
    $send_tpl_params = [
      'messageTemplateID' =>(int) $msg_tpl['id'],
      'tplParams' => ['contacts' => $contacts],
      'tokenContext' => ['smarty' => TRUE],
      'PDFFilename' => $this->pdf_name,
    ];

    /* Generate the html code for the pdf file. */
    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($send_tpl_params);

    // Assign contacts data to the template
    $this->assign('templateContent', $html);
    $this->html = $html;
  }

  public function buildQuickForm() {
    parent::buildQuickForm();

    // Add a submit button
    $this->addDefaultButtons(E::ts('Print Contacts Summary'));

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Download DOCX'),
        // 'isDefault' => TRUE,
      ],
      [
        'type' => 'done',
        'name' => ts('Download PDF'),
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function postProcess() {
    $action = $this->controller->getButtonName();

    switch ($action) {
      case '_qf_PrintSummary_next': // Download DOCX
        $this->downloadDOCX();
        break;
      case '_qf_PrintSummary_done': // Download PDF
        $this->downloadPDF();
        break;
      default:
        // Handle other actions or no action specified
        CRM_Core_Session::setStatus(E::ts('Invalid action'), E::ts('Error'), 'error');
        $this->controller->setDestination(NULL);
        $this->controller->resetPage($this->getName());
        break;
    }
}

  private function downloadDOCX() {
    $file_name = $this->pdf_name . ".docx";

    // Use $this->all_contacts to get the contacts data
    $contacts = $this->all_contacts;

    // Create a PHPWord document
    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    // Define section style with narrow margins
    // $sectionStyle = [
    //   'marginLeft' => 500, // Narrow left margin (in twips)
    //   'marginRight' => 500, // Narrow right margin (in twips)
    //   'marginTop' => 500, // Narrow top margin (in twips)
    //   'marginBottom' => 500, // Narrow bottom margin (in twips)
    // ];

    // Add a section with the specified style
    $section = $phpWord->addSection(); // $sectionStyle for custom margin

    if (!empty($contacts)) {
      $table = $section->addTable();
      $cellIndex = 0;

      foreach ($contacts as $contact) {
        if ($cellIndex % 2 == 0) {
          $table->addRow(1000); // Set the row height (in twips), 1000 twips is approximately 1.76 cm or 0.69 inches
        }

        $cell = $table->addCell(5000); // Adjust the width as needed

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

        $cellIndex++;

        // Add an empty row to create a gap between the rows
        if ($cellIndex % 2 == 0) {
          $table->addRow(250); // Adding an empty row with a smaller height to create a gap
          $table->addCell(250);
          $table->addCell(250);
        }
      }
    } else {
      $section->addText("No contacts found.");
    }

    // Save the Word document to a temporary file
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($tempFile);

    // Attach the DOCX file to an activity
    $logged_contact_id = CRM_Core_Session::getLoggedInContactID();
    $activity_type = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_Activity',
      'activity_type_id',
      'Print PDF Letter'
    );
    $activityParams = [
      'subject' => 'Download DOCX File',
      'source_contact_id' => $logged_contact_id,
      'activity_type_id' => $activity_type,
      'target_contact_id' => $logged_contact_id,
    ];
    $activity = civicrm_api3('Activity', 'create', $activityParams);

    // Attach the temporary file to the activity as an attachment
    $attachmentParams = [
      'sequential' => 1,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
      'name' => basename($tempFile),
      'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'content' => file_get_contents($tempFile),
    ];
    $attachment = civicrm_api3('Attachment', 'create', $attachmentParams);

    // Provide a link to download the attached DOCX file
    if (!empty($attachment['id'])) {
      $docx_url = $attachment['values'][0]['url'];
      CRM_Utils_System::redirect($docx_url);
    } else {
      // Handle error if attachment creation fails
      CRM_Core_Session::setStatus(E::ts('Failed to create attachment'), E::ts('Error'), 'error');
      $this->controller->setDestination(NULL);
      $this->controller->resetPage($this->getName());
      $this->controller->redirect();
    }

    // Clean up temporary file
    unlink($tempFile);
  }

  private function downloadPDF() {
    $file_name = $this->pdf_name . ".pdf";
    $pdf_contents = CRM_Utils_PDF_Utils::html2pdf($this->html, $file_name, true);

    $logged_contact_id = CRM_Core_Session::getLoggedInContactID();
    $activity_type = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_Activity',
      'activity_type_id',
      'Print PDF Letter'
    );
    /* Creating an activity and attaching the pdf file to that activity. */
    $activity = civicrm_api3('Activity', 'create', [
      'subject' => 'Download PDF File',
      'source_contact_id' => $logged_contact_id,
      'activity_type_id' => $activity_type,
      'target_contact_id' => $logged_contact_id,
    ]);
    $attachment = civicrm_api3('Attachment', 'create', [
      'sequential' => 1,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
      'name' => $file_name,
      'mime_type' => 'application/pdf',
      'content' => $pdf_contents,
    ]);
    $pdf_url = $attachment['values'][0]['url'];

    /* Redirecting the user to download the pdf file. */
    CRM_Utils_System::redirect($pdf_url);
  }
}
