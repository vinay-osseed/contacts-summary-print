<?php

use CRM_ContactsSummaryPrint_ExtensionUtil as E;

class CRM_ContactsSummaryPrint_Form_Task_PrintSummary extends CRM_Contact_Form_Task {
  public $pdf_name;
  public $html;

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
    $params = $this->exportValues();
    $file_name = $this->pdf_name . ".docx";

    $option = [
      "qfKey" => $params['qfKey'],
      "qfKey" => $params['entryURL'],
      "paper_size" => "letter",
      "orientation" => "portrait",
      "metric" => "in",
      "margin_left" => "0.75",
      "margin_right" => "0.75",
      "margin_top" => "0.75",
      "margin_bottom" => "0.75",
      "document_type" => "docx",
      "MAX_FILE_SIZE" => "2097152",
    ];

    $logged_contact_id = CRM_Core_Session::getLoggedInContactID();
    $activity_type = CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_Activity',
      'activity_type_id',
      'Print PDF Letter'
    );
    /* Creating an activity and attaching the pdf file to that activity. */
    $activity = civicrm_api3('Activity', 'create', [
      'subject' => 'Download DOCX File',
      'source_contact_id' => $logged_contact_id,
      'activity_type_id' => $activity_type,
      'target_contact_id' => $logged_contact_id,
    ]);

    $tee = CRM_Utils_ConsoleTee::create()->start();

    $docx_contents = CRM_Utils_PDF_Document::html2doc($this->html, $file_name, $option);

    if ($tee) {
      $tee->stop();
      $content = file_get_contents($tee->getFileName(), FALSE, NULL, 0, 5);
      if (empty($content)) {
        throw new \CRM_Core_Exception("Failed to capture document content (type=docx)!");
      }
      $attachment = civicrm_api3('Attachment', 'create', [
        'entity_table' => 'civicrm_activity',
        'entity_id' => $activity['id'],
        'name' => $file_name,
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'options' => [
          'move-file' => $tee->getFileName(),
        ],
      ]);
      d(json_encode($attachment));
      // dd(json_encode($attachment));
      $docx_url = $attachment['values'][0]['url'];
    }

    // $attachment = civicrm_api3('Attachment', 'create', [
    //   'sequential' => 1,
    //   'entity_table' => 'civicrm_activity',
    //   'entity_id' => $activity['id'],
    //   'name' => $file_name,
    //   'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    //   // 'content' => $docx_contents,
    //   'options' => [
    //     'move-file' => $file_name,
    //   ],
    // ]);
    // $docx_url = $attachment['values'][0]['url'];

    /* Redirecting the user to download the docx file. */
    // CRM_Utils_System::redirect($docx_url);
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
