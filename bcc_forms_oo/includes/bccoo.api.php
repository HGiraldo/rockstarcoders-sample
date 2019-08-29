<?php

/**
 * @file
 * Main class to manage OpenOffice object.
 */

include_once "bccooEvent.api.php";

/**
* Main Class Bayside City Council Open Office.
*/
class bccoo extends bccooEvent {

  /**
   * { function_description }
   *
   * @param      <type>  $nid
   *   The nid
   */
  function __construct($nid) {
    // Set Common Info.
    $this->table = "bcc_forms_oo";
    $this->table_comp = "bcc_forms_oo_components";

    // Validate Node.
    if ($nid && $node = node_load($nid)) {
      if ($node->type == 'webform') {
        $this->node_wrapper = entity_metadata_wrapper('node', $node);
        $this->nid = $this->node_wrapper->getIdentifier();
        $this->components = $this->node_wrapper->value()->webform['components'];
      }
    }

    // Reload Status Integration.
    self::statusIntegration();

    // Reload  Cateories.
    self::realoadCategories();
  }

  /**
   * { function_description }
   *
   * @return     <type>
   *   { description_of_the_return_value }
   */
  public function optionsMatch() {
    return array(
      '_none' => '- None -',
      '_addToDescription' => '* Attach to general description Text',
      '_attachments' => '* Document to attach',
      'Requestor_Anonymous' => 'Requestor_Anonymous',
      'Requestor_Title' => 'Requestor_Title',
      'Requestor_FirstName' => 'Requestor FirstName',
      'Requestor_LastName' => 'Requestor_LastName',
      'Requestor_PropertyAssessmentNo' => 'Requestor_PropertyAssessmentNo',
      'Requestor_UnitNo' => 'Requestor_UnitNo',
      'Requestor_StreetNo' => 'Requestor_StreetNo',
      'Requestor_StreetNamePart_Name' => 'Requestor_StreetNamePart_Name',
      'Requestor_StreetNamePart_Type' => 'Requestor_StreetNamePart_Type',
      'Requestor_Suburb' => 'Requestor_Suburb',
      'Requestor_Postcode' => 'Requestor_Postcode',
      'Requestor_HomePhone' => 'Requestor_HomePhone',
      'Requestor_WorkPhone' => 'Requestor_WorkPhone',
      'Requestor_Mobile' => 'Requestor_Mobile',
      'Requestor_Email' => 'Requestor_Email',
      'LocationType_REFID' => 'LocationType_REFID',
      'Location_PropertyAssessmentNo' => 'Location_PropertyAssessmentNo',
      'Event_Description' => 'Event_Description',
      'OnBehalfOf_FirstName' => 'OnBehalfOf_FirstName',
      'OnBehalfOf_LastName' => 'OnBehalfOf_LastName',
      'OnBehalfOf_PropertyAssessmentNo' => 'OnBehalfOf_PropertyAssessmentNo',
      'OnBehalfOf_UnitNo' => 'OnBehalfOf_UnitNo',
      'OnBehalfOf_StreetNo' => 'OnBehalfOf_StreetNo',
      'OnBehalfOf_StreetNamePart_Name' => 'OnBehalfOf_StreetNamePart_Name',
      'OnBehalfOf_StreetNamePart_Type' => 'OnBehalfOf_StreetNamePart_Type',
      'OnBehalfOf_Suburb' => 'OnBehalfOf_Suburb',
      'OnBehalfOf_Postcode' => 'OnBehalfOf_Postcode',
      'OnBehalfOf_HomePhone' => 'OnBehalfOf_HomePhone',
      'OnBehalfOf_WorkPhone' => 'OnBehalfOf_WorkPhone',
      'OnBehalfOf_Mobile' => 'OnBehalfOf_Mobile',
      'OnBehalfOf_Email' => 'OnBehalfOf_Email',
      'HardRubbish_PickupLocation' => 'HardRubbish_PickupLocation',
      'AdditionalReferenceNumber' => 'AdditionalReferenceNumber',
    );
  }



  // ************************************************************
  // GENERAL CONFIG.
  // ************************************************************

  /**
   * Sets the cat form.
   *
   * @param      string  $cat
   *   The cat
   */
  public function setCatForm($cat = '') {
    if ($row = $this->checkRow()) {
      $data = $row['data'];
      $data['cat'] = $cat;
      db_update($this->table)
      ->fields(array(
          'data' => serialize($data)
        )
      )
      ->condition('nid', $this->nid)
      ->execute();
      // Self call to Reload Cats.
      self::realoadCategories();
    }
  }

  /**
   * Sets the subcat form.
   *
   * @param      string  $subcat
   *   The subcat
   */
  public function setSubcatForm($subcat = '') {
    if ($row = $this->checkRow()) {
      $data = $row['data'];
      $data['subcat'] = $subcat;
      db_update($this->table)
      ->fields(array(
          'data' => serialize($data)
        )
      )
      ->condition('nid', $this->nid)
      ->execute();
      // Self call to Reload Cats.
      self::realoadCategories();
    }
  }

  /**
   * Sets the cat alternatives.
   *
   * @param      string  $text
   *   The text
   */
  public function setCatAlternatives($text = '') {
    if ($row = $this->checkRow()) {
      $data = $row['data'];
      $data['alternative'] = $text;
      db_update($this->table)
      ->fields(array(
          'data' => serialize($data)
        )
      )
      ->condition('nid', $this->nid)
      ->execute();
      // Self call to Reload Cats.
      self::realoadCategories();
    }
  }

  public function setCustomValidation($val = NULL) {
    if ($row = $this->checkRow()) {
      $data = $row['data'];

      if ($val == '_none') {
        $val = FALSE;
      }

      $data['validation'] = $val;
      db_update($this->table)
      ->fields(array(
          'data' => serialize($data)
        )
      )
      ->condition('nid', $this->nid)
      ->execute();
      // Self call to Reload Cats.
      self::realoadCategories();
    }
  }

  /**
   * { function_description }
   */
  private function realoadCategories() {
    $this->oo_cat = '';
    $this->oo_subcat = '';
    $this->oo_catsAlt = '';
    $this->oo_custom_val = NULL;
    if ($row = $this->checkRow()) {
      $data = $row['data'];
      $this->oo_cat = isset($data['cat']) ? $data['cat'] : '';
      $this->oo_subcat = isset($data['subcat']) ? $data['subcat'] : '';
      $this->oo_catsAlt = isset($data['alternative']) ? $data['alternative'] : '';
      $this->oo_custom_val = isset($data['validation']) ? $data['validation'] : '';
    }
  }

  /**
   * Sets the status form.
   *
   * @param      <type>  $state
   *   The state
   */
  public function setStatusForm($state = TRUE) {

    if ($state != TRUE) {
      $state = FALSE;
    }

    // Set New Status.
    $this->updateRow($state);

    // Reload Status Integration.
    self::statusIntegration();
  }

  public function getRexCid() {
    $cid = FALSE;
    // Check row on DB.
    if ($this->nid) {
      $cid = db_select('webform_component', 'comp')
             ->fields('comp', array('cid'))
             ->condition('nid', $this->nid)
             ->condition('form_key', 'rex_reference')
             ->execute()
             ->fetchField();
    }
    return $cid;
  }

  /**
   * { function_description }
   *
   * @param      <type>  $state
   *   The state
   */
  private function updateRow($state) {
    if ($this->nid) {
      // Create if no ahve results.
      $query = $this->checkRow();
      if (!$query || empty($query)) {
        $this->createRowState($state);
      }
      // Update.
      else {
        $this->updateRowState($state);
      }
    }
  }

  /**
   * { function_description }
   *
   * @return     <type>
   *   { description_of_the_return_value }
   */
  private function checkRow() {
    if ($this->nid) {
      // Check row on DB.
      $query = db_select($this->table, 'tb')
             ->fields('tb')
             ->condition('nid', $this->nid)
             ->execute()
             ->fetchAssoc();

      if (isset($query['data'])) {
        $query['data'] = unserialize($query['data']);
      }
    }
    return isset($query) ? $query : FALSE;
  }

  /**
   * { function_description }
   *
   * @param      <type>  $state
   *   The state
   */
  private function createRowState($state) {
    if (!$this->checkRow() && $this->nid) {
      $record = array(
        'nid' => $this->nid,
        'status' => $state,
        'created' => time(),
        'data' => array()
      );
      drupal_write_record($this->table, $record);
    }
  }

  /**
   * { function_description }
   *
   * @param      <type>  $state
   *   The state
   */
  private function updateRowState($state) {
    if ($this->checkRow()) {
      db_update($this->table)
      ->fields(array(
          'status' => $state
        )
      )
      ->condition('nid', $this->nid)
      ->execute();
    }
  }

  /**
   * { function_description }
   */
  private function statusIntegration() {
    $this->oo_status = FALSE;
    if ($q = $this->checkRow()) {
      $this->oo_status = FALSE;
      if ($q['status'] != FALSE) {
        $this->oo_status = TRUE;
      }
    }
  }

  /**
   * Gets the cetegories.
   *
   * @return     <type>
   *   The cetegories.
   */
  public function getCategories() {
    $list = array();

    // Cache Get.
    $cache = cache_get('bccRexCategories');

    if (!isset($cache->data) || empty($cache->data)) {
      $client = parent::soapClient();

      if ($client) {
        $request = new stdClass;
        $request->SecurityToken = BAYSIDE_CRMS_TOKEN;
        $response = $client->GetCategoryList($request);

        if (isset($response->GetCategoryListResult->ArrayOfString)) {
          foreach ($response->GetCategoryListResult->ArrayOfString as $key => $cat) {
            $cat = reset($cat);
            $list["cat:" . $cat[0]] = $cat[1];
          }
          // Cache Set.
          cache_set('bccRexCategories', $list, 'cache', CACHE_TEMPORARY);
        }
      }
    }
    else {
      $list = $cache->data;
    }

    return $list;
  }

  /**
   * Gets the category.
   *
   * @return     <type>
   *   The category.
   */
  public function getCatName() {
    $return = FALSE;

    $catg = self::getCategories();
    if (isset($catg[$this->oo_cat])) {
      $return = $catg[$this->oo_cat];
    }

    if ($this->oo_catsAlt && !empty($this->oo_catsAlt)) {
      $cat = $scat = NULL;
      list($cat, $scat) = explode("|", $this->oo_catsAlt);
      if ($cat && !empty($cat)) {
        $return = filter_xss_admin(trim($cat));
      }
    }

    return $return;
  }

  public function getSubCatName() {
    $return = FALSE;

    $catg = self::getSubCategories($this->oo_cat);
    if (isset($catg[$this->oo_subcat])) {
      $return = $catg[$this->oo_subcat];
    }

    if ($this->oo_catsAlt && !empty($this->oo_catsAlt)) {
      $cat = $scat = NULL;
      list($cat, $scat) = explode("|", $this->oo_catsAlt);
      if ($scat && !empty($scat)) {
        $return = filter_xss_admin(trim($scat));
      }
    }

    return $return;
  }

  /**
   * Gets the sub cetegories.
   *
   * @param      <type>  $id_cat
   *   The identifier cat
   *
   * @return     <type>
   *   The sub cetegories.
   */
  public function getSubCategories($id_cat = NULL) {
    $list = array();

    list($text, $id_cat) = explode(":", $id_cat);

    if ($id_cat && is_numeric($id_cat)) {

      // Cache Get.
      $cache = cache_get("bccRexSubCategory_{$id_cat}");

      if (!isset($cache->data) || empty($cache->data)) {
        $client = parent::soapClient();

        if ($client) {
          $request = new stdClass;
          $request->SecurityToken = BAYSIDE_CRMS_TOKEN;
          $request->Category_ID = $id_cat;
          $response = $client->GetSubCategoryList($request);

          if (isset($response->GetSubCategoryListResult->ArrayOfString)) {
            foreach ($response->GetSubCategoryListResult->ArrayOfString as $key => $cat) {
              $cat = reset($cat);
              $list["sub:" . $cat[0]] = $cat[1];
            }

            // Cache Set.
            cache_set("bccRexSubCategory_{$id_cat}", $list, 'cache', CACHE_TEMPORARY);
          }
        }
      }
      else {
        $list = $cache->data;
      }
    }

    return $list;
  }

  /**
   * Gets the street types.
   */
  public function getStreetTypes() {

    $list = array();

    // Cache Get.
    $cache = cache_get('bccRexStreetTypes');

    if (isset($cache->data)) {
      $list = $cache->data;
    }
    else {
      $client = parent::soapClient();

      if ($client) {
        $GetStreetTypes = new stdClass;
        $GetStreetTypes->SecurityToken = BAYSIDE_CRMS_TOKEN;
        $response = $client->GetStreetTypes($GetStreetTypes);
        if (isset($response->GetStreetTypesResult->string)) {
          $streetTypes = $response->GetStreetTypesResult->string;
          array_unshift($streetTypes, '');
          $list = drupal_map_assoc($streetTypes);
          // Cache Set.
          cache_set('bccRexStreetTypes', $list, 'cache', CACHE_TEMPORARY);
        }
      }
    }


    return $list;
  }

  /**
   * Gets the title types.
   *
   * @return     <type>
   *   The title types.
   */
  public function getTitleTypes() {
    return array(
      'Dr' => 'Dr',
      'Miss' => 'Miss',
      'Mr' => 'Mr',
      'Mrs' => 'Mrs',
      'Ms' => 'Ms',
    );
  }

  // ************************************************************
  // WEBFORM COMPONENTS.
  // ************************************************************

  public function loadComp($cid = NULL) {
    if ($cid && $this->nid) {
      // Check row on DB.
      $query = db_select($this->table_comp, 'tb')
             ->fields('tb')
             ->condition('nid', $this->nid)
             ->condition('cid', $cid)
             ->execute()
             ->fetchAssoc();
      if ($query['data']) {
        $query['data'] = unserialize($query['data']);
      }

      // Webform Info.
      if (isset($this->components[$cid])) {
        $query['_webform'] = $this->components[$cid];
      }
    }
    return isset($query) ? $query : FALSE;
  }

  /**
   * Sets up component.
   *
   * @param      array  $comp
   *   The component
   *
   * @return     <type>
   *   { description_of_the_return_value }
   */
  public function setUpComp($comp = array()) {
    $answ = FALSE;
    // Validate before Update or Create.
    if (!empty($comp) && isset($comp['nid']) && isset($comp['cid']) && isset($comp['oo_name'])) {
      if ($this->checkComp($comp)) {
        // UPDATE.
        $answ = $this->updateComp($comp);
      }
      else {
        // CREATE.
        $answ = $this->createComp($comp);
      }
    }
    return $answ;
  }

  /**
   * { function_description }
   *
   * @param      array  $comp
   *   The component
   *
   * @return     <type>
   *   { description_of_the_return_value }
   */
  private function checkComp($comp = array()) {
    if (!empty($comp) && isset($comp['nid']) && isset($comp['cid']) && isset($comp['oo_name'])) {
      // Check row on DB.
      $query = db_select($this->table_comp, 'tb')
             ->fields('tb')
             ->condition('nid', $comp['nid'])
             ->condition('cid', $comp['cid'])
             ->execute()
             ->fetchAssoc();
    }
    return isset($query) ? $query : FALSE;
  }

  /**
   * { function_description }
   *
   * @param      array  $comp
   *   The component
   */
  private function updateComp($comp = array()) {
    $return = FALSE;

    if ($this->checkComp($comp)) {

      // Save DESC Option for Components.
      if (isset($comp['oo_desc'])) {
        $comp['data']['desc'] = $comp['oo_desc'];
      }

      // Save ExtraVal Option for Components.
      if (isset($comp['oo_extval'])) {
        $comp['data']['val'] = $comp['oo_extval'];
      }

      db_update($this->table_comp)
      ->fields(array(
          'oo_name' => $comp['oo_name'],
          'data' => isset($comp['data']) ? serialize($comp['data']) : serialize(array()),
        )
      )
      ->condition('nid', $comp['nid'])
      ->condition('cid', $comp['cid'])
      ->execute();

      if ($updated) {
        $return = TRUE;
      }
    }

    return $return;
  }

  /**
   * Creates a component.
   *
   * @param      array  $comp
   *   The component
   */
  private function createComp($comp = array()) {
    $return = FALSE;

    if (!$this->checkComp() && isset($comp['nid']) && isset($comp['cid']) && isset($comp['oo_name'])) {

      // Save DESC Option for Components.
      if (isset($comp['oo_desc'])) {
        $comp['data']['desc'] = $comp['oo_desc'];
      }

      // Save ExtraVal Option for Components.
      if (isset($comp['oo_extval'])) {
        $comp['data']['val'] = $comp['oo_extval'];
      }

      $record = array(
        'nid' => $comp['nid'],
        'cid' => $comp['cid'],
        'oo_name' => $comp['oo_name'],
        'data' => isset($comp['data']) ? $comp['data'] : array()
      );

      drupal_write_record($this->table_comp, $record);

      if ($record->id) {
        $return = TRUE;
      }
    }

    return $return;
  }

  /**
   * Sends a sub.
   *
   * @param      <type>  $sid
   *   The sid
   *
   * @return     bccooEvent
   *   { description_of_the_return_value }
   */
  public function sendSub($data) {
    module_load_include('inc','webform','includes/webform.submissions');
    if ($this->nid && !empty($data)) {
      if (isset($data)) {
        foreach ($data as $key => &$data_sub) {

          // GET ALL DELTAS.
          $value = array();
          foreach ($data_sub as $key_val => $val) {
            if (is_numeric($key_val) && !empty($data_sub[$key_val])) {
              $value[] = $data_sub[$key_val];
            }
          }

          $data_sub['value'] = implode(", ", $value);
          $data_sub += self::loadComp($key);

          // Check Locations
          if (isset($data_sub['oo_name'])) {
            if (substr($data_sub['oo_name'], -21) == '_PropertyAssessmentNo' && isset($data_sub[0])) {
              if (isset($_SESSION['rex_locids'][$data_sub['cid']])) {
                $data_sub['value'] = filter_xss($_SESSION['rex_locids'][$data_sub['cid']]);
                // $matches = array();
                // preg_match('/\(([^)]+)\)/', $data_sub[0], $matches);
                // if (isset($matches[1]) && !empty($matches[1])) {
                //   $data_sub['value'] = filter_xss(trim($matches[1]));
                // }
              }
            }
          }
        }

        return new bccooEvent($data, $this);
      }
    }
  }


  /**
   * Implements function to check REX availability.
   *
   * @return     bool
   */
  public function serviceExternalStatus() {
    return parent::soapClient();
  }
}
