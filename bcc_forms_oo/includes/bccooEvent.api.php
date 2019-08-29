<?php

/**
 * undocumented class
 *
 * @package default
 * @author bliss_media
 **/
class bccooEvent {

  public $WSR_Source_ENUM = 'Internet';
  public $Event_REFID = 0;
  public $Type_REFID = 0;
  public $Source_REFID = 0;
  public $RequestorType_REFID = 0;
  public $Requestor_Classification = 0;
  public $Requestor_PreferredContactMethod = 0;
  public $OnBehalfOf_Classification = 0;
  public $OnBehalfOf_PreferredContactMethod_REFID = 0;

  public $Urgent = FALSE;
  public $ReturnCall = FALSE;
  public $Custom_Flag1 = FALSE;
  public $Custom_Flag2 = FALSE;
  public $Custom_Flag3 = FALSE;
  public $Flag_LodgedByAfterHoursService = FALSE;


  public $ReceivedBy_UserID = 0;
  public $RecordedBy_UserID = 0;

  public $Category = '';
  public $SubCategory = '';

  public $Requestor_Anonymous = FALSE;
  public $Requestor_Title = '';
  public $Requestor_FirstName = '';
  public $Requestor_LastName = '';
  public $Requestor_PropertyAssessmentNo = NULL;

  public $Requestor_UnitNo = '';
  public $Requestor_StreetNo = '';
  public $Requestor_StreetNamePart_Name = '';
  public $Requestor_StreetNamePart_Type = '';
  public $Requestor_Suburb = '';
  public $Requestor_Postcode = '';

  public $Requestor_HomePhone = '';
  public $Requestor_WorkPhone = '';
  public $Requestor_Mobile = '';
  public $Requestor_Email = '';

  public $LocationType_REFID = LOCATIONTYPE_REFID_SAME_AS_REQUESTOR;
  public $Location_PropertyAssessmentNo = NULL;
  public $Location_StreetNo = '';
  public $Location_StreetNamePart_Name = '';
  public $Location_Suburb = '';
  public $Location_Postcode = '';

  public $Event_Description = '';

  public $OnBehalfOf_FirstName = '';
  public $OnBehalfOf_LastName = '';
  public $OnBehalfOf_PropertyAssessmentNo = '';

  public $OnBehalfOf_UnitNo = '';
  public $OnBehalfOf_StreetNo = '';
  public $OnBehalfOf_StreetNamePart_Name = '';
  public $OnBehalfOf_StreetNamePart_Type = '';
  public $OnBehalfOf_Suburb = '';
  public $OnBehalfOf_Postcode = '';

  public $OnBehalfOf_HomePhone = '';
  public $OnBehalfOf_WorkPhone = '';
  public $OnBehalfOf_Mobile = '';
  public $OnBehalfOf_Email = '';

  public $HardRubbish_PickupLocation = '';

  public $Attachments = array();
  public $AdditionalReferenceNumber = '';

  /**
   * { function_description }
   *
   * @param      <type>  $data
   *   The data
   * @param      <type>  $bccoo
   *   The bccoo
   */
  function __construct($data, $bccoo) {

    // Fields to Exclude from the automatic process.
    $exl_auto = array(
      'Requestor_Anonymous',
      '_addToDescription',
      '_attachments',
      'Event_Description',
    );


    $desc_compile = array();
    $custom_cat = $custom_subcat = FALSE;

    foreach ($data as $value) {
      $cid = $value['cid'];
      if (!isset($value['oo_name'])) {
        $value['oo_name'] = '_none';
      }
      // Check if this field is not in exclude array.
      // Regular Fields.
      if (!in_array($value['oo_name'], $exl_auto)) {
        $this->{$value['oo_name']} = $value['value'];

        // Validate and set *_StreetNamePart_Name* based on *_PropertyAssessmentNo*
        $pos = strpos($value['oo_name'], "_PropertyAssessmentNo");
        if ($pos !== FALSE && isset($value[0])) {
          $fp = substr($value['oo_name'], 0, $pos);
          $this->{"{$fp}_StreetNamePart_Name"} = $value[0];
        }
      }

      // Requestor_Anonymous
      if ($value['oo_name'] == 'Requestor_Anonymous' && ($value['value'] == 'Yes' || $value['value'] == '1')) {
        $this->{$value['oo_name']} = TRUE;
      }

      // Only for Event_Description;
      if ($value['oo_name'] == "Event_Description") {
        $title_field = $value['_webform']['name'];
        $data_field = $value['value'];
        $desc_compile[$cid] = "{$title_field}: {$data_field}";
      }

      // Process Description.
      if ($value['oo_name'] == '_addToDescription' && !empty($value['value'])) {
        $title_field = $value['_webform']['name'];
        $title_field = str_replace(":", "", $title_field);
        $data_field = $value['value'];
        if (!empty($value['_webform']['extra']['multiple']) && !empty($value['_webform']['extra']['items'])) {
          // field with multiple values, replace comma with new line
          $options = preg_split('/\r\n|\r|\n/', $value['_webform']['extra']['items']);
          foreach ($options as $option) {
            $opt = explode('|', $option);
            if (!empty($opt[0])) {
              $search = $opt[0].', ';
              $replace = $opt[0].' and ';
              $data_field = str_replace($search, $replace, $data_field);
            }
          }

          // custom condition for hard waste
          if ($value['_webform']['form_key'] == 'hardrubbish_items') {
            $data_field = str_replace(array('243830','243832','260557','260562'), array('Mattress','Bundled Branches/Christmas Trees (in January)','General','e waste'), $data_field);
          }
        }
        $desc_compile[$cid] = "{$title_field}: {$data_field}";
      }
      // Add fields to Event Description like Secondary option.
      elseif (isset($value['data']['desc']) && $value['data']['desc'] && $value['oo_name'] != '_addToDescription') {
        $title_field = $value['_webform']['name'];
        $data_field = $value['value'];

        // field with single value from availble options
        if (!empty($value['_webform']['extra']['items']) && empty($value['_webform']['extra']['multiple'])) {
          $options = preg_split('/\r\n|\r|\n/', $value['_webform']['extra']['items']);
          foreach ($options as $option) {
            $opt = explode('|', $option);
            if (isset($opt[0]) && !empty($opt[1])) {
              $key = $opt[0];
              if ($key === '0') {
                $key = ''; // when the key is '0' the value is '' instead of '0'
              }
              if ($key === $value['value']) {
                $data_field = $opt[1];
              }
            }
          }
        }

        if (!empty($data_field)) {
          $title_field = str_replace(":", "", $title_field);
          $desc_compile[$cid] = "{$title_field}: {$data_field}";
        }
      }

      // Process Attachments.
      if ($value['oo_name'] == '_attachments') {
        try {
          if ($file = file_load($value['value'])) {
            $filePath = drupal_realpath($file->uri);
            $handle = fopen($filePath, "r");
            $contents = fread($handle, filesize($filePath));
            fclose($handle);
            $attach  = new stdClass;
            $attach->FileName = $file->filename;
            $attach->Bytes = NULL;
            $attach->Base64String = base64_encode($contents);
            $this->Attachments[] = $attach;
          }
        } catch (Exception $e) {
          // @watchdog
          watchdog('bccooEvent', "Load File: %msg", array('%msg' => $e->getMessage()), WATCHDOG_ERROR);
        }
      }

      // Custom Category And Subcategory.
      if ($value['_webform']['form_key'] == 'rex_cat' && !empty($value[0])) {
        $custom_cat = $value[0];
      }
      if ($value['_webform']['form_key'] == 'rex_subcat' && !empty($value[0])) {
        $custom_subcat = $value[0];
      }

    }

    // Set description.
    $prefix = '';
    if (strlen($this->Event_Description) > 0) {
      $prefix = ".\n";
    }
    if (!empty($desc_compile)) {
      $text_desc = implode(".\n", $desc_compile);
      $this->Event_Description .= "{$prefix}{$text_desc}";
    }

    // Set category.
    $this->Category = $bccoo->getCatName();
    $this->SubCategory = $bccoo->getSubCatName();

    // Custom Categories.
    // It depends of the current webform and the correct setup of
    // rex_cat and rex_subcat
    //
    // Those options might be override global variables previously added.
    if ($custom_cat) {
      $this->Category = $custom_cat;
    }
    if ($custom_subcat) {
      $this->SubCategory = $custom_subcat;
    }

    // VALIDATE ADDRESS.
    // Validation of locations & Variables.
    if (!$this->Requestor_PropertyAssessmentNo) {
      $this->LocationType_REFID = LOCATIONTYPE_REFID_NO_LOCATION;
    }

    if ($this->Requestor_PropertyAssessmentNo && !$this->Location_PropertyAssessmentNo) {
      $this->Location_PropertyAssessmentNo = $this->Requestor_PropertyAssessmentNo;
      $this->Location_StreetNamePart_Name = $this->Requestor_StreetNamePart_Name;
      $this->LocationType_REFID = LOCATIONTYPE_REFID_SAME_AS_REQUESTOR;
    }

    if ($this->Location_PropertyAssessmentNo && !$this->Requestor_PropertyAssessmentNo) {
      $this->Requestor_PropertyAssessmentNo = '';
      $this->LocationType_REFID = LOCATIONTYPE_REFID_ADDRESS;
    }

    if (!$this->Requestor_PropertyAssessmentNo && !$this->Location_PropertyAssessmentNo) {
      $this->Location_PropertyAssessmentNo = $this->Requestor_PropertyAssessmentNo = '';
      $this->LocationType_REFID = LOCATIONTYPE_REFID_NO_LOCATION;
    }

    // Proccess Web Service.
    $this->processedWS($bccoo);
  }

  /**
   * { function_description }
   */
  private function processedWS($oo) {
    try {

      $client = self::soapClient();

      if ($client) {
        // Create Request Object.
        $SubmitRequest = new stdClass;
        $SubmitRequest->SecurityToken = BAYSIDE_CRMS_TOKEN;
        $SubmitRequest->oWSEvent = $this;

        $response = $client->SubmitRequest($SubmitRequest);
        $this->response = $response;

        $SubmitRequestResult = isset($response->SubmitRequestResult) ? $response->SubmitRequestResult : FALSE;
        if (is_object($SubmitRequestResult)) {
          $to_save = array(
            'SubmitRequest' => $SubmitRequest,
            'response' => $response,
          );
          $message_wd = '<pre>' . print_r( $to_save, TRUE) . '</pre>';
          if ($SubmitRequestResult->Success) {
            $this->rex_reference = $SubmitRequestResult->EventReference;
            // @watchbog
            watchdog("Rex WebF", "Success node/{$oo->nid}, {$message_wd}", array(), WATCHDOG_NOTICE);
          } else {
            // todo: hide the api error message from the user and just show generic error message?
            if (isset($SubmitRequestResult->ErrorCodes->string)) {
              $errMsg = "Error No ".$SubmitRequestResult->ErrorCodes->string;
            }
            if (isset($SubmitRequestResult->Errors->string)) {
              if (isset($errMsg)) {
                $errMsg .= ': ';
              }
              $errMsg .= $SubmitRequestResult->Errors->string;
            }
            if (!isset($errMsg)) {
              $errMsg = 'Unknown error';
            }

            // @watchbog
            watchdog("Rex WebF", "Failure node/{$oo->nid}, {$errMsg}, {$message_wd}", array(), WATCHDOG_WARNING);

          }
        }
      }
    } catch (Exception $e) {
      // @watchdog
      watchdog('bccooEvent', "Soap Request: %msg", array('%msg' => $e->getMessage()), WATCHDOG_ERROR);
    }

    // Delete Session Variable.
    unset($_SESSION['rex_locids']);

  }

  /**
   * Implements function SoapClient
   *
   * @return (object) SoapClient
   *   Object ready to use.
   */
  public function soapClient() {
    try {
      return new SoapClient(
        BAYSIDE_CRMS_URL . "?WSDL",
        array(
          'location' => BAYSIDE_CRMS_URL,
          'soap_version' => SOAP_1_2,
          'trace' => 1,
          'exceptions' => true
        )
      );
    } catch (Exception $e) {
      watchdog('bccooEvent', "Load Soap: %msg", array('%msg' => $e->getMessage()), WATCHDOG_ERROR);
    }
  }
}
