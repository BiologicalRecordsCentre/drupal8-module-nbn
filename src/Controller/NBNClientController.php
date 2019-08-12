<?php

namespace Drupal\nbn\Controller;
use Drupal\Core\Controller\ControllerBase;

Class NBNClientController extends ControllerBase {
  private $token;

  function CurlGetString($url)
  {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $json = curl_exec($ch);
      $response = $this->CurlDecodeResponse($json, $ch);
      curl_close($ch);
      return $response;
  }

  function CurlPostData($url, $postData)
  {
      $fields_string = "";
      foreach($postData as $key=>$value)
      {
          $fields_string .= $key . "=" . $value . "&";
      }
      $fields_string = rtrim($fields_string, "&");

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_COOKIE, "nbn.token_key=".$this->token);

      curl_setopt($ch, CURLOPT_POST, count($postData));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

      curl_setopt($ch, CURLOPT_TIMEOUT, 120);
      $json = curl_exec($ch);
      $response = $this->CurlDecodeResponse($json, $ch);
      curl_close($ch);
      return $response;
  }

  function CurlDecodeResponse($json, $ch)
  {
    if ($json === FALSE) {
      // Check for error in response from curl_exec.
      $response = array();
      $response['error'] = curl_error($ch);
      $response['error'] = str_replace(array("\r\n", "\n", "\r"), '<br />', $response['error']);
    }
    elseif ($json === '') {
      // Check for no results returned.
      $response = array();
      $response['error'] = 'The NBN Atlas responded with no data. Ensure the settings are correct.';
    }
    else {
      // Decode response.
      $response = json_decode($json, TRUE);

      // Check for error during decoding.
      if (!isset($response)) {
        $response = array();
        switch (json_last_error()) {
          case JSON_ERROR_DEPTH:
            $response['error'] = 'Maximum stack depth exceeded';
            break;
          case JSON_ERROR_STATE_MISMATCH:
            $response['error'] = 'Underflow or the modes mismatch';
            break;
          case JSON_ERROR_CTRL_CHAR:
            $response['error'] = 'Unexpected control character found';
            break;
          case JSON_ERROR_SYNTAX:
            $response['error'] = 'Syntax error, malformed JSON';
            break;
          case JSON_ERROR_UTF8:
            $response['error'] = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
          default:
            $response['error'] = 'Unknown error';
            break;
        }
        $response['error'] = 'Error while decoding JSON. ' . $response['error'];
      }
    }

    // Return decoded response object or error array.
    return $response;
  }

  // Get the list of data resources from a given provider.
  function GetProviderResources($provider)
  {
      $url = "https://registry.nbnatlas.org/ws/dataProvider/" . $provider;
      $response = $this->CurlGetString($url);
      if(array_key_exists('error', $response)) {
        return $response;
      }
      else {
        return $response['dataResources'];
      }
  }
}