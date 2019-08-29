<?php
/**
 * @file
 * Objects, properties, and methods to communicate with the MYOB REST API
 */

/**
 * Ability to authorize and communicate with the MYOB REST API.
 */
class MYOB {

  public $response;

  /**
   * Constructor which initializes the consumer.
   *
   * @param string $api_key
   *   MYOB key to connect to your MYOB instance.
   * @param string $api_secret
   *   MYOB secret to connect to your MYOB instance.
   */
  public function __construct($api_key, $api_secret = '') {
    $this->api_key = $api_key;
    $this->api_secret = $api_secret;
    $this->login_url = 'https://secure.myob.com';

    // Set Info Businness
    $this->infoBusiness = $this->businessDetail();

    // Set GST Status.
    $this->setGstGeneralStatus();
  }

  /**
   * Determine if this MYOB instance is fully configured.
   *
   * @TODO: Consider making a test API call.
   */
  public function isAuthorized() {
    return !empty($this->api_key) && !empty($this->api_secret) && $this->getRefreshToken();
  }

  /**
   * Make a call to the MYOB REST API.
   *
   * @param string $path
   *   Path to resource.
   * @param array $params
   *   Parameters to provide.
   * @param string $method
   *   Method to initiate the call, such as GET or POST.  Defaults to GET.
   *
   * @return mixed
   *   The requested response.
   *
   * @throws MYOBException
   */
  public function apiCall($path, $params = array(), $method = 'GET', $businesses = true) {
    if (!$this->getAccessToken() || ($this->getAccessTokenExpires() <= time())) {
      $this->refreshToken();
    }

    if ($businesses && !empty($path)) {
      if ($this->getBusinessUid()) {
        $path = '/'.$this->getBusinessUid().$path;
      } else {
        watchdog('vate_myob', 'API call %path failed because Business UID is not set', array('%path' => $path), WATCHDOG_ERROR);
        return false;
      }
    }

    $this->response = $this->apiHttpRequest($path, $params, $method, $businesses);
    switch ($this->response->code) {
      // The session ID or OAuth token used has expired or is invalid.
      case 401:
        // Refresh token.
        $this->refreshToken();

        // Rebuild our request and repeat request.
        $this->response = $this->apiHttpRequest($path, $params, $method,$businesses);
        // Throw an error if we still have bad response.
        if (!in_array($this->response->code, array(200, 201, 204))) {
          if (!empty($this->response->myob_errors)) {
            throw new MYOBException(serialize($this->response->myob_errors), $this->response->code);
          } else {
            throw new MYOBException($this->response->error, $this->response->code);
          }
        }

        break;

      case 200:
      case 201:
      case 204:
        break;

      case 400:
      case 404:
        break;

      default:
        if (!empty($this->response->myob_errors)) {
          throw new MYOBException(serialize($this->response->myob_errors), $this->response->code);
        } else {
          throw new MYOBException($this->response->error, $this->response->code);
        }
    }
    // PUT request on update return empty data
    if (empty($this->response->data)) {
      return true;
    }
    $data = drupal_json_decode($this->response->data);

    // todo: do we need to check if there is any error in the data?
    return $data;
  }

  /**
   * Private helper to issue an MYOB API request.
   *
   * @param string $path
   *   Path to resource.
   * @param array $params
   *   Parameters to provide.
   * @param string $method
   *   Method to initiate the call, such as GET or POST.  Defaults to GET.
   *
   * @return object
   *   The requested data.
   */
  protected function apiHttpRequest($path, $params, $method, $businesses) {
    $url = $this->getApiEndPoint($businesses) . $path;
    $headers = array(
      'Authorization: Bearer '.$this->getAccessToken(),
      'x-myobapi-key: '.$this->api_key,
      'x-myobapi-version: v0',
      'Accept: application/json'
    );
    //return $this->httpRequest($url, $data, $headers, $method);
    // note: does not work with drupal_http_request, do it with curl instead
    $session = curl_init($url);
    if (!empty($params)) {
      $headers[] = 'Content-Type: application/json';
      $data = drupal_json_encode($params);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // todo: does not need this if the site is on https
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($session, CURLINFO_HEADER_OUT, true);
    switch ($method) {
      case 'POST':
        curl_setopt($session, CURLOPT_POST, true);
        break;
      case 'PUT':
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
        break;
    }

    // get the response & close the session
    $response = curl_exec($session);
    $info = curl_getinfo($session);

    $responses = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Time-out',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Large',
      415 => 'Unsupported Media Type',
      416 => 'Requested range not satisfiable',
      417 => 'Expectation Failed',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Time-out',
      505 => 'HTTP Version not supported',
    );

    $result = new stdClass();
    $result->data = $response;
    if (!empty($info)) {
      $result->request = $info['request_header'];
      $result->code = $info['http_code'];
    }

    if ($info['http_code'] != 200 && $info['http_code'] != 201 ) {
      $resp_data = drupal_json_decode($response);
      if (!empty($resp_data['errors'])) {
        $result->myob_errors = $resp_data['errors'];
      }
      if (isset($responses[$info['http_code']])) {
        $result->error = $responses[$info['http_code']];
      } else {
        $result->error = $info['http_code'] . $response;
      }
    }
    curl_close($session);

    if (empty($result->data) && empty($result->code)) {
      // todo: handle error
    }
    return $result;
  }

  /**
   * Make the HTTP request. Wrapper around drupal_http_request().
   *
   * @param string $url
   *   Path to make request from.
   * @param array $data
   *   The request body.
   * @param array $headers
   *   Request headers to send as name => value.
   * @param string $method
   *   Method to initiate the call, such as GET or POST.  Defaults to GET.
   *
   * @return object
   *   MYOB response object.
   */
  protected function httpRequest($url, $data, $headers = array(), $method = 'GET') {

    // If method is GET, data should not be sent otherwise drupal_http_request()
    // will set Content-Length header which confuses some proxies like Squid.
    if ($method === 'GET') {
      $data = NULL;
    }

    // Build the request, including path and headers. Internal use.
    $options = array(
      'method' => $method,
      'headers' => $headers,
      'data' => $data,
    );

    return drupal_http_request($url, $options);
  }

  /**
   * Get the API end point.
   *
   * @param boolean $businesses
   *   Flag to set the end point for businesses
   *
   * @return string
   *   Complete URL endpoint for API access.
   */
  public function getApiEndPoint($businesses) {
    if ($businesses) {
      return 'https://api.myob.com/au/essentials/businesses';
    }
    return 'https://api.myob.com/au/essentials';
  }

  /**
   * Get the access token.
   */
  public function getAccessToken() {
    return isset($_SESSION['myob_access_token']) ? $_SESSION['myob_access_token'] : FALSE;
  }

  /**
   * Set the access token.
   *
   * It is stored in session.
   *
   * @param string $token
   *   Access token from MYOB.
   */
  protected function setAccessToken($token) {
    $_SESSION['myob_access_token'] = $token;
  }

  /**
   * Get the access token expiry timestamp.
   */
  public function getAccessTokenExpires() {
    return variable_get('myob_access_token_expires', '');
  }

  /**
   * Set the access token expiry timestamp.
   *
   * @param int $timestamp
   *   Access token expiry from MYOB.
   */
  protected function setAccessTokenExpires($timestamp) {
    variable_set('myob_access_token_expires', $timestamp);
  }

  /**
   * Get refresh token.
   */
  protected function getRefreshToken() {
    return variable_get('myob_refresh_token', '');
  }

  /**
   * Set refresh token.
   *
   * @param string $token
   *   Refresh token from MYOB.
   */
  protected function setRefreshToken($token) {
    variable_set('myob_refresh_token', $token);
  }

  /**
   * Get myob business uid.
   */
  public function getBusinessUid() {
    return variable_get('myob_business_uid', '');
  }

  /**
   * Refresh access token based on the refresh token. Updates session variable.
   *
   * @throws MYOBException
   * @todo test this
   */
  protected function refreshToken() {
    $refresh_token = $this->getRefreshToken();
    if (empty($refresh_token)) {
      throw new MYOBException(t('There is no refresh token.'));
    }

    $data = drupal_http_build_query(array(
      'grant_type' => 'refresh_token',
      'refresh_token' => $refresh_token,
      'client_id' => $this->api_key,
      'client_secret' => $this->api_secret,
    ));

    $url = $this->login_url . '/oauth2/v1/authorize';
    $headers = array(
      // This is an undocumented requirement on MYOB's end.
      'Content-Type' => 'application/x-www-form-urlencoded',
    );
    $response = $this->httpRequest($url, $data, $headers, 'POST');
    $data = drupal_json_decode($response->data);

    if ($response->code != 200) {
      echo 'error';
      print_r($response);
      print_r($data);
      exit;
      $error = isset($data['error']) ? $data['error'] : $response->error;
      throw new MYOBException($error, $response->code);
      //throw new MYOBException(t('Unable to get a MYOB access token.'), $response->code);
    }

    $this->setRefreshToken($data['refresh_token']);
    $this->setAccessToken($data['access_token']);
    $this->setAccessTokenExpires(time() + $data['expires_in']);
  }

  /**
   * OAuth step 1: Redirect to MYOB and request and access code.
   */
  public function getAccessCode() {
    $url = $this->login_url . '/oauth2/account/authorize';
    $query = array(
      'client_id' => $this->api_key,
      'redirect_uri' => $this->redirectUrl(),
      'response_type' => 'code',
      'scope' => 'la.global'
    );

    drupal_goto($url, array('query' => $query));
  }

  /**
   * OAuth step 2: Exchange an access code for an access token.
   *
   * @param string $code
   *   Code from MYOB.
   */
  public function requestToken($code) {
    $data = drupal_http_build_query(array(
      'client_id' => $this->api_key,
      'client_secret' => $this->api_secret,
      'scope'=> 'la.global',
      'code' => $code,
      'redirect_uri' => $this->redirectUrl(),
      'grant_type' => 'authorization_code'
    ));

    $url = $this->login_url . '/oauth2/v1/authorize';
    $headers = array(
      // This is an undocumented requirement on MYOB's end.
      'Content-Type' => 'application/x-www-form-urlencoded',
    );
    $response = $this->httpRequest($url, $data, $headers, 'POST');
    $data = drupal_json_decode($response->data);

    if ($response->code != 200) {
      $error = isset($data['error']) ? $data['error'] : $response->error;
      throw new MYOBException($error, $response->code);
    }

    // Ensure all required attributes are returned. They can be omitted if the
    // OAUTH scope is inadequate.
    $required = array('refresh_token', 'access_token', 'expires_in');
    foreach ($required as $key) {
      if (!isset($data[$key])) {
        return FALSE;
      }
    }

    $this->setRefreshToken($data['refresh_token']);
    $this->setAccessToken($data['access_token']);
    $this->setAccessTokenExpires(time() + $data['expires_in']);

    return TRUE;
  }

  /**
   * Helper to build the redirect URL for OAUTH workflow.
   *
   * @return string
   *   Redirect URL.
   */
  protected function redirectUrl() {
    return url('myob/oauth_callback', array(
      'absolute' => TRUE,
      'https' => TRUE,
    ));
  }

  /**
   * Return an array of business details.
   *
   * @param $business_id
   *   Business ID.
   *
   * @return array
   *   Array of businesses information.
   */
  private function businessDetail() {
    $business_id = $this->getBusinessUid();
    return $this->apiCall("/businesses/{$business_id}", NULL, 'GET', FALSE);
  }

  /**
   * Set gstRegistered status
   */
  private function setGstGeneralStatus() {
    $this->gstRegistered = FALSE;
    if (isset($this->infoBusiness)) {
      if (isset($this->infoBusiness['gstRegistered'])) {
        $this->gstRegistered = $this->infoBusiness['gstRegistered'];
      }
    }
    else {
      $business = $this->businessDetail();
      if (isset($business['gstRegistered'])) {
        $this->gstRegistered = $business['gstRegistered'];
      }
    }
  }


  /**
   * Return a list of businesses for the configured API version.
   *
   * @return array
   *   Array of businesses array.
   */
  public function listBusinesses() {
    $businesses = $this->apiCall('');
    if (!empty($businesses['items'])) {
      return $businesses['items'];
    }
    return array();
  }

  /**
   * Return a list of inventory items for the configured MYOB business.
   *
   * @return array
   *   Array of businesses array.
   */
  public function listItems($keyword = '') {
    $url = '/inventory/items';
    if ($keyword != '') {
      $url .= '?q='.$keyword;
    }
    $items = $this->apiCall($url,NULL,'GET');
    if (!empty($items['items'])) {
      return $items['items'];
    }
    return array();
  }

  /**
   * Return a list of Invoies
   *
   * @return array
   *   Array of businesses array.
   */
  public function getInvoices($invoiceNumber = NULL) {
    $url = '/sale/invoices';
    if ($invoiceNumber) {
      $url .= '?invoiceNumber=' . $invoiceNumber;
    }
    $items = $this->apiCall($url,NULL,'GET');
    if (!empty($items['items'])) {
      return $items['items'];
    }
    return array();
  }

  /**
   * Get Next Payment Reference.
   *
   * @return string
   *   Payment Reference.
   */
  public function getNextPaymentReference() {
    $url = '/sale/payments/nextReference';
    $items = $this->apiCall($url,NULL,'GET');
    return isset($items['reference']) ? $items['reference'] : NULL;
  }

  /**
   * Get Next invoice Reference.
   *
   * @return string
   *   Payment Reference.
   */
  public function getNextInvoiceReference() {
    $url = '/sale/invoices/nextReference';
    $items = $this->apiCall($url, NULL,'GET');
    return isset($items['reference']) ? $items['reference'] : NULL;
  }

  /**
   * Return a list of inventory items for the configured MYOB business.
   *
   * @return array
   *   Array of businesses array.
   */
  public function listItemsBySku($sku = NULL) {
    if ($sku) {
      $url = "/inventory/items?q={$sku}";
      $items = $this->apiCall($url, NULL, 'GET');
      if (isset($items['items'][0]) && !empty($items['items'][0])) {
        return $items['items'][0];
      }
    }
    return array();
  }

  /**
   * Return a list of accounts for the configured MYOB business.
   *
   * @return array
   *   Array of accounts array.
   */
  public function listAccounts($type = 'all') {
    $path = '/generalledger/accounts?active=true';
    switch ($type) {
      case 'income':
        $path = '/generalledger/accounts?type=9';
        break;

      case 'banking':
        $path = '/generalledger/accounts/?type=1';
        break;

      default:
        # code...
        break;
    }
    $accounts = $this->apiCall($path, NULL, 'GET');

    if (!empty($accounts['items'])) {
      return $accounts['items'];
    }
    return array();
  }

  /**
   * Return a list of tax types for the configured API version.
   *
   * @return array
   *   Array of accounts array.
   */
  public function listTaxTypes() {
    $taxTypes = $this->apiCall('/tax/types',NULL,'GET',false);
    if (!empty($taxTypes['items'])) {
      return $taxTypes['items'];
    }
    return array();
  }

  /**
   * Create a new MYOB entity of the given type.
   *
   * @param string $name
   *   Entity type name, E.g., contact, item, etc.
   * @param array $params
   *   Values of the fields to set for the entity.
   *
   * @return mix
   *   Boolean false or entity array
   */
  public function createEntity($name, $params) {
    switch ($name) {
      case 'contact': $path = '/contacts'; break;
      case 'item': $path = '/inventory/items'; break;
      case 'invoice': $path = '/sale/invoices'; break;
      case 'payment': $path = '/sale/payments'; break;
      default: $path = ''; break;
    }
    if (!empty($path)) {
      $result = $this->apiCall($path, $params, 'POST');

      // Save Errors.
      if (isset($result['errors'])) {
        $errors = $result['errors'];
        watchdog('vate_myob', 'MYOB Entity Create / %name: <pre>%errors</pre>  ------  <pre>%data</pre>', array(
          '%name' => $name,
          '%errors' => print_r( $errors, TRUE),
          '%data' => print_r( $params, TRUE),
        ),
        WATCHDOG_ERROR);
      }

      if (!empty($result['uid'])) {
        return $result;
      }
    }
    return false;
  }

  /**
   * Update an existing MYOB entity.
   *
   * @param string $name
   *   Entity type name, E.g., contact, item, etc.
   * @param string $id
   *   MYOB id of the entity.
   * @param array $params
   *   Values of the fields to set for the entity.
   *
   * @return boolean
   *   Boolean success or failed
   */
  public function updateEntity($name, $id, $params) {
    switch ($name) {
      case 'contact': $path = '/contacts'; break;
      case 'item': $path = '/inventory/items'; break;
      case 'invoice': $path = '/sale/invoices'; break;
      default: $path = ''; break;
    }
    if (!empty($path)) {
      $params['uid'] = $id;
      $result = $this->apiCall("{$path}/{$id}", $params, 'PUT');
      return $result;
    }
    return false;
  }
}

class MYOBException extends Exception {
}
