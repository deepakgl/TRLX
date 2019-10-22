<?php


namespace App\Http\Controllers;

use App\Model\Elastic\ElasticUserModel;
use App\Support\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Mockery\Exception;

class UserController extends Controller
{
	/**
	 * Elastic search limit.
	 *
	 * @var limit
	 */
	private $limit = NULL;
	/**
	 * Elastic search offset.
	 *
	 * @var offset
	 */
	private $offset = NULL;

	/**
	 * Elastic client.
	 */
	private $elasticClient = NULL;

	/**
	 * UserController constructor.
	 * @param Request $request
	 */
	public function __construct()
	{
		$this->elasticClient = '';
		$this->elasticClient = Helper::checkElasticClient();
		if (!$this->elasticClient) {
			return $this->errorResponse('No alive nodes found in cluster.', Response::HTTP_INTERNAL_SERVER_ERROR);
		}
		$this->limit = 10;
		$this->offset = 0;
	}

	/**
	 * @param Request $request
	 * @return \App\Support\json
	 */
	public function updateUsersIndex(Request $request)
	{
		try{
			$data = $request->all();
			unset($data['/v1/users']);

			$rules['*.uid'] = 'required|integer|min:1';
			$rules['*.email'] = 'required|email';

			$message['*.uid.required'] = 'The field uid is required.';
			$message['*.email.required'] = 'The field email is required.';
			$message['*.email.email'] = 'The field email should be valid email id.';

			$validator = Validator::make($data, $rules, $message);
			if ($validator->fails())
			{
				return Helper::jsonError($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
			}

			if(is_array($data)){
				foreach ($data as $key=>$user){
					$this->updateUserIndex($user);
				}
			}else{
				$this->updateUserIndex($data);
			}
			$response['results'] = [
				'message' => 'Users data updated successfully.',
			];
			return new Response($response, 200);
		}catch (Exception $e){
			return Helper::jsonError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @param $data
	 */
	private function updateUserIndex($data)
	{
		$params = [];
		$exist = ElasticUserModel::checkElasticUserIndex($data['uid'], $this->elasticClient);
		// If index not exist, create new index.
		if ($exist) {
			$elastic_data = ElasticUserModel::fetchElasticUserData($data['uid'], $this->elasticClient);
			$elastic_arr = $elastic_data['_source'];
			$params['body'] = $this->createUserBody($elastic_arr, $data, 'update');
			ElasticUserModel::updateElasticUserData($params, $data['uid'], $this->elasticClient);
		}
		$elastic_arr = $this->getEmptyUserDataArr();
		$params['body'] = $this->createUserBody($elastic_arr, $data, 'add');
		ElasticUserModel::createElasticUserIndex($params, $data['uid'], $this->elasticClient);
	}

	/**
	 * @param $elastic_arr
	 * @param $data
	 * @param string $action
	 * @return mixed
	 */
	private function createUserBody($elastic_arr, $data, $action = 'add')
	{
		$elastic_arr['uid'] = isset($data['uid']) && !empty($data['uid']) ? $data['uid'] : $elastic_arr['uid'];
		$elastic_arr['email'] = isset($data['email']) && !empty($data['email']) ? $data['email'] : $elastic_arr['email'];
		$elastic_arr['status'] = isset($data['status']) && !empty($data['status']) ? $data['status'] : $elastic_arr['status'];
		$elastic_arr['region'] = isset($data['region']) && !empty($data['region']) ? $data['region'] : $elastic_arr['region'];
		$elastic_arr['subRegion'] = isset($data['subRegion']) && !empty($data['subRegion']) ? $data['subRegion'] : $elastic_arr['subRegion'];
		$elastic_arr['country'] = isset($data['country']) && !empty($data['country']) ? $data['country'] : $elastic_arr['country'];
		$elastic_arr['market'] = array_merge($elastic_arr['region'], $elastic_arr['subRegion'], $elastic_arr['country']);
		$elastic_arr['locations'] = isset($data['locations']) && !empty($data['locations']) ? $data['locations'] : $elastic_arr['locations'];
		$elastic_arr['store'] = isset($data['store']) && !empty($data['store']) ? $data['store'] : $elastic_arr['store'];
		$elastic_arr['brands'] = isset($data['brands']) && !empty($data['brands']) ? $data['brands'] : $elastic_arr['brands'];
		$elastic_arr['node_views_faqs'] = isset($data['node_views_faqs']) && !empty($data['node_views_faqs']) ? $data['node_views_faqs'] : $elastic_arr['node_views_faqs'];
		$elastic_arr['node_views_level_interactive_content'] = isset($data['node_views_level_interactive_content']) && !empty($data['node_views_level_interactive_content']) ? $data['node_views_level_interactive_content'] : $elastic_arr['node_views_level_interactive_content'];
		$elastic_arr['node_views_product_detail'] = isset($data['node_views_product_detail']) && !empty($data['node_views_product_detail']) ? $data['node_views_product_detail'] : $elastic_arr['node_views_product_detail'];
		$elastic_arr['node_views_stories'] = isset($data['node_views_stories']) && !empty($data['node_views_stories']) ? $data['node_views_stories'] : $elastic_arr['node_views_stories'];
		$elastic_arr['node_views_best_sellers'] = isset($data['node_views_best_sellers']) && !empty($data['node_views_best_sellers']) ? $data['node_views_best_sellers'] : $elastic_arr['node_views_best_sellers'];
		$elastic_arr['node_views_tools'] = isset($data['node_views_tools']) && !empty($data['node_views_tools']) ? $data['node_views_tools'] : $elastic_arr['node_views_tools'];
		$elastic_arr['node_views_t_c'] = isset($data['node_views_t_c']) && !empty($data['node_views_t_c']) ? $data['node_views_t_c'] : $elastic_arr['node_views_t_c'];
		$elastic_arr['node_views_tools_pdf'] = isset($data['node_views_tools_pdf']) && !empty($data['node_views_tools_pdf']) ? $data['node_views_tools_pdf'] : $elastic_arr['node_views_tools_pdf'];
		$elastic_arr['like'] = isset($data['like']) && !empty($data['like']) ? $data['like'] : $elastic_arr['like'];
		$elastic_arr['bookmark'] = isset($data['bookmark']) && !empty($data['bookmark']) ? $data['bookmark'] : $elastic_arr['bookmark'];
		$elastic_arr['total_points'] = isset($data['total_points']) && !empty($data['total_points']) ? $data['total_points'] : $elastic_arr['total_points'];
		$elastic_arr['badge'] = isset($data['badge']) && !empty($data['badge']) ? $data['badge'] : $elastic_arr['badge'];
		$elastic_arr['is_otm'] = isset($data['is_otm']) && !empty($data['is_otm']) ? $data['is_otm'] : $elastic_arr['is_otm'];
    $elastic_arr['isExternal'] = isset($data['isExternal']) && !empty($data['isExternal']) ? $data['isExternal'] : $elastic_arr['isExternal'];
    $elastic_arr['primaryBrand'] = isset($data['primaryBrand']) && !empty($data['primaryBrand']) ? $data['primaryBrand'] : $elastic_arr['primaryBrand'];
		$elastic_arr['account'] = isset($data['account']) && !empty($data['account']) ? $data['account'] : $elastic_arr['account'];
		$elastic_arr['access_permission'] = isset($data['access_permission']) && !empty($data['access_permission']) ? $data['access_permission'] : $elastic_arr['access_permission'];
		$elastic_arr['ignore'] = isset($data['ignore']) && !empty($data['ignore']) ? $data['ignore'] : $elastic_arr['ignore'];
		return $elastic_arr;
	}

	private function getEmptyUserDataArr()
	{
		return [
			'uid' => '',
			'email' => '',
			'status' => 0,
			'region' => [],
			'subRegion' => [],
			'country' => [],
			'market' => [],
			'locations' => [],
			'store' => [],
			'brands' => [],
			'node_views_faqs' => [],
			'node_views_level_interactive_content' => [],
			'node_views_product_detail' => [],
			'node_views_stories' => [],
			'node_views_best_sellers' => [],
			'node_views_tools' => [],
			'node_views_t_c' => [],
			'node_views_tools_pdf' => [],
			'like' => [],
			'bookmark' => [],
			'total_points' => 0,
			'badge' => [],
			'is_otm' => '',
      'isExternal' => 0,
			'account' => [],
			'access_permission' => 0,
			'ignore' => 0,
      'primaryBrand' => 0,
		];
	}

	/**
	 * @param Request $request
	 * @return array|Response
	 */
	public function getUsersListing(Request $request)
	{
		$this->limit = !empty($request->input('limit')) ? $request->input('limit') : $this->limit;
		$this->offset = !empty($request->input('offset')) ? $request->input('offset') : $this->offset;
		$queryParams = ElasticUserModel::getElasticSearchParam('email', '', $this->limit, $this->offset);
		$searchResult = ElasticUserModel::search($this->elasticClient, $queryParams);
		if (empty($searchResult)) {
			return new Response(NULL, Response::HTTP_NO_CONTENT);
		}
		if (isset($searchResult['success']) && $searchResult['success'] == FALSE) {
			return Helper::jsonError($searchResult['error'], $searchResult['code']);
		}
		$total_count = $searchResult['hits']['total']['value'] - $this->offset;
		// Build pagination.
		$response['pager'] = [
			"count" => ($total_count > 0) ? $total_count : 0,
			"pages" => intval(ceil($total_count / $this->limit)),
			"items_per_page" => $this->limit,
			"current_page" => 0,
			"next_page" => 1,
		];
		$response['results'] = array_pluck($searchResult['hits']['hits'], '_source') ?: [];
		return new Response($response, Response::HTTP_OK);
	}
}
