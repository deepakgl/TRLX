<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Elasticsearch\ClientBuilder;
use App\Support\Helper;
use App\Model\Mysql\ContentModel;
use App\Traits\ApiResponser;

/**
 * Class to get the data from elastic based on query.
 */
class NotificationController extends Controller {

  use ApiResponser;

  const INDEX = 'index';
  const NOTIFICATION_DATE = 'notificationDate';
  const QUERY = 'query';
  const MATCH = 'match';
  const USER_ID = 'userId';
  const NOTIFICATION_FLAG = 'notificationFlag';
  const NOTIFICATION_KEY = 'notifications';
  const NOTIFICATION_LANGUAGE = 'notificationLanguage';

  /**
   * User id.
   *
   * @var userId
   */
  private $userId = NULL;

  /**
   * Elastic size.
   *
   * @var size
   */
  private $size = NULL;

  /**
   * Elastic notification index name.
   *
   * @var elasticNotificationIndex
   */
  private $elasticNotificationIndex = NULL;

  /**
   * Elastic notification index type.
   *
   * @var elasticNotificationIndexType
   */
  private $elasticNotificationIndexType = NULL;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->userId = 0;
    $this->size = 10000;
    $this->elasticNotificationIndex = getenv("ELASTIC_SEARCH_NOTIFICATION_INDEX");
    $this->elasticNotificationIndexType = getenv("ELASTIC_SEARCH_NOTIFICATION_TYPE");
    $this->helper = new Helper();
    $this->contentModel = new ContentModel();
    $this->searchHelper = new SearchController();
  }

  /**
   * Get notifications of user.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Data for the notification in json format.
   */
  public function getByUserId(Request $request) {
    // Get user id from jwt token.
    global $_userData;
    $this->userId = $_userData->userId;
    // Get elastic client.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $validatedData = $this->validate($request, [
      'limit' => 'sometimes|required|positiveinteger',
      'offset' => 'sometimes|required|positiveinteger',
      '_format' => 'required|format',
      'language' => 'required',
    ]);
    $langcode = $validatedData['language'];
    $this->limit = isset($validatedData['limit']) ? $validatedData['limit'] : 10;
    $this->offset = isset($validatedData['offset']) ? $validatedData['offset'] : 0;
    if (!empty($this->userId) && is_numeric($this->userId)) {
      // Get the search response from elastic.
      $params = [
        self::INDEX => $this->elasticNotificationIndex,
        'type' => $this->elasticNotificationIndexType,
        'size' => $this->size,
        'body' => [
          'sort' => [
            [self::NOTIFICATION_DATE => 'desc'],
          ],
          self::QUERY => [
            'bool' => [
              'must' => [
                [
                  self::MATCH => [
                    self::USER_ID => $this->userId,
                  ],
                ],
                [
                  self::MATCH => [
                    self::NOTIFICATION_LANGUAGE => $langcode,
                  ],
                ],
              ],
            ],
          ],
        ],
      ];

      $response = $client->search($params);
      $hits = count($response['hits']['hits']);
      $result = NULL;
      $i = 0;

      while ($i < $hits) {
        $result[$i] = $response['hits']['hits'][$i]['_source'];
        $i++;
      }
      $notifications = [];
      if (!empty($result)) {
        $notificationArray = [];

        foreach ($result as $key => $value) {
          $notificationArray[$key]['notificationType'] = $value['notificationType'];
          $notificationArray[$key][self::USER_ID] = $value[self::USER_ID];
          $notificationArray[$key]['notificationHeading'] = $value['notificationHeading'];
          $notificationArray[$key]['notificationText'] = $value['notificationText'];
          $notificationArray[$key][self::NOTIFICATION_DATE] = $value[self::NOTIFICATION_DATE];
          $notificationArray[$key]['notificationLink'] = $value['notificationLink'];
          $notificationArray[$key]['notificationLinkType'] = $value['notificationLinkType'];
          $notificationArray[$key][self::NOTIFICATION_FLAG] = $value[self::NOTIFICATION_FLAG];
        }

        // Get notification count.
        $notificationsCount = $this->notificationsCount($this->userId, $langcode);
        $notifications['notificationsCount'] = $notificationsCount;
        $notifications[self::NOTIFICATION_KEY] = $notificationArray;
        return $this->successResponse($notifications, Response::HTTP_CREATED);
      }
      else {
        $notifications['notificationsCount'] = 0;
        $notifications[self::NOTIFICATION_KEY] = [];
        return $this->successResponse($notifications, Response::HTTP_CREATED);
      }
    }
  }

  /**
   * To get unread notifications count.
   *
   * @param int $userId
   *   User ID.
   * @param int $langcode
   *   Selected language code.
   *
   * @return int
   *   Returns the count of unread notifications.
   */
  public function notificationsCount($userId, $langcode) {
    // Get elastic client.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    // Get the search response from elastic.
    $params = [
      self::INDEX => $this->elasticNotificationIndex,
      'type' => $this->elasticNotificationIndexType,
      'size' => $this->size,
      'body' => [
        self::QUERY => [
          'bool' => [
            'must' => [
              [
                self::MATCH => [
                  self::USER_ID => $userId,
                ],
              ],
              [
                self::MATCH => [
                  self::NOTIFICATION_LANGUAGE => $langcode,
                ],
              ],
              [
                self::MATCH => [
                  self::NOTIFICATION_FLAG => '0',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $client->search($params);
    return (count($response['hits']['hits']));
  }

  /**
   * Get index id from elastic for particular userId.
   *
   * @param int $userId
   *   User ID.
   * @param string $langcode
   *   Selected language code.
   *
   * @return int
   *   The index ID.
   */
  public function getIndexIdByUserId($userId, $langcode) {
    // Get elastic client.
    $client = Helper::checkElasticClient();
    if (!$client) {
      return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $this->userId = $userId;
    // Get the Ids from elastic.
    $params = [
      self::INDEX => $this->elasticNotificationIndex,
      'type' => $this->elasticNotificationIndexType,
      'size' => $this->size,
      'body' => [
        self::QUERY => [
          'bool' => [
            'must' => [
              [
                self::MATCH => [
                  self::USER_ID => $this->userId,
                ],
              ],
              [
                self::MATCH => [
                  self::NOTIFICATION_LANGUAGE => $langcode,
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $client->search($params);
    $hits = count($response['hits']['hits']);
    $result = NULL;
    $i = 0;

    while ($i < $hits) {
      $result[$i] = $response['hits']['hits'][$i]['_id'];
      $i++;
    }

    return $result;
  }

  /**
   * Update notifications data flag once user view them.
   *
   * @param \Illuminate\Http\Request $request
   *   Rest resource query parameters.
   *
   * @return json
   *   Read notification flag.
   */
  public function updateNotificationsFlag(Request $request) {
    global $_userData;
    $uid = $_userData->userId;
    $validatedData = $this->validate($request, [
      '_format' => 'required|format',
      'language' => 'required',
    ]);
    $langcode = $validatedData['language'];
    // Get user id from jwt token.
    if (is_numeric($uid)) {
      $ids = $this->getIndexIdByUserId($uid, $langcode);
      // Get elastic client.
      $client = Helper::checkElasticClient();
      if (!$client) {
        return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
      }
      foreach ($ids as $value) {
        $params = [
          self::INDEX => $this->elasticNotificationIndex,
          'type' => $this->elasticNotificationIndexType,
          'id' => $value,
          'body' => [
            'doc' => [
              self::NOTIFICATION_FLAG => '1',
            ],
          ],
        ];

        // Update notification flag value.
        $client->update($params);
      }

      $response = ['message' => trans('Notification status updated for the user: ') . $uid];
      return $this->successResponse($response, Response::HTTP_CREATED);
    }
  }

  /**
   * Get elastic client.
   *
   * @return object
   *   Returns the elastic client.
   */
  protected function getElasticClient() {
    $hosts = [
      [
        'host' => getenv("ELASTIC_URL"),
        'port' => getenv("ELASTIC_PORT"),
        'scheme' => getenv("ELASTIC_SCHEME"),
      ],
    ];

    return (ClientBuilder::create()->setHosts($hosts)->build());
  }

}
