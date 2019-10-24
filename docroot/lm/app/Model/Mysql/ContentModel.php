<?php

namespace App\Model\Mysql;

use Illuminate\Support\Facades\DB;
use App\Support\Helper;
use App\Model\Elastic\BadgeModel;

/**
 * Purpose of this class is to alter the content in db.
 */
class ContentModel {

  /**
   * Get term name by tid and vid.
   *
   * @param int $tid
   *   Taxonomy id.
   * @param mixed $vid
   *   Vocabulary id.
   *
   * @return string
   *   Term name.
   */
  public static function getTermName($tid, $vid = NULL) {
    $query = DB::table('taxonomy_term_field_data as ttfd');
    $query->whereIn('ttfd.tid', $tid);
    $query->where('ttfd.langcode', '=', 'en');
    if (!empty($vid) && $vid == 'leaderboard_comparison') {
      $query->where('ttfd.vid', '=', $vid);
      $query->where('ttfd.langcode', '=', 'en');
    }
    elseif (!empty($vid) && $vid != 'leaderboard_comparison') {
      $query->where('ttfd.vid', '=', $vid);
    }
    $results = $query->get();
    if (empty($query)) {
      return FALSE;
    }
    $data = [];
    foreach ($results as $key => $result) {
      $data[] = $result->name;
    }

    return $data;
  }

  /**
   * Get market name by tid and language.
   *
   * @param int $tid
   *   Taxonomy id.
   * @param string $lang
   *   Language id.
   *
   * @return string
   *   Term name.
   */
  public static function getMarketNameByLang($tid, $lang) {
    $query = DB::table('taxonomy_term_field_data as ttfd');
    $query->whereIn('ttfd.tid', $tid);
    $query->whereIn('ttfd.langcode', ['en', $lang]);
    $results = $query->get();
    if (empty($query)) {
      return FALSE;
    }
    $data = [];
    foreach ($results as $key => $result) {
      if (empty($data[$result->tid]) || $data[$result->tid]['lang'] == 'en') {
        $data[$result->tid] = [
          'name' => $result->name,
          'mid' => $result->tid,
          'lang' => $result->langcode,
        ];
      }
    }
    $market_name = array_column($data, 'name');

    return $market_name;
  }

  /**
   * Set levels node data.
   *
   * @param int $nid
   *   Node id.
   * @param int $tid
   *   Term id.
   *
   * @return bool
   *   True.
   */
  public static function setTermsNodeData($nid, $tid) {
    $query = DB::table('lm_terms_node as tn')
      ->select('tn.tid')
      ->where('tn.nid', '=', $nid)
      ->get();
    if (!empty($query[0])) {
      DB::table('lm_terms_node')
        ->where('nid', $nid)
        ->update(['tid' => $tid]);
      return TRUE;
    }
    DB::table('lm_terms_node')->insert([
      ['nid' => $nid, 'tid' => $tid],
    ]);

    return TRUE;
  }

  /**
   * Set LRS data.
   *
   * @param array $params
   *   Rest resource query parameters.
   *
   * @return bool
   *   True or false.
   */
  public static function setLrsData($params) {
    $query = DB::table('lm_lrs_records as records')
      ->select('records.statement_status')
      ->where('records.nid', '=', $params['nid'])
      ->where('records.uid', '=', $params['uid'])
      ->get()->all();
    if (isset($query[0]) && !empty($query[0])) {
      // If level status is in complete state return.
      if ($query[0]->statement_status != 'progress') {
        return FALSE;
      }
      return DB::table('lm_lrs_records')
        ->where('nid', $params['nid'])
        ->update(['statement_status' => $params['statement_status'], 'tid' => $params['tid']]);
    }

    DB::table('lm_lrs_records')->insert([
      [
        'nid' => $params['nid'],
        'uid' => $params['uid'],
        'tid' => $params['tid'],
        'statement_status' => $params['statement_status'],
        'statement_id' => $params['statement_id'],
        'created_on' => time(),
      ],
    ]);
    // Allocate badge to user on completion of level.
    self::allocateBadgeConditions($params);

    return TRUE;
  }

  /**
   * Fetch intereactive level node status.
   *
   * @param int $uid
   *   User id.
   * @param int $tid
   *   Term id.
   * @param array $nid
   *   Node id.
   *
   * @return array
   *   Intereactive level node.
   */
  public static function getIntereactiveLevelNodeStatus($uid, $tid, $nid) {
    $nid = unserialize($nid);
    $query = DB::table('lm_lrs_records as records');
    $query->select('records.nid', 'records.statement_status');
    $query->where('records.uid', '=', $uid);
    if (!empty($tid)) {
      $query->where('records.tid', '=', $tid);
    }
    $query->whereIn('records.nid', $nid);
    $results = $query->get();
    $data = [];
    foreach ($results as $key => $result) {
      $data[$result->nid] = $result;
    }

    return $data;
  }

  /**
   * Get node type by nid.
   *
   * @param int $nid
   *   Node id.
   *
   * @return string
   *   Node type.
   */
  public static function getTypeByNid($nid) {
    $query = DB::table('node as n')
      ->select('n.type')
      ->where('n.nid', '=', $nid)
      ->first();
    return $query;
  }

  /**
   * Get node type by user lang.
   *
   * @param int $nid
   *   Node id.
   * @param string $lang
   *   User langcode.
   *
   * @return string
   *   Node type.
   */
  public static function getTypeByLang($nid, $lang) {
    $query = DB::table('node_field_data as n')
      ->select('n.type')
      ->where('n.nid', '=', $nid)
      ->where('n.langcode', '=', $lang)
      ->first();

    return $query;
  }

  /**
   * Get Point Value by nid.
   *
   * @param int $nid
   *   Node id.
   * @param string $lang
   *   Language code.
   *
   * @return int
   *   Node points.
   */
  public static function getPointValueByNid($nid, $lang) {
    if (!is_array($nid)) {
      $nid = [$nid];
    }
    $results = DB::table('node__field_point_value as points')
      ->select('points.field_point_value_value')
      ->whereIn('points.entity_id', $nid)
      ->where('points.langcode', '=', $lang)
      ->get();
    $points = 0;
    foreach ($results as $key => $result) {
      $points = $points + $result->field_point_value_value;
    }

    return $points;
  }

  /**
   * Get products content based on node id.
   *
   * @param int $nid
   *   Node Id.
   * @param string $lang
   *   User language.
   *
   * @return array
   *   Products content field data.
   */
  public static function getProductsContent($nid, $lang) {
    $query = DB::table('node_field_data as nd');
    $query->select(
      'dt.field_display_title_value', 'fpi.field_field_product_image_target_id', 'wtoo.field_why_there_s_only_one_value',
      'fs.field_subtitle_value'
    );
    $query->leftJoin('node__field_display_title as dt', function ($join) {
        $join->on('nd.nid', '=', 'dt.entity_id');
        $join->on('nd.langcode', '=', 'dt.langcode');
    });
    $query->leftJoin('node__field_field_product_image as fpi', 'nd.nid', '=', 'fpi.entity_id');
    $query->leftJoin('node__field_why_there_s_only_one as wtoo', function ($join) {
        $join->on('nd.nid', '=', 'wtoo.entity_id');
        $join->on('nd.langcode', '=', 'wtoo.langcode');
    });
    $query->leftJoin('node__field_subtitle as fs', function ($join) {
        $join->on('nd.nid', '=', 'fs.entity_id');
        $join->on('nd.langcode', '=', 'fs.langcode');
    });
    $query->where('nd.nid', '=', $nid);
    $query->where('nd.langcode', '=', $lang);
    $result = $query->get();

    return [
      (!empty($result[0]->field_display_title_value)) ? $result[0]->field_display_title_value : '',
      (!empty($result[0]->field_field_product_image_target_id)) ? $result[0]->field_field_product_image_target_id : '',
      (!empty($result[0]->field_why_there_s_only_one_value)) ? $result[0]->field_why_there_s_only_one_value : '',
      (!empty($result[0]->field_subtitle_value)) ? $result[0]->field_subtitle_value : '',
    ];
  }

  /**
   * Get tools content based on node id.
   *
   * @param int $nid
   *   Node Id.
   * @param string $lang
   *   User language.
   *
   * @return array
   *   Tools content field data.
   */
  public static function getToolsContent($nid, $lang) {
    $query = DB::table('node_field_data as nd');
    $query->select('dt.field_display_title_value', 'fi.field_tool_thumbnail_target_id', 'td.field_tool_description_value');
    $query->leftJoin('node__field_display_title as dt', function ($join) {
        $join->on('nd.nid', '=', 'dt.entity_id');
        $join->on('nd.langcode', '=', 'dt.langcode');
    });
    $query->leftJoin('node__field_tool_thumbnail as fi', function ($join) {
        $join->on('nd.nid', '=', 'fi.entity_id');
        $join->on('nd.langcode', '=', 'fi.langcode');
    });
    $query->leftJoin('node__field_tool_description as td', function ($join) {
        $join->on('nd.nid', '=', 'td.entity_id');
        $join->on('nd.langcode', '=', 'td.langcode');
    });
    $query->where('nd.nid', '=', $nid);
    $query->where('nd.langcode', '=', $lang);
    $results = $query->get();

    return [
      (!empty($results[0]->field_display_title_value)) ? $results[0]->field_display_title_value : '',
      (!empty($results[0]->field_tool_thumbnail_target_id)) ? $results[0]->field_tool_thumbnail_target_id : '',
      (!empty($results[0]->field_tool_description_value)) ? $results[0]->field_tool_description_value : '',
    ];
  }

  /**
   * Get stories content based on node id.
   *
   * @param int $nid
   *   Node Id.
   * @param string $lang
   *   User language.
   *
   * @return array
   *   Stories content field data.
   */
  public static function getStoriesContent($nid, $lang) {
    $query = DB::table('node_field_data as nd');
    $query->select('dt.field_display_title_value', 'fi.field_hero_image_target_id', 'nb.body_value', 'fs.field_sub_title_value');
    $query->leftJoin('node__field_display_title as dt', function ($join) {
        $join->on('nd.nid', '=', 'dt.entity_id');
        $join->on('nd.langcode', '=', 'dt.langcode');
    });
    $query->leftJoin('node__field_hero_image as fi', function ($join) {
        $join->on('nd.nid', '=', 'fi.entity_id');
        $join->on('nd.langcode', '=', 'fi.langcode');
    });
    $query->leftJoin('node__body as nb', function ($join) {
        $join->on('nd.nid', '=', 'nb.entity_id');
        $join->on('nd.langcode', '=', 'nb.langcode');
    });
    $query->leftJoin('node__field_sub_title as fs', function ($join) {
        $join->on('nd.nid', '=', 'fs.entity_id');
        $join->on('nd.langcode', '=', 'fs.langcode');
    });
    $query->where('nd.nid', '=', $nid);
    $query->where('nd.langcode', '=', $lang);
    $results = $query->get();

    return [
      (!empty($results[0]->field_display_title_value)) ? $results[0]->field_display_title_value : '',
      (!empty($results[0]->field_hero_image_target_id)) ? $results[0]->field_hero_image_target_id : '',
      (!empty($results[0]->body_value)) ? $results[0]->body_value : '',
      (!empty($results[0]->field_sub_title_value)) ? $results[0]->field_sub_title_value : '',
    ];
  }

  /**
   * Get level content based on node id.
   *
   * @param int $nid
   *   Node Id.
   * @param string $lang
   *   User language.
   *
   * @return array
   *   Interactive content field data.
   */
  public static function getLevelContent($nid, $lang) {
    $query = DB::table('node_field_data as nd');
    $query->select('dt.field_headline_value', 'fi.field_hero_image_target_id', 'nb.id');
    $query->leftJoin('node__field_headline as dt', function ($join) {
        $join->on('nd.nid', '=', 'dt.entity_id');
        $join->on('nd.langcode', '=', 'dt.langcode');
    });
    $query->leftJoin('node__field_hero_image as fi', function ($join) {
        $join->on('nd.nid', '=', 'fi.entity_id');
        $join->on('nd.langcode', '=', 'fi.langcode');
    });
    $query->leftJoin('paragraphs_item_field_data as nb', function ($join) {
        $join->on('nd.nid', '=', 'nb.parent_id');
        $join->on('nd.langcode', '=', 'nb.langcode');
    });
    $query->where('nd.nid', '=', $nid);
    $query->where('nd.langcode', '=', $lang);
    $results = $query->get();

    return [
      (!empty($results[0]->field_headline_value)) ? $results[0]->field_headline_value : '',
      (!empty($results[0]->field_hero_image_target_id)) ? $results[0]->field_hero_image_target_id : '',
      (!empty($results[0]->id)) ? $results[0]->id : '',
    ];
  }

  /**
   * Get level paragraph field value content based on node id.
   *
   * @param int $id
   *   Node Id.
   * @param string $lang
   *   User language.
   *
   * @return mixed
   *   Paragragh field data.
   */
  public static function getLevelParagraphById($id, $lang) {
    $query = DB::table('paragraph__field_intro_text as fm');
    $query->select('fm.field_intro_text_value', 'mf.field_sub_title_value');
    $query->leftJoin('paragraph__field_sub_title as mf', 'mf.entity_id', '=', 'fm.entity_id');
    $query->where('fm.entity_id', '=', $id);
    $query->where('fm.langcode', '=', $lang);
    $results = $query->get();

    return [
      (!empty($results[0]->field_intro_text_value)) ? $results[0]->field_intro_text_value : '',
      (!empty($results[0]->field_sub_title_value)) ? $results[0]->field_sub_title_value : '',
    ];
  }

  /**
   * Fetch image url by fid.
   *
   * @param int $fid
   *   File Id.
   *
   * @return string
   *   Image url.
   */
  public static function getImageUrlByFid($fid) {
    $site_image_url = getenv("SITE_IMAGE_URL");
    if (!empty($fid)) {
      $query = DB::table('file_managed as fm');
      $query->select('fm.uri');
      $query->leftJoin('media_field_data as mfd', 'mfd.thumbnail__target_id', '=', 'fm.fid');
      $query->where('mfd.mid', '=', $fid);
      $result = $query->get();
    }
    $url = isset($result[0]->uri) ? str_replace("public://", $site_image_url, $result[0]->uri) : '';

    return $url;
  }

  /**
   * Fetch intereactive level status.
   *
   * @param mixed $nid
   *   Node id.
   *
   * @return array
   *   Level response based on term id.
   */
  public static function getIntereactiveLevelTermStatus($nid) {
    $nid = $nid != NULL ? unserialize($nid) : '';
    $results = DB::table('lm_lrs_records as records')
      ->select('records.tid', 'records.nid', 'records.statement_status')
      ->whereIn('records.nid', $nid)
      ->get();
    $data = [];
    foreach ($results as $key => $result) {
      $data[$result->tid][] = $result;
    }

    return $data;
  }

  /**
   * Delete levels node data.
   *
   * @param int $nid
   *   Node id.
   * @param int $tid
   *   Term id.
   *
   * @return bool
   *   True or false.
   */
  public static function deleteTermsNodeData($nid, $tid) {
    try {
      DB::table('lm_terms_node')
        ->where('nid', '=', $nid)
        ->where('tid', '=', $tid)
        ->delete();
      DB::table('lm_lrs_records')
        ->where('nid', '=', $nid)
        ->where('tid', '=', $tid)
        ->delete();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Conditions to allocate badges to user.
   *
   * @param mixed $params
   *   Level details.
   */
  public static function allocateBadgeConditions($params) {
    $elastic_client = Helper::checkElasticClient();
    if (!$elastic_client) {
      return FALSE;
    }
    // Allocate badge to user on level percentage.
    $level_info = self::getLevelModules($params);
    $percentage = self::getLevelModulePercentage($params, $level_info);
    list($badge_tid, $badge_percentage) = self::getLevelBadgeAndPercentage($params);
    if (!empty($badge_tid)) {
      $user_badge[] = self::getTermName([$badge_tid])[0];
      if ($percentage >= $badge_percentage && $percentage != 0) {
        BadgeModel::allocateBadgeToUser($params['nid'], $params, $user_badge, $elastic_client);
      }
    }
  }

  /**
   * Fetch level module information.
   *
   * @param mixed $params
   *   Level details.
   *
   * @return array
   *   Level related nids.
   */
  public static function getLevelModules($params) {
    $market = explode(", ", $params['market']);
    // Query for get all module by category id.
    $query = DB::table('node_field_data as n');
    $query->leftJoin('node__field_learning_category as nflc', 'n.nid', '=', 'nflc.entity_id');
    $query->leftJoin('node__field_markets as nfm', 'n.nid', '=', 'nfm.entity_id');
    $query->select('n.nid');
    $query->distinct('n.nid');
    $query->where('n.type', '=', 'level_interactive_content');
    $query->where('n.status', '=', 1);
    $query->where('n.langcode', '=', $params['lang']);
    $query->where('nflc.langcode', '=', $params['lang']);
    $query->where('nflc.field_learning_category_target_id', '=', $params['tid']);
    $query->whereIn('nfm.field_markets_target_id', $market);
    $result = $query->get();
    $nids = [];
    foreach ($result as $key => $value) {
      $nids[] = $value->nid;
    }

    return $nids;
  }

  /**
   * Fetch level module percentage.
   *
   * @param mixed $params
   *   Level details.
   * @param array $nids
   *   Level nodes.
   *
   * @return int
   *   Level percentage.
   */
  public static function getLevelModulePercentage($params, $nids) {
    // Query for get level percentage.
    $get_percentage = DB::table('lm_lrs_records as records');
    $get_percentage->select('records.statement_status', 'records.nid');
    $get_percentage->where('records.uid', '=', $params['uid']);
    $get_percentage->where('records.tid', '=', $params['tid']);
    $get_percentage->whereIn('records.nid', $nids);
    $percentage_result = $get_percentage->get();
    $incomplete_status = ['progress', NULL];
    foreach ($percentage_result as $key => $result) {
      $data[$result->nid] = $result;
    }
    foreach ($nids as $key => $value) {
      $percentage_status = (isset($data[$value]) && !in_array($data[$value]->statement_status, $incomplete_status)) ? (int) 1 : (int) 0;
      $response[$value] = [
        "nid" => (int) $value,
        "status" => $percentage_status,
      ];
    }
    $total_count = count($nids);
    $completed = 0;
    foreach ($response as $key => $module_detail) {
      if ($module_detail['status'] == 1) {
        $completed = $completed + 1;
      }
    }
    $percentage = ceil($completed / $total_count * 100);

    return $percentage;
  }

  /**
   * Fetch level badge name and percentage.
   *
   * @param mixed $params
   *   Level details.
   *
   * @return array
   *   Badge name and percentage.
   */
  public static function getLevelBadgeAndPercentage($params) {
    // Query for get badge name and percentage.
    $badge = DB::table('taxonomy_term__field_badges as fb');
    $badge->leftJoin('taxonomy_term__field_percentage as fp', 'fp.entity_id', '=', 'fb.entity_id');
    $badge->select('fb.field_badges_target_id', 'fp.field_percentage_value');
    $badge->where('fb.entity_id', '=', $params['tid']);
    $badge_result = $badge->get();

    return [
      $badge_result[0]->field_badges_target_id,
      $badge_result[0]->field_percentage_value,
    ];
  }

  /**
   * Update number of quiz attempt per user.
   *
   * @param int $nid
   *   Node id.
   * @param int $uid
   *   User id.
   */
  public static function quizAttemptSummary($nid, $uid) {
    $query = DB::insert("INSERT INTO `quiz_attempt_summary` (`quiz_id`, `uid`,
    `no_of_attempts`) VALUES ($nid, $uid, 1) ON DUPLICATE KEY UPDATE
    `no_of_attempts`= `no_of_attempts` + 1");
  }

  /**
   * Get node status by nid.
   *
   * @param int $nid
   *   Node id.
   *
   * @return int
   *   Node status.
   */
  public static function getStatusByNid($nid) {
    $query = DB::table('node_field_data as n')
      ->select('n.status')
      ->where('n.nid', '=', $nid)
      ->first();

    return $query->status;
  }

  /**
   * Get brand term ids.
   *
   * @return array
   *   Brand term ids.
   */
  public static function getBrandTermIds() {
    $query = DB::table('taxonomy_term__field_brand_key as ttfbk')
      ->select('ttfbk.entity_id', 'ttfbk.field_brand_key_value')
      ->get()->all();

    return json_decode(json_encode((array) $query), TRUE);
  }

  /**
   * Get trlx utility config form values.
   *
   * @return array
   *   Content config form data.
   */
  public static function getTrlxUtilityConfigValues() {
    $query = DB::table('config')
      ->select('config.data')
      ->where('config.name', '=', 'trlx_utility.settings')
      ->first();
    return unserialize($query->data);
  }

  /**
   * Get content section key by tid.
   *
   * @param int $tid
   *   Taxonomy id.
   *
   * @return string
   *   Key name.
   */
  public static function getContentSectionKeyByTid($tid) {
    $query = DB::table('taxonomy_term_data as ttfd');
    $query->leftJoin('taxonomy_term__field_content_section_key as tk', 'tk.entity_id', '=', 'ttfd.tid');
    $query->where('ttfd.tid', '=', $tid);
    $query->where('ttfd.langcode', '=', 'en');

    $results = $query->get();
    if (empty($query)) {
      return FALSE;
    }
    foreach ($results as $key => $result) {
      $data = $result->field_content_section_key_value;
    }
    return $data;
  }

  /**
   * Get trlx section names.
   *
   * @return array
   *   Section names.
   */
  public static function getTrlxSectionNames() {
    $sectionData = [];
    $sectionData = [
      'trend' => 'TR TRENDS',
      'consumer' => 'CONSUMERS',
      'sellingTips' => 'SELLING TIPS',
      'insiderCorner' => "INSIDER'S CORNER",
      'tools' => 'VIDEOS',
      'faq' => 'FAQ',
      'product_detail' => 'FACT SHEETS',
      'brand_story' => 'BRAND STORY',
      'level_interactive_content' => 'MY LESSONS',
    ];
    return $sectionData;
  }

  /**
   * Get node data by nid.
   *
   * @param int $nid
   *   Node id.
   * @param int $language
   *   Language code.
   *
   * @return string
   *   Node type.
   */
  public static function getNodeDataByNid($nid, $language) {
    $query = DB::table('node_field_data as n')
      ->select('n.nid',
       'nfdt.field_display_title_value',
       'nfh.field_headline_value',
       'nfpv.field_point_value_value',
        'nfb.field_brands_target_id',
         'nfcs.field_content_section_target_id',
          'nfhi.field_hero_image_target_id',
           'nfpi.field_field_product_image_target_id',
            'nftt.field_tool_thumbnail_target_id',
             'nffi.field_featured_image_target_id')
      ->leftJoin('node__field_display_title as nfdt', function ($join) {
          $join->on('n.nid', '=', 'nfdt.entity_id');
          $join->on('n.langcode', '=', 'nfdt.langcode');
      })
      ->leftJoin('node__field_headline as nfh', function ($join) {
          $join->on('n.nid', '=', 'nfh.entity_id');
          $join->on('n.langcode', '=', 'nfh.langcode');
      })
      ->leftJoin('node__field_point_value as nfpv', function ($join) {
          $join->on('n.nid', '=', 'nfpv.entity_id');
          $join->on('n.langcode', '=', 'nfpv.langcode');
      })
      ->leftJoin('node__field_brands as nfb', function ($join) {
          $join->on('n.nid', '=', 'nfb.entity_id');
      })
      ->leftJoin('node__field_content_section as nfcs', function ($join) {
          $join->on('n.nid', '=', 'nfcs.entity_id');
      })
      ->leftJoin('node__field_hero_image as nfhi', function ($join) {
          $join->on('n.nid', '=', 'nfhi.entity_id');
          $join->on('n.langcode', '=', 'nfhi.langcode');
      })
      ->leftJoin('node__field_field_product_image as nfpi', function ($join) {
          $join->on('n.nid', '=', 'nfpi.entity_id');
          $join->on('n.langcode', '=', 'nfpi.langcode');
      })
      ->leftJoin('node__field_tool_thumbnail as nftt', function ($join) {
          $join->on('n.nid', '=', 'nftt.entity_id');
          $join->on('n.langcode', '=', 'nftt.langcode');
      })
      ->leftJoin('node__field_featured_image as nffi', function ($join) {
          $join->on('n.nid', '=', 'nffi.entity_id');
          $join->on('n.langcode', '=', 'nffi.langcode');
      })
      ->distinct('n.nid')
      ->where('n.nid', '=', $nid)
      ->where('n.status', '=', 1)
      ->where('n.langcode', '=', $language)
      ->first();
    return $query;
  }

  /**
   * Fetch bookmark image url by fid.
   *
   * @param int $fid
   *   File Id.
   *
   * @return string
   *   Image url.
   */
  public static function getBookmarkImageUrlByFid($fid) {
    $small_image_url = getenv("SITE_IMAGE_URL") . 'styles/bookmark_image_mobile/public/';
    $medium_image_url = getenv("SITE_IMAGE_URL") . 'styles/bookmark_image_tablet/public/';
    $large_image_url = getenv("SITE_IMAGE_URL") . 'styles/bookmark_image_desktop/public/';
    $url = [];
    if (!empty($fid)) {
      $query = DB::table('file_managed as fm');
      $query->select('fm.uri');
      $query->leftJoin('media_field_data as mfd', 'mfd.thumbnail__target_id', '=', 'fm.fid');
      $query->where('mfd.mid', '=', $fid);
      $result = $query->get();
    }
    $url[0] = isset($result[0]->uri) ? str_replace("public://", $small_image_url, $result[0]->uri) : '';
    $url[1] = isset($result[0]->uri) ? str_replace("public://", $medium_image_url, $result[0]->uri) : '';
    $url[2] = isset($result[0]->uri) ? str_replace("public://", $large_image_url, $result[0]->uri) : '';

    return $url;
  }

  /**
   * Get brand data from brand key.
   *
   * @return array
   *   Brand data.
   */
  public static function getBrandDataFromBrandKey($brand_key) {
    $query = DB::table('taxonomy_term__field_brand_key as ttfbk')
      ->select('ttfbk.entity_id', 'ttfbk.field_brand_key_value', 'ttfd.name')
      ->leftJoin('taxonomy_term_field_data as ttfd', 'ttfd.tid', '=', 'ttfbk.entity_id')
      ->where('ttfbk.field_brand_key_value', '=', $brand_key)
      ->first();

    return json_decode(json_encode((array) $query), TRUE);
  }

  /**
   * Get all trlx regions.
   *
   * @return array
   *   Regions data.
   */
  public static function getAllRegions() {
    $query = DB::table('taxonomy_term__parent as ttp')
      ->select('ttp.entity_id')
      ->where('ttp.parent_target_id', '=', 0)
      ->where('ttp.bundle', '=', 'markets')
      ->where('ttp.deleted', '=', 0)
      ->get()->all();

    return json_decode(json_encode((array) $query), TRUE);
  }

  /**
   * Get all trlx sub regions.
   *
   * @return array
   *   Sub regions data.
   */
  public static function getAllSubRegions() {
    $regions = ContentModel::getAllRegions();
    $query = DB::table('taxonomy_term__parent as ttp')
      ->select('ttp.entity_id')
      ->whereIn('ttp.parent_target_id', $regions)
      ->where('ttp.bundle', '=', 'markets')
      ->where('ttp.deleted', '=', 0)
      ->get()->all();

    return json_decode(json_encode((array) $query), TRUE);
  }

  /**
   * Get all trlx countries.
   *
   * @return array
   *   Countries data.
   */
  public static function getAllCountries() {
    $subRegions = ContentModel::getAllSubRegions();
    $query = DB::table('taxonomy_term__parent as ttp')
      ->select('ttp.entity_id')
      ->whereIn('ttp.parent_target_id', $subRegions)
      ->where('ttp.bundle', '=', 'markets')
      ->where('ttp.deleted', '=', 0)
      ->get()->all();

    return json_decode(json_encode((array) $query), TRUE);
  }

  /**
   * Get region subregion country key by tid.
   *
   * @param array $tids
   *   Taxonomy ids.
   *
   * @return array
   *   Key ids.
   */
  public static function getRegionSubregionCountryKeyByTid(array $tids) {
    $query = DB::table('taxonomy_term__field_region_subreg_country_id as tfrsc');
    $query->select('tfrsc.field_region_subreg_country_id_value');
    $query->whereIn('tfrsc.entity_id', $tids);
    $query->where('tfrsc.langcode', '=', 'en');
    $query->where('tfrsc.deleted', '=', 0);
    $results = $query->get()->all();
    return json_decode(json_encode((array) $results), TRUE);
  }

  /**
   * Insert user id in drupal table and send set user id.
   *
   * @return int
   *   User id.
   */
  public static function setUserData($params) {
    $query = DB::table('user_records as records')
      ->select('records.id')
      ->where('records.uid', '=', $params['body']['uid'])
      ->get()->all();
    $insert = 0;
    if (empty($query)) {
      $insert = DB::table('user_records')->insertGetId(
       [
         'uid' => $params['body']['uid'],
         'created_on' => time(),
       ]
      );
      return $insert;
    }
  }

}
