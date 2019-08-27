<?php

namespace Drupal\elx_utility;

use SendGrid\Email;
use SendGrid\Client;
use Drupal\Core\Mail\MailFormatHelper;
use GuzzleHttp\Exception\ClientException;

/**
 * Mail functionality using sendgrid.
 *
 * Provides helper function :
 *
 * lxSendGridSendMail()  : Send mail using sendgrid.
 */
class ElxSendGridMail {

  /**
   * Send mail using sendgrid.
   *
   * @param string $to
   *   The email id to send mail.
   * @param string $subject
   *   Mail subject.
   * @param string $mail_body_text
   *   Plain text mail body.
   * @param string $from
   *   From if any custom one.
   *
   * @return mixed
   *   The response array by sendgrid.
   */
  public function elxSendGridSendMail($to, $subject, $mail_body_text, $from = NULL) {
    // Get sendgrid api key
    // NALX-486: Adding config ignore for sendgrid.
    $sendgrid_api_key = \Drupal::config('sendgrid_integration.settings')->get('apikey');
    // Get site email address as default.
    $mail_from = empty($from) ? \Drupal::config('system.site')->get('mail') : $from;
    // Get site name.
    $sitename = \Drupal::config('system.site')->get('name');
    // Check got empty key.
    if (empty($sendgrid_api_key)) {
      // Set a error in the logs if there is no api key.
      // \Drupal::logger('elx_utility')->info('No api secret key has been set');
      // Return false to indicate message was not able to send.
      return FALSE;
    }
    $options = [
      'turn_off_ssl_verification' => FALSE,
      'protocol' => 'https',
      'port' => NULL,
      'url' => NULL,
      'raise_exceptions' => FALSE,
    ];
    // Defining default unique args.
    $unique_args = [
      'timestamp' => \Drupal::time()->getCurrentTime(),
      'module' => 'lx_utility',
    ];
    // Create a new sendgrid object.
    $client = new Client($sendgrid_api_key, $options);
    $sendgrid_message = new Email();
    $sendgrid_message
      ->addTo($to)
      ->setFrom($mail_from)
      ->setSubject($subject)
      ->setUniqueArgs($unique_args)
      ->setFromName($sitename)
      ->setText(MailFormatHelper::wrapMail(MailFormatHelper::htmlToText($mail_body_text)));
    // Lets try and send the message and catch the error.
    try {
      $response = $client->send($sendgrid_message);
    }
    catch (ClientException $e) {
      // \Drupal::logger('elx_utility')->info('Sending emails to sendgrid
      // service failed with error code ' . $e->getCode());
      $exception = (string) $e->getResponse()->getBody();
      $exception = json_decode($exception);
      foreach ($exception->errors as $error_info) {
        // \Drupal::logger('elx_utility')->info('Sendgrid generated error : ' .
        // $error_info);.
      }

      return FALSE;
    }

    return !empty($response->getBody()->message) ? $response->getBody()->message : FALSE;
  }

}
