<?php

namespace Drupal\trlx_utility\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LogMessageParserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * TrlxLogger controller.
 */
class TrlxLogger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The request stack instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a ElxLogger object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The requestStack to get current request.
   */
  public function __construct(LogMessageParserInterface $parser, RequestStack $request_stack) {
    $this->parser = $parser;
    $this->requestStack = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($level <= RfcLogLevel::DEBUG) {
      $output = fopen('php://stderr', 'w');
    }
    $severity = strtoupper(RfcLogLevel::getLevels()[$level]);
    $username = '';

    if (isset($context['user']) && !empty($context['user'])) {
      if ($context['user'] instanceof NodeInterface) {
        $username = $context['user']->getAccountName();
      }
      else {
        if (isset($content['user']->name)) {
          $username = $context['user']->name;
        }
      }
    }

    if (empty($username)) {
      $username = 'Not available';
    }
    $data_value = '';
    if (isset($context['data']) && !empty($context['data'])) {
      $data_value = $context['data'];
    }
    if (empty($data_value)) {
      $data_value = 'No Data available';
    }
    $request_uri = !empty($context['request_uri']) ? $context['request_uri'] : NULL;
    $referrer_uri = !empty($context['referer']) ? $context['referer'] : NULL;
    $variables = $this->parser->parseMessagePlaceholders($message, $context);
    $request_header = !is_null($this->requestStack) ? $this->requestStack->headers : NULL;

    $x_trace_operation = (!is_null($request_header)
    && !empty($request_header->get('X-TRACE-OPERATION'))) ?
    $request_header->get('X-TRACE-OPERATION') : '';
    $x_trace_requestid = (!is_null($request_header)
    && !empty($request_header->get('X-TRACE-OPERATION'))) ?
    $request_header->get('X-TRACE-REQUESTID') : '';
    $x_trace_user = (!is_null($request_header)
    && !empty($request_header->get('X-TRACE-USER'))) ?
    $request_header->get('X-TRACE-USER') : '';
    $message = t('[@severity] [@type] @message | user: @user | uri:
    @request_uri | referer: @referer_uri | data: @data | x_trace_operation:
    @x_trace_operation | x_trace_requestid: @x_trace_requestid | x_trace_user:
    @x_trace_user', [
      '@severity' => $severity,
      '@type' => !empty($context['channel']) ? $context['channel'] : NULL,
      '@message' => $message,
      '@user' => $username,
      '@request_uri' => $request_uri,
      '@referer_uri' => $referrer_uri,
      '@data' => $data_value,
      '@x_trace_operation' => $x_trace_operation,
      '@x_trace_requestid' => $x_trace_requestid,
      '@x_trace_user' => $x_trace_user,
    ]);
    try {
      fwrite($output, $message . "\r\n");
      fclose($output);
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

}
