<?php
/**
 * Manage User
 *
 * PHP version 5.6
 *
 * @category  Users
 * @package   Users
 * @author    Ramasankar <ramasankarm@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Users\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Settings\Models\Language;
use App\Modules\Users\Models\Privileges;
use App\Modules\Users\Models\SecurityQuestion;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\UserPrivilegesRel;
use App\Modules\Users\Models\UserRole;
use App\Modules\Users\Models\UserRolePrivilegesRel;
use App\Modules\Users\Models\UserRoleRel;
use App\Modules\Users\Models\UserStoreRel;
use App\Modules\Users\Models\PrivilegesSubModules;
use App\Modules\Users\Models\PrivilegesSubModulesRel;
use App\Modules\Users\Models\UserModulePrivilegeRel;
use App\Modules\Users\Models\UserSubModulePrivilegeRel;
use \Firebase\JWT\JWT;

/**
 * User Controller
 *
 * @category Class
 * @package  Users
 * @author   Ramasankar <ramasankarm@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class UsersController extends ParentController {
	/**
	 * POST: Authenticate user for login
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return User details
	 */
	public function login($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Auth', 'invalid_login'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (!empty($allPostPutVars)
			&& !empty($allPostPutVars['email']) && !empty($allPostPutVars['password'])
		) {
			$userData = [
				'email' => $allPostPutVars['email'],
				'password' => $allPostPutVars['password'],
			];
			$userObj = new User();
			$getUserDetails = $userObj->where('email', $userData['email']);
			if ($getUserDetails->count() > 0) {
				$getUser = $getUserDetails->with('user_roles')
					->first()
					->toArray();
				// Verify Password in Database
				if (password_verify($userData['password'], $getUser['password'])) {
					$privileges = [];
					$getPrivileges = [];
					$roleId = !empty($getUser['user_roles'][0]['role_id'])
					? to_int($getUser['user_roles'][0]['role_id']) : 0;
					//get role type
					$userRoleInit = new UserRole();
					$roleTypeData = $userRoleInit->select('is_default')->where('xe_id', $getUser['user_roles'][0]['role_id'])->first();
					$roleTypeData = json_clean_decode($roleTypeData, true);
					$roleType = ($roleTypeData['is_default'] == 0) ? 'agent' : 'operator'; 

					if ($roleId > 1) {
						//Get user privileges and it's sub privileges
						$userModulePrivilegeRelInit = new UserModulePrivilegeRel();
						$privilegesData = $userModulePrivilegeRelInit
						->join('user_privileges', 
							'user_module_privilege_rel.privilege_id', 
							'=', 
							'user_privileges.xe_id')
						->select('user_privileges.module_name as name', 'user_module_privilege_rel.privilege_id', 'user_module_privilege_rel.xe_id')
						->where('user_module_privilege_rel.user_id', $getUser['xe_id']);
						if ($privilegesData->count() > 0) {
							$privilegesDataArr = $privilegesData->get();
							$enabledMainMenu = [];
							$productionHubArr = [7,8,9,10,11];
							foreach ($privilegesDataArr as $data) {
								if (in_array($data['privilege_id'], $productionHubArr)) {
									if (!in_array('Production Hub', $enabledMainMenu)) {
										$enabledMainMenu[] = 'Production Hub';
									}
								} else {
									$enabledMainMenu[] = $data['name'];
								}
								$tempPrivilegesData = $data;
								$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel();
								$subModuleData = $userSubModulePrivilegeRelInit
								->join('privileges_sub_modules', 
									'user_sub_module_privilege_rel.action_id' , 
									'=', 
									'privileges_sub_modules.xe_id')
								->select('privileges_sub_modules.xe_id', 'privileges_sub_modules.type as name', 'privileges_sub_modules.slug', 'privileges_sub_modules.comments')
								->where('user_module_privilege_id', $data['xe_id']);
								$subModuleDataArr = $subModuleData->get();
								$tempPrivilegesData['actions'] = $subModuleDataArr;
								array_push($privileges, $tempPrivilegesData);
							}
						}
					}
					$storeList = $this->getStoreList($getUser['xe_id']);
					$token = array(
						"iss" => isset($getUser['email'])
						? $getUser['email'] : "INKXE", // Issuer
						"aud" => "http://inkxe.com", // Audience
						"iat" => date_time(
							'today', [], 'timestamp'
						), // Issued-at time
						"exp" => date_time('add', ['days' => 60], 'timestamp'),
						"data" => [
							"user_id" => isset($getUser['xe_id'])
							? $getUser['xe_id'] : 0,
						]
					);

					$getJWTSecret = get_app_settings('jwt_secret');
					$jwtObj = new JWT();
					$jwt = $jwtObj->encode($token, $getJWTSecret);

					$languageInit = new Language();
					$language = $languageInit->select('name')->where(['type' => 'admin', 'is_default' => 1])->first();
					$jsonResponse = [
						'status' => 1,
						'message' => "Login Successfull",
						'jwt' => $jwt,
						'expired_at' => $token['exp'],
						'role_id' => $roleId,
						'role_type' => $roleType,
						'user_id' => $getUser['xe_id'],
						'user_name' => $getUser['name'],
						'enabled_main_menu' => $enabledMainMenu,
						'privileges' => $privileges,
						'language' => $language['name'],
						'store_list' => $storeList,
					];

					if ($getUser['pwd_update_status'] === 0) {
						$currentDate = date('Y/m/d');
						$updatedDate = date('Y/m/d', strtotime($getUser['pwd_reset_date']));
						if ($currentDate === $updatedDate || $currentDate > $updatedDate) {
							$jsonResponse['is_notify'] = 1;
						} else {
							$jsonResponse['is_notify'] = 0;
						}
					}

				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Post: Update password reset date
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   22 May 2020
	 * @return All Privileges List
	 */
	public function updatePasswordReset($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Reset Password', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();

		if (!empty($allPostPutVars['email'])) {
			$userObj = new User();
			$newDate = date('Y/m/d', strtotime("+2 days"));
			$userObj->where('email', $allPostPutVars['email'])
				->update(['pwd_reset_date' => $newDate]);
			$jsonResponse = [
				'status' => 0,
				'message' => message('Reset Password', 'done'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Post: Verify Password
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   07 Feb 2020
	 * @return All Privileges List
	 */
	public function verifyPassword($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => 'The password is wrong. Please provide the right one.',
		];

		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();
		$userInit = new User();
		$getOldPassword = $userInit->select('password')
			->where('store_id', $getStoreDetails['store_id'])
			->where('xe_id', $allPostPutVars['user_id'])
			->first();

		if (password_verify(
			$allPostPutVars['password'], $getOldPassword['password']
		)
		) {
			$jsonResponse = [
				'status' => 1,
				'message' => 'Password is correct.',
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of Privileges
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   06 Jan 2020
	 * @return All Privileges List
	 */
	public function getPrivileges($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Privileges', 'not_found'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$privilegeInit = new Privileges();
		$privileges = $privilegeInit->where('status', '1');
		$privileges->select('xe_id', 'store_id', 'module_name');
		$privileges->where('store_id', $getStoreDetails['store_id']);
		$privileges->orderBy('module_name', 'asc');
		$finalPrivilegesData = [];
		$privilegesData = $privileges->get();
		if (count($privilegesData) > 0) {
			foreach ($privilegesData as $privileges) {
				$tempPrivilegesData = $privileges;
				$privilegesSubModulesInit = new PrivilegesSubModules();
				$privilegesType = $privilegesSubModulesInit
					->select('xe_id', 'type as name', 'slug', 'comments')
					->where('user_privilege_id', $privileges['xe_id']);
				$privilegesTypeData = [];
				if ($privilegesType->count() > 0) {
					$privilegesTypeData = $privilegesType->orderBy('type', 'asc')->get();
					$privilegesTypeData = json_clean_decode($privilegesTypeData, true);
				}
				$tempPrivilegesData['actions'] = $privilegesTypeData;
				array_push($finalPrivilegesData, $tempPrivilegesData);
			}
			
			$jsonResponse = [
				'status' => 1,
				'records' => count($privilegesData),
				'data' => $finalPrivilegesData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of user roles/ a single user role
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   06 Jan 2020
	 * @return All User Role List
	 */
	public function getUserRoles($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('User Role', 'not_found'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);

		$userRoleInit = new UserRole();
		$userRoles = $userRoleInit->with('privileges')
			->where('xe_id', '<>', 1);

		// Condition for single user role
		if (!empty($args['id'])) {
			$userRoles->where('xe_id', $args['id']);
		}
		if ($userRoles->count() > 0) {
			$allUserRoles = $userRoles->orderBy('xe_id', 'desc')->get();
			$userRoles = [];
			foreach ($allUserRoles as $key => $userRole) {
				$finalPrivilegesData = [];
				foreach ($userRole['privileges'] as $data) {
					$tempPrivilegesData = $data;
					$privilegesSubModulesRelInit = new PrivilegesSubModulesRel();
					$typeData = $privilegesSubModulesRelInit
						->join('privileges_sub_modules', 
						'privileges_sub_modules_rel.privileges_sub_module_id',
						'=',
						'privileges_sub_modules.xe_id')
						->select('privileges_sub_modules.xe_id', 'privileges_sub_modules.type')
					->where('privileges_sub_modules_rel.privilege_rel_id', $data['xe_id']);
					$typeData = $typeData->get();
					$tempPrivilegesData['enabled_actions'] = $typeData;
					array_push($finalPrivilegesData, $tempPrivilegesData);
				}
				$userRoles[$key] = [
					'id' => $userRole['xe_id'],
					'store_id' => $userRole['store_id'],
					'role_name' => $userRole['role_name'],
					'privileges' => $finalPrivilegesData,
					'is_default' => $userRole['is_default']
				];
			}

			$jsonResponse = [
				'status' => 1,
				'records' => !empty($args['id']) ? 1 : count($userRoles),
				'data' => !empty($args['id']) ? $userRoles[0] : $userRoles,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save user role
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   06 Jan 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUserRole($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Role', 'exist'),
		];
		$allPostPutVars = $request->getParsedBody();
		$roleName = $allPostPutVars['role_name'];
		if ($roleName != "") {
			$userRoleInit = new UserRole();
			$rows = $userRoleInit->where('role_name', '=', $roleName)
				->get();
			if ($rows->count() == 0) {
				$getStoreDetails = get_store_details($request);
				$userRoleData = [
					'store_id' => 1,
					'role_name' => $roleName,
				];
				$saveUserRole = new UserRole($userRoleData);
				if ($saveUserRole->save()) {
					$jsonResponse = [
						'status' => 1,
						'message' => message('User Role', 'saved'),
					];
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * PUT: Update user role
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   06 Jan 2020
	 * @return json response wheather data is updated or any error occured
	 */
	public function updateUserRole($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Role', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$roleName = $allPostPutVars['role_name'];
		// Id should be greater than 1 because it is the super admin role, so
		// can't be updated.
		if (isset($args['id']) && $args['id'] > 1) {
			$userRoleInit = new UserRole();
			$getUserRole = $userRoleInit->where('role_name', '=', $roleName)
				->where('xe_id', '<>', $args['id']);
			if ($getUserRole->count() == 0) {
				$updateData = ['role_name' => $roleName];
				try {
					$userRoleInit->where('xe_id', '=', $args['id'])
						->update($updateData);
					$jsonResponse = [
						'status' => 1,
						'message' => message('User Role', 'updated'),
					];
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'users', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Update user role',
							],
						]
					);
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save user role privileges
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   07 Jan 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUserRolePrivileges($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Role', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (!empty($allPostPutVars['data'])) {
			$privileges = json_clean_decode($allPostPutVars['data'], true);
			$userRoleId = $args['id'];
			try {
				$userPrivRelInit = new UserRolePrivilegesRel();
				//delete user role privilege and it's sub privilege
				$allUserPriv = $userPrivRelInit->select('xe_id')->where('role_id', $userRoleId);
				if ($allUserPriv->count() > 0) {
					$allUserPrivData = json_clean_decode($allUserPriv->get(), true);
					foreach($allUserPrivData as $privData) {
						$privilegesSubModulesRelInit = new PrivilegesSubModulesRel();
						$checkData = $privilegesSubModulesRelInit->where('privilege_rel_id', $privData['xe_id']);
						if ($checkData->count() > 0) {
							$checkData->delete();
						}
					}
					$userPrivRelInit->where('role_id', $userRoleId)->delete();
				}
				foreach ($privileges as $privillege) {
					if (!empty($privillege)) {
						$postData = [
							'role_id' => $userRoleId,
							'privilege_id' => $privillege['id'],
							'privilege_type' => $privillege['privilege_type']
						];
						
						$savePrivilageInit = new UserRolePrivilegesRel($postData);
						$savePrivilageInit->save();
						$lastId = $savePrivilageInit->xe_id;
						if (!empty($privillege['actions'])) {
							foreach ($privillege['actions'] as $subItems) {
								$saveData = [
									"privilege_rel_id" => $lastId,
									"privileges_sub_module_id" => $subItems['id']
								];
								$privilegesSubModulesRelInit = new PrivilegesSubModulesRel($saveData);
								$privilegesSubModulesRelInit->save();
							}
						}
					}
				}
				$jsonResponse = [
					'status' => 1,
					'message' => message('User Role', 'updated'),
				];
			} catch (\Exception $e) {
				$serverStatusCode = EXCEPTION_OCCURED;
				create_log(
					'users', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Saving user role privileges',
						],
					]
				);
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * DELETE: Delete user roles along with its relationship with privileges
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   07 jan 2020
	 * @return json response wheather data is deleted or not
	 */
	public function deleteUserRole($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Role', 'error'),
		];
		if (isset($args['id']) && $args['id'] != '') {
			$getDeleteIds = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds);
			if (!empty($getDeleteIdsToArray) && !in_array(1, $getDeleteIdsToArray)) {
				$userRoleInit = new UserRole();
				$count = $userRoleInit->whereIn('xe_id', $getDeleteIdsToArray)
					->count();
				if ($count > 0) {
					try {
						$userRoleInit->whereIn('xe_id', $getDeleteIdsToArray)
							->delete();
						$userPrivRelInit = new UserRolePrivilegesRel();
						$userPrivRelInit->whereIn('role_id', $getDeleteIdsToArray)
							->delete();
						$jsonResponse = [
							'status' => 1,
							'message' => message('User Role', 'deleted'),
						];
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						create_log(
							'users', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Delete user role',
								],
							]
						);
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of security questions
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   7 Feb 2020
	 * @return All Security Question
	 */
	public function getSecurityQuestions($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Security Questions', 'not_found'),
		];
		// Get User Details
        $userInit = new User();
        $getUsers = $userInit->with('user_roles');
        $userData = $getUsers->where('name', '=', 'Super Admin')->first();
        $languageSelected = $userData->language_selected;
		// Get Language Details
        $languagePath = path('abs', 'language') . 'admin/' . 'lang_' . $languageSelected . '.json';
        $langFile = file_get_contents($languagePath);
        $langData = json_decode($langFile, true);
		$questionData = [];
        if (count($langData['security-questions']) > 0) {
            $i = 0;
            $j = 1;
            foreach ($langData['security-questions'] as $value) {
                $questionData[$i++] = array("xe_id" => $j++, "question" => $value);
            }
        } else {
            // Get Store Specific Details from helper
            $getStoreDetails = get_store_details($request);
            $store_id = 1;
            $questionInit = new SecurityQuestion();
            $questionData = $questionInit->select('xe_id', 'question')
                ->where('xe_id', '>', 0)
                ->where('store_id', '=', $store_id)
                ->get();
        }
		$jsonResponse = [
			'status' => 1,
			'data' => $questionData,
		];

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of users/ a single user
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   076 Jan 2020
	 * @return All User List
	 */
	public function getUsers($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('User Role', 'not_found'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$offset = 0;
		$userInit = new User();

		$getUsers = $userInit->with('user_roles');
		if (!empty($args) && $args['id'] > 0) {
			$userId = $args['id'];
			//For single User data
			$userData = $getUsers->where('xe_id', '=', $userId)->first();
			$storeList = $this->getStoreList($userId);
			if ($userData['user_roles'][0]['role_id'] > 1) {
				$users = [
					'id' => $userData['xe_id'],
					'name' => $userData['name'],
					'email' => $userData['email'],
					'role_id' => (!empty($userData['user_roles'][0]['role_id'])
						&& $userData['user_roles'][0]['role_id'] > 0)
					? $userData['user_roles'][0]['role_id'] : null,
					'store_list' => !empty($storeList) ? $storeList : [],
				];
			} else {
				$users = [
					'id' => $userData['xe_id'],
					'name' => $userData['name'],
					'email' => $userData['email'],
					'first_question_id' => $userData['first_question_id'],
					'second_question_id' => $userData['second_question_id'],
					'role_id' => (!empty($userData['user_roles'][0]['role_id'])
						&& $userData['user_roles'][0]['role_id'] > 0)
					? $userData['user_roles'][0]['role_id'] : null,
					'store_list' => [],
				];
			}

			$jsonResponse = [
				'status' => 1,
				'data' => [
					$users,
				],
			];
		} else {
			//All Filter columns from url
			$page = $request->getQueryParam('page');
			$perpage = $request->getQueryParam('perpage');
			$sortBy = !empty($request->getQueryParam('sortby'))
			&& $request->getQueryParam('sortby') != ""
			? $request->getQueryParam('sortby') : 'name';
			$order = !empty($request->getQueryParam('order'))
			&& $request->getQueryParam('order') != ""
			? $request->getQueryParam('order') : 'asc';
			$name = $request->getQueryParam('name');
			$users = [];

			// For multiple User data
			$getUsers->select('xe_id', 'name', 'email');

			// Searching as per name, category name & tag name
			if (isset($name) && $name != "") {
				$name = '\\' . $name;
				$getUsers->where(
					function ($query) use ($name) {
						$query->where('name', 'LIKE', '%' . $name . '%')
							->orWhere('email', 'LIKE', '%' . $name . '%');
					}
				);
			}
			// Total records including all filters
			$getTotalPerFilters = $getUsers->count();
			// Pagination Data
			if (isset($page) && $page != "") {
				$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
				$offset = $totalItem * ($page - 1);
				$getUsers->skip($offset)->take($totalItem);
			}
			// Sorting All records by column name and sord order parameter
			if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
				$getUsers->orderBy($sortBy, $order);
			}
			$UserData = $getUsers->get();
			foreach ($UserData as $key => $user) {
				if (isset($user['user_roles'][0]['role_id'])
					&& $user['user_roles'][0]['role_id'] != 1
				) {
					//get role type
					$userRoleInit = new UserRole();
					$roleTypeData = $userRoleInit->select('is_default')->where('xe_id', $user['user_roles'][0]['role_id'])->first();
					$roleTypeData = json_clean_decode($roleTypeData, true);
					$roleType = ($roleTypeData['is_default'] == 0) ? 'agent' : 'operator'; 

					$finalPrivilegesData = [];
					$userModulePrivilegeRelInit = new UserModulePrivilegeRel();
					$privilegeData = $userModulePrivilegeRelInit
					->join('user_privileges', 
							'user_module_privilege_rel.privilege_id',
							'=',
							'user_privileges.xe_id')
					->select('user_module_privilege_rel.role_type', 'user_module_privilege_rel.xe_id as rel_id', 'user_privileges.xe_id', 'user_privileges.module_name')
					->where('user_module_privilege_rel.user_id', $user['xe_id']);
					if ($privilegeData->count() > 0) {
						$privilegeDataArr = json_clean_decode($privilegeData->get(), true);
						foreach ($privilegeDataArr as  $privileges) {
							$tempPrivilegesData = $privileges;
							$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel();
							$subModuleData = $userSubModulePrivilegeRelInit
							->join('privileges_sub_modules', 
							'user_sub_module_privilege_rel.action_id',
							'=',
							'privileges_sub_modules.xe_id')
							->select('privileges_sub_modules.xe_id', 'privileges_sub_modules.type', 'privileges_sub_modules.slug', 'privileges_sub_modules.comments')
							->where('user_sub_module_privilege_rel.user_module_privilege_id', $privileges['rel_id']);
							$subModuleDataArr = $subModuleData->get();
							$tempPrivilegesData['enabled_actions'] = $subModuleDataArr;
							array_push($finalPrivilegesData, $tempPrivilegesData);
						}
					}
					$users[] = [
						'id' => $user['xe_id'],
						'name' => $user['name'],
						'email' => $user['email'],
						'role_id' => (!empty($user['user_roles'][0]['role_id'])
							&& $user['user_roles'][0]['role_id'] > 0)
						? $user['user_roles'][0]['role_id'] : null,
						'role_type' => $roleType,
						'privileges' => $finalPrivilegesData,
					];
				}
			}

			$jsonResponse = [
				'status' => 1,
				'records' => count($users),
				'total_records' => $getTotalPerFilters,
				'data' => $users,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save user
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   07 Jan 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUser($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'exist'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (!empty($allPostPutVars['data'])) {
			$getAllFormData = json_clean_decode($allPostPutVars['data'], true);
			if ($getAllFormData['role_id'] > 1) {
				$user = new User();
				//Check email, whether exist or not.
				$getUserFromDb = $user->where(
					'email', '=', $getAllFormData['email']
				);
				if ($getUserFromDb->count() == 0) {
					$getStoreDetails = get_store_details($request);
					$getPostPutData = [
						'name' => $getAllFormData['name'],
						'email' => $getAllFormData['email'],
						'store_id' => $getStoreDetails['store_id'],
						'password' => password_hash(
							$getAllFormData['password'], PASSWORD_BCRYPT
						),
						'avatar' => "",
						'created_at' => date('Y-m-d H:i:s'),
					];
					$saveUser = new User($getPostPutData);
					if ($saveUser->save()) {
						//Set user role
						$userRoleRelData = [
							'user_id' => $saveUser->xe_id,
							'role_id' => $getAllFormData['role_id'],
						];
						$saveUserRoleRel = new UserRoleRel($userRoleRelData);
						$saveUserRoleRel->save();
						//Get the privileges of perticular role
						 $userRolePrivilegesRelInit = new UserRolePrivilegesRel();
						 $privilegesRelData = $userRolePrivilegesRelInit->where('role_id', $getAllFormData['role_id']); 
						 if ($privilegesRelData->count() > 0) {
						 	$privilegesRelDataArr = $privilegesRelData->get();
						 	$privilegesRelDataArr = json_clean_decode($privilegesRelDataArr, true);
						 	$finalPrivilegeData = [];
						 	foreach ($privilegesRelDataArr as $privilege) {
						 		//For existing user update privilege_type column
						 		if ($privilege['privilege_type'] == '') {
						 			$userRoleInit = new UserRole();
									$roleTypeData = $userRoleInit->select('is_default')->where('xe_id', $getAllFormData['role_id'])->first();
									$roleTypeData = json_clean_decode($roleTypeData, true);
									$roleType = ($roleTypeData['is_default'] == 0) ? 'agent' : 'operator'; 
									//$userInit = new User();
									$userRolePrivilegesRelInit->where('xe_id', $privilege['xe_id'])
											->update(['privilege_type' => $roleType]);
						 		}
						 		$typeDataArr = [];
								if ($privilege['privilege_type'] == 'agent') {
									$privilegesSubModulesRelInit = new PrivilegesSubModulesRel();
									$typeData = $privilegesSubModulesRelInit->select('privileges_sub_module_id')->where('privilege_rel_id', $privilege['xe_id']);
									$typeDataArr = $typeData->get();
									$typeDataArr = json_clean_decode($typeDataArr, true);
								}
								$tempPrivilegeData = [
									'user_id' => $saveUser->xe_id,
									'role_id' => $getAllFormData['role_id'],
									'role_type' => $privilege['privilege_type'],
									'privilege_id' => $privilege['privilege_id'], 
									'actions' => $typeDataArr,
								];
								array_push($finalPrivilegeData, $tempPrivilegeData);
							}
							if (!empty($finalPrivilegeData)) {
								foreach ($finalPrivilegeData as $data) {
									$actionDataArr = $data['actions'];
									unset($data['actions']);
									//save data in user_module_privilege_rel
									$userModulePrivilegeRelInit = new UserModulePrivilegeRel($data);
									if ($userModulePrivilegeRelInit->save()) {
										foreach ($actionDataArr as $actions) {
											$saveActionData = [
												'user_module_privilege_id' => $userModulePrivilegeRelInit->xe_id,
												'action_id' => $actions['privileges_sub_module_id']
											];
											$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel($saveActionData);
											$userSubModulePrivilegeRelInit->save();
										}
									}	
								}
						 	}
					 	}
						if (!empty($getAllFormData['store_list'])) {
							$this->saveUserStoreRelations($saveUser->xe_id, $getAllFormData['store_list']);
						}
						$jsonResponse = [
							'status' => 1,
							'message' => message('User', 'saved'),
						];
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * PUT: Update user
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   06 Jan 2020
	 * @return json response wheather data is updated or any error occured
	 */
	public function updateUser($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'exist'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (isset($args['id']) && $args['id'] > 0) {
			$getAllFormData = json_clean_decode($allPostPutVars['data'], true);
			if (!empty($getAllFormData)) {
				$userInit = new User();
				$getUserData = $userInit->where(
					'email', '=', $getAllFormData['email']
				)
					->where('xe_id', '<>', $args['id']);
				if ($getUserData->count() == 0) {
					try {
						$updateData = [
							'name' => $getAllFormData['name'],
							'email' => $getAllFormData['email'],
						];

						if (isset($getAllFormData['first_answer'])) {
							$updateData += ['first_answer' => $getAllFormData['first_answer']];
						}
						if (isset($getAllFormData['second_answer'])) {
							$updateData += ['second_answer' => $getAllFormData['second_answer']];
						}
						$userInit = new User();
						$userInit->where('xe_id', '=', $args['id'])
							->update($updateData);
						// Dete user role relation table and inserting data again
						$userRoleRelInit = new UserRoleRel();

						$previousData = $userRoleRelInit->where('user_id', $args['id'])->first();
						$previousData = json_clean_decode($previousData, true);
						$previousRoleId = $previousData['role_id'];

						$userRoleRelInit->where('user_id', $args['id'])->delete();
						$userRoleRelData = [
							'user_id' => $args['id'],
							'role_id' => $getAllFormData['role_id'],
						];
						$saveUserRoleRel = new UserRoleRel($userRoleRelData);
						$saveUserRoleRel->save();

						//save the previllage data
						if ($previousRoleId != $getAllFormData['role_id']) {
							//delete user privileges and it's sub privileges
							$userModulePrivilegeRelInit = new UserModulePrivilegeRel();
							$modulePrivilegeRelData = $userModulePrivilegeRelInit->where('user_id', $args['id']);
							if ($modulePrivilegeRelData->count() > 0) {
								$modulePrivilegeRelDataArr = $modulePrivilegeRelData->get();
								foreach ($modulePrivilegeRelDataArr as $data) {
									$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel();
									$checkData = $userSubModulePrivilegeRelInit->where('user_module_privilege_id', $data['xe_id']);
									if ($checkData->count() > 0) {
										$checkData->delete();
									}
								}
								$userModulePrivilegeRelInit->where('user_id', $args['id'])->delete();
							}
							//Get the privileges of perticular role
							 $userRolePrivilegesRelInit = new UserRolePrivilegesRel();
							 $privilegesRelData = $userRolePrivilegesRelInit->where('role_id', $getAllFormData['role_id']); 
							 if ($privilegesRelData->count() > 0) {
							 	$privilegesRelDataArr = $privilegesRelData->get();
							 	$privilegesRelDataArr = json_clean_decode($privilegesRelDataArr, true);
							 	$finalPrivilegeData = [];
							 	foreach ($privilegesRelDataArr as $privilege) {
							 		//For existing user update privilege_type column
							 		if ($privilege['privilege_type'] == '') {
							 			$userRoleInit = new UserRole();
										$roleTypeData = $userRoleInit->select('is_default')->where('xe_id', $getAllFormData['role_id'])->first();
										$roleTypeData = json_clean_decode($roleTypeData, true);
										$roleType = ($roleTypeData['is_default'] == 0) ? 'agent' : 'operator'; 
										$userRolePrivilegesRelInit->where('xe_id', $privilege['xe_id'])
												->update(['privilege_type' => $roleType]);
							 		}
							 		$typeDataArr = [];
									if ($privilege['privilege_type'] == 'agent') {
										$privilegesSubModulesRelInit = new PrivilegesSubModulesRel();
										$typeData = $privilegesSubModulesRelInit->select('privileges_sub_module_id')->where('privilege_rel_id', $privilege['xe_id']);
										$typeDataArr = $typeData->get();
										$typeDataArr = json_clean_decode($typeDataArr, true);
									}
									$tempPrivilegeData = [
										'user_id' => $args['id'],
										'role_id' => $getAllFormData['role_id'],
										'role_type' => $privilege['privilege_type'],
										'privilege_id' => $privilege['privilege_id'], 
										'actions' => $typeDataArr,
									];
									array_push($finalPrivilegeData, $tempPrivilegeData);
								}
								if (!empty($finalPrivilegeData)) {
									foreach ($finalPrivilegeData as $data) {
										$actionDataArr = $data['actions'];
										unset($data['actions']);
										//save data in user_module_privilege_rel
										$userModulePrivilegeRelInit = new UserModulePrivilegeRel($data);
										if ($userModulePrivilegeRelInit->save()) {
											foreach ($actionDataArr as $actions) {
												$saveActionData = [
													'user_module_privilege_id' => $userModulePrivilegeRelInit->xe_id,
													'action_id' => $actions['privileges_sub_module_id']
												];
												$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel($saveActionData);
												$userSubModulePrivilegeRelInit->save();
											}
										}	
									}
							 	}
						 	}
					 	}

						if (!empty($getAllFormData['password'])) {
							$password = password_hash(
								$getAllFormData['password'], PASSWORD_BCRYPT
							);
							$updateData = ['password' => $password];
							$userInit = new User();
							$userInit->where('xe_id', '=', $args['id'])
								->update($updateData);
						}
						if (!empty($getAllFormData['store_list'])) {
							$this->saveUserStoreRelations($args['id'], $getAllFormData['store_list']);
						}
						$jsonResponse = [
							'status' => 1,
							'message' => message('User', 'updated'),
						];
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						create_log(
							'users', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Updating a user',
								],
							]
						);
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * DELETE: Delete user along with its relationship with user role and privileges
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   07 jan 2020
	 * @return json response wheather data is deleted or not
	 */
	public function deleteUser($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Users', 'error'),
		];
		if (isset($args['id']) && $args['id'] != "") {
			$getDeleteIds = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds);
			if (!empty($getDeleteIdsToArray)) {
				// Checking whether user ia a super admin or normal user.
				$userRoleRelInit = new UserRoleRel();
				$admin = $userRoleRelInit->where('role_id', '=', 1)->get();
				if (!in_array($admin[0]['user_id'], $getDeleteIdsToArray)) {
					$userInit = new User();
					$userCount = $userInit->whereIn('xe_id', $getDeleteIdsToArray)
						->count();
					if ($userCount > 0) {
						try {
							$userInit->whereIn('xe_id', $getDeleteIdsToArray)
								->delete();
							$userPrivilegesRelObj = new UserPrivilegesRel();
							$userRoleRelInit = new UserRoleRel();
							$userPrivilegesRelObj->whereIn(
								'user_id', $getDeleteIdsToArray
							)
								->delete();
							$userRoleRelInit->whereIn(
								'user_id', $getDeleteIdsToArray
							)
								->delete();
							$this->deleteUserStore($getDeleteIdsToArray);
							$jsonResponse = [
								'status' => 1,
								'message' => message('User', 'deleted'),
							];
						} catch (\Exception $e) {
							$serverStatusCode = EXCEPTION_OCCURED;
							create_log(
								'users', 'error',
								[
									'message' => $e->getMessage(),
									'extra' => [
										'module' => 'Deleting a user',
									],
								]
							);
						}
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save user privileges
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author ramasankarm@riaxe.com
	 * @date   07 Jan 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUserPrivileges($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Privileges', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (!empty($allPostPutVars['data'])) {
			$privileges = json_clean_decode($allPostPutVars['data'], true);
			$userId = $args['id'];
			try {
				//delete user privileges and it's sub privileges
				$userModulePrivilegeRelInit = new UserModulePrivilegeRel();
				$modulePrivilegeRelData = $userModulePrivilegeRelInit->where('user_id', $userId);
				if ($modulePrivilegeRelData->count() > 0) {
					$modulePrivilegeRelDataArr = $modulePrivilegeRelData->get();
					foreach ($modulePrivilegeRelDataArr as $data) {
						$userSubModulePrivilegeRelInit = new UserSubModulePrivilegeRel();
						$checkData = $userSubModulePrivilegeRelInit->where('user_module_privilege_id', $data['xe_id']);
						if ($checkData->count() > 0) {
							$checkData->delete();
						}
					}
					$userModulePrivilegeRelInit->where('user_id', $userId)->delete();
				}
				//save the new privileges and it's sub privileges
				foreach ($privileges as $privilege) {
					if (!empty($privilege)) {
						$postData = [
							'user_id' => $userId,
							'role_id' => $privilege['role_id'],
							'role_type' => $privilege['privilege_type'],
							'privilege_id' => $privilege['id']
						];
						
						$savePrivilageInit = new UserModulePrivilegeRel($postData);
						$savePrivilageInit->save();
						$lastId = $savePrivilageInit->xe_id;
						if (!empty($privilege['actions'])) {
							foreach ($privilege['actions'] as $subItems) {
								$saveData = [
									"user_module_privilege_id" => $lastId,
									"action_id" => $subItems['id']
								];
								$subModulePrivilegeRel = new UserSubModulePrivilegeRel($saveData);
								$subModulePrivilegeRel->save();
							}
						}
					}
				}
				$jsonResponse = [
					'status' => 1,
					'message' => 'User Privilege saved successfully',
				];
			} catch (\Exception $e) {
				$serverStatusCode = EXCEPTION_OCCURED;
				create_log(
					'users', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Saving user privileges',
						],
					]
				);
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Bulk User Type Save
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   25 Feb 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUserTypes($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Types', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (!empty($allPostPutVars['data'])) {
			$userIds = json_clean_decode($allPostPutVars['data'], true);
			$roleId = $args['id'];
			try {

				foreach ($userIds as $userId) {
					$userRoleRelInit = new UserRoleRel();
					$userRoleRelInit->where('user_id', $userId)
						->update(['role_id' => $roleId]);
				}
				$jsonResponse = [
					'status' => 1,
					'message' => message('User roles', 'updated'),
				];
			} catch (\Exception $e) {
				$serverStatusCode = EXCEPTION_OCCURED;
				create_log(
					'users', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Saving user type',
						],
					]
				);
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Validate User Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   12 May 2020
	 * @return json response wheather user is valid or not
	 */
	public function validateDetails($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();

		$userInit = new User();
		if (isset($allPostPutVars['first_answer']) && $allPostPutVars['first_answer'] != "" && isset($allPostPutVars['email']) && $allPostPutVars['email'] != "") {
			$getUserDetails = $userInit->where(['first_answer' => $allPostPutVars['first_answer'], 'email' => $allPostPutVars['email']])->with('hasQuestionTwo')->first();
			if (!empty($getUserDetails)) {
				$getUserDetails = $getUserDetails->toArray();
				$jsonResponse = [
					'status' => 1,
					'data' => [
						'second_question_id' => $getUserDetails['second_question_id'],
						'second_question' => $getUserDetails['has_question_two']['question'],
					],
				];
			}
		}

		if (isset($allPostPutVars['second_answer']) && $allPostPutVars['second_answer'] != "" && isset($allPostPutVars['email']) && $allPostPutVars['email'] != "") {
			$getUserDetails = $userInit->where(['second_answer' => $allPostPutVars['second_answer'], 'email' => $allPostPutVars['email']])->first();
			if (!empty($getUserDetails)) {
				$getUserDetails = $getUserDetails->toArray();
				$jsonResponse = [
					'status' => 1,
					'data' => [
						'status' => 1,
						'message' => message('Question', 'done'),
					],
				];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Validate User Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   12 May 2020
	 * @return json response wheather user is valid or not
	 */
	public function validateEmail($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();

		$userInit = new User();
		if (isset($allPostPutVars['email']) && $allPostPutVars['email'] != "") {
			$getUserDetails = $userInit->with('user_roles')->where('email', $allPostPutVars['email'])->with('hasQuestionOne')->first();
			if (!empty($getUserDetails)) {
				$getUserDetails = $getUserDetails->toArray();
				if ($getUserDetails['user_roles'][0]['role_id'] === 1) {
					$token = array(
						"iss" => isset($getUserDetails['email'])
						? $getUserDetails['email'] : "INKXE", // Issuer
						"aud" => "http://inkxe.com", // Audience
						"iat" => date_time(
							'today', [], 'timestamp'
						), // Issued-at time
						"exp" => date_time('add', ['days' => 60], 'timestamp'),
						"data" => [
							"user_id" => isset($getUserDetails['xe_id'])
							? $getUserDetails['xe_id'] : 0,
						]
					);
					$getJWTSecret = get_app_settings('jwt_secret');
					$jwtObj = new JWT();
					$jwt = $jwtObj->encode($token, $getJWTSecret);
					$jsonResponse = [
						'status' => 1,
						'role_id' => $getUserDetails['user_roles'][0]['role_id'],
						'data' => [
							'jwt' => $jwt,
							'role_id' => $getUserDetails['user_roles'][0]['role_id'],
							'first_question' => $getUserDetails['has_question_one']['question'],
						],
					];
				} else {
					$jsonResponse = [
						'status' => 1,
						'data' => [
							'role_id' => $getUserDetails['user_roles'][0]['role_id'],
						],
					];
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Reset Password
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   12 May 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function updatePassword($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$userInit = new User();
		if (isset($allPostPutVars['password']) && $allPostPutVars['password'] != "") {

			$password = password_hash(
				$allPostPutVars['password'], PASSWORD_BCRYPT
			);
			$updateData = ['password' => $password];
			$userInit->where('email', $allPostPutVars['email'])
				->update($updateData);
			$jsonResponse = [
				'status' => 1,
				'message' => message('Password', 'done'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Reset Password
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   12 May 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function resetPassword($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$getStoreDetails = get_store_details($request);
		$userInit = new User();

		if (isset($allPostPutVars['old_password']) && $allPostPutVars['old_password'] != "") {
			$getOldPassword = $userInit->select('password')
				->where('store_id', $getStoreDetails['store_id'])
				->where('email', $allPostPutVars['email'])
				->first();

			if (password_verify($allPostPutVars['old_password'], $getOldPassword['password'])) {
				$newPassword = password_hash(
					$allPostPutVars['new_password'], PASSWORD_BCRYPT
				);
				$updateData = ['password' => $newPassword];
				$userInit->where('email', $allPostPutVars['email'])
					->update($updateData);
				$jsonResponse = [
					'status' => 1,
					'message' => message('Password', 'done'),
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Password Does not match.',
				];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST: Assign user to store
	 *
	 * @param $userId
	 * @param $storeIds
	 *
	 * @author soumays@riaxe.com
	 * @date   29 May 2020
	 * @return int
	 */
	public function saveUserStoreRelations($userId, $storeIds) {
		$storeCount = 0;
		// Delete user store relation table and inserting data again
		$userStoreRelInit = new UserStoreRel();
		$storeCount = $userStoreRelInit->whereIn('user_id', [$userId])->count();
		if ($storeCount > 0) {
			$userStoreRelInit->whereIn('user_id', [$userId])->delete();
		}
		foreach ($storeIds as $storeId) {
			$saveData = [
				'user_id' => $userId,
				'store_id' => $storeId,
			];
			$saveUserStore = new UserStoreRel($saveData);
			if ($saveUserStore->save()) {
				$storeCount++;
			}
		}
		return $storeCount;
	}
	/**
	 * Delete: Delete assign user to store
	 *
	 * @param $userIds
	 *
	 * @author soumays@riaxe.com
	 * @date   29 May 2020
	 * @return int
	 */
	public function deleteUserStore($userIds) {
		$deleteCount = 0;
		$userStoreRelInit = new UserStoreRel();
		$storeCount = $userStoreRelInit->whereIn('user_id', $userIds)->count();
		if ($storeCount > 0) {
			$userStoreRelInit->whereIn('user_id', $userIds)->delete();
			$deleteCount++;
		}
		return $deleteCount;
	}
	/**
	 * GET: Get store list
	 *
	 * @param $userId
	 *
	 * @author soumays@riaxe.com
	 * @date   29 May 2020
	 * @return int
	 */
	public function getStoreList($userId) {
		$storeIdList = array();
		$userStoreRelInit = new UserStoreRel();
		$getStoreIds = $userStoreRelInit->select('store_id')->where(['user_id' => $userId]);
		if ($getStoreIds > 0) {
			$getStoreResposne = $getStoreIds->get()->toArray();
			foreach ($getStoreResposne as $key => $value) {
				$storeIdList[$key] = $value['store_id'];
			}
		}
		return $storeIdList;
	}

	
}
