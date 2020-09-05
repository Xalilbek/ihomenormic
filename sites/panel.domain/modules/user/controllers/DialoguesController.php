<?php
namespace Controllers;

use Models\Cases;
use Models\DialogueMessages;
use Models\Dialogues;
use Models\DialogueUsers;
use Models\Users;

class DialoguesController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$id 		= $this->auth->getData()->id;
		$skip 		= (int)$this->request->get("skip");
		$limit 		= 20;

		$dialogues = DialogueUsers::find([
			[
				"user_id"	=> (int)$id,
				"status" => [
					'$ne'	=> 0
				]
			],
			"sort"	=> [
				"updated_at"	=> -1
			],
			"skip"	=> $skip,
			"limit"	=> $limit,
		]);

		$count = DialogueUsers::count([
			[
				"user_id"	=> (int)$id,
				"status" => [
					'$ne'	=> 0
				]
			],
		]);

		$dialogueIds = [];
		foreach($dialogues as $value)
			$dialogueIds[] = $this->mymongo->objectId($value->dialogue);

		$dialogueQuery = Dialogues::find([
			[
				"_id" => [
					'$in'	=> $dialogueIds
				]
			]
		]);

		$userIds = [];
		$usersArr = [];
		$dialoguesData = [];
		foreach($dialogueQuery as $value)
		{
			$usersArr = array_merge($usersArr, $value->users);
			$dialoguesData[(string)$value->_id] = $value;
		}
		$usersArr = array_unique($usersArr);
		foreach($usersArr as $value)
			$userIds[] = (int)$value;

		$users = [];
		$dialogueTitles = [];
		if(count($userIds) > 0)
		{
			$userQuery = Users::find([
				[
					"id"	=> [
						'$in'	=> $userIds
					]
				]
			]);

			//exit(count($userQuery)." asds");
			foreach($userQuery as $value)
			{
				$users[$value->id] = $value;
			}

			foreach($dialogueQuery as $dialogue)
			{
				foreach($dialogue->users as $userId)
				{
					if(count($dialogueTitles[(string)$dialogue->_id]) < 5 && (int)$id !== (int)$userId)
						$dialogueTitles[(string)$dialogue->_id][] = $users[$userId]->firstname." ".$users[$userId]->lastname;
				}
			}
		}

		$pagination = $this->lib->navigator($skip, $count, $limit, "?skip=");

		$this->view->setVar("dialogues", $dialogues);
		$this->view->setVar("dialoguesData", $dialoguesData);
		$this->view->setVar("dialogueTitles", $dialogueTitles);
		$this->view->setVar("pagination", $pagination);
	}

	public function addAction()
	{
		$error 			= false;
		$success 		= false;
		$message 		= $this->request->get("message");
		$moderators 	= $this->request->get("moderator");
		$citizens 		= $this->request->get("citizen");
		$employees 		= $this->request->get("employee");
		$partners 		= $this->request->get("partner");
		if((int)$this->request->get("save") == 1)
		{
			if (strlen(trim($message)) < 1 || strlen($message) > 10000)
			{
				$error = $this->lang->get("MessageError", "Message is wrong");
			}
			elseif (count($citizens) == 0 &&count($employees) == 0)
			{
				$error = $this->lang->get("ChooseMsgUser", "Please, Choose users to send message");
			}
			else
			{
				$users = [];
				$users[] = (int)$this->auth->getData()->id;
				foreach($citizens as $value)
					$users[] = (int)$value;
				foreach($moderators as $value)
					$users[] = (int)$value;
				foreach($employees as $value)
					$users[] = (int)$value;
				foreach($partners as $value)
					$users[] = (int)$value;
				$dialogue = Dialogues::getDialogue($users);

				$msgIns = [
					"dialogue"		=> (string)$dialogue->_id,
					"user"			=> (int)$this->auth->getData()->id,
					"body"			=> $message,
					"type"			=> "text",
					"is_deleted"	=> 0,
					"created_at"	=> $this->mymongo->getDate()
				];
				$msgId = DialogueMessages::insert($msgIns);

				Dialogues::update(
					[
						"_id"	=> $dialogue->_id,
					],
					[
						"updated_at"	=> $this->mymongo->getDate(),
						"message"		=> [
							"id"	=> (string)$msgId,
							"body"	=> strlen($message) > 50 ? substr($message, 0, 50)."...": $message,
							"type"	=> "text",
						]
					]);

				DialogueUsers::update(
					[
						"dialogue"	=> (string)$dialogue->_id,
						"status"	=> [
							'$gte'	=> 0
						],
						//"user_id"	=> ['$ne'	=> (int)$this->auth->getData()->id]
					],
					[
						"status"		=> 4,
						"updated_at"	=> $this->mymongo->getDate(),
					]
				);


				$success = $this->lang->get("SentSuccessfully", "Sent successfully");
			}
		}
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);


		$binds = [];
		if($this->auth->getData()->type == "citizen")
		{
			$binds["citizen"] = (int)$this->auth->getData()->id;
		}
		else
		{
			$binds["employee"] = (int)$this->auth->getData()->id;
		}

		$binds["is_deleted"] = 0;

		$data =  Cases::find([
			$binds,
			"limit"     => 100,
			"skip"      => 0,
		]);
		$citizenIds = [];
		$employeeIds = [];
		$contact_persons = [];
		foreach($data as $value){
			$citizenIds[] 	= (int)$value->citizen[0];
			$employeeIds[] 	= (int)$value->employee[0];
			$contact_persons[] 	= (int)$value->contact_person[0];
			if((int)$value->contact_person[1] > 0)
				$contact_persons[] 	= (int)$value->contact_person[1];
		}


		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));
		$this->view->setVar("citizens", Users::find([["id" => ['$in' => $citizenIds], "type" => "citizen", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("employees", Users::find([["id" => ['$in' => $employeeIds], "type" => "employee", "is_deleted" => ['$ne' => 1]]]));
		$this->view->setVar("contactPersons", Users::find([["id" => ['$in' => $contact_persons], "type" => "partner", "is_deleted" => ['$ne' => 1]]]));
	}

	public function messagesAction($did)
	{
		$dialogue = Dialogues::findById($did);

		$users = [];
		$dialogueTitle = [];
		if($dialogue)
		{
			$dialogueUsers = DialogueUsers::find([
				[
					"dialogue"	=> (string)$did
				],
				"limit"	=> 20
			]);

			$userIds = [];
			foreach($dialogueUsers as $value)
				$userIds[] = (int)$value->user_id;


			if(count($userIds) > 0)
			{
				$userQuery = Users::find([
					[
						"id"	=> [
							'$in'	=> $userIds
						]
					]
				]);
				foreach($userQuery as $value)
				{
					$users[$value->id] = $value;
					$dialogueTitle[] = htmlspecialchars($value->firstname." ".$value->lastname);
				}
			}
			//var_dump($userIds);exit;
		}else{
			exit("Dialogue not found");
		}


		$this->view->setVar("dialogueTitle", $dialogueTitle);
		$this->view->setVar("users", $users);
		$this->view->setVar("did", $did);
	}

	public function sendAction()
	{
		$did 		= trim($this->request->get("dialogue"));
		$message 	= (string)$this->request->get("message");

		if (strlen(trim($message)) < 1 || strlen($message) > 10000)
		{
			$error = $this->lang->get("MessageError", "Message is wrong");
		}
		elseif(strlen($did) < 0)
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$dialogue=Dialogues::findById($did))
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		else
		{
			$msgIns = [
				"dialogue"		=> (string)$did,
				"user"			=> (int)$this->auth->getData()->id,
				"body"			=> $message,
				"type"			=> "text",
				"is_deleted"	=> 0,
				"created_at"	=> $this->mymongo->getDate()
			];
			$msgId = DialogueMessages::insert($msgIns);


			$dialogueUpdate = [
				"updated_at"	=> $this->mymongo->getDate(),
				"message"		=> [
					"id"	=> (string)$msgId,
					"body"	=> strlen($message) > 50 ? htmlspecialchars(substr($message, 0, 50))."...": htmlspecialchars($message),
					"type"	=> "text",
				]
			];

			if(!in_array((int)$this->auth->getData()->id, $dialogue->users))
			{
				if(!DialogueUsers::findFirst([["dialogue" => $did, "user_id" => (int)$this->auth->getData()->id]]))
				{
					DialogueUsers::createUserForDialogue($did, (int)$this->auth->getData()->id);
					$dialogue->users[] = (int)$this->auth->getData()->id;
					$dialogueUpdate["users"]	= $dialogue->users;
				}
			}

			Dialogues::update(
				[
					"_id"	=> $dialogue->_id,
				],
				$dialogueUpdate
			);

			DialogueUsers::update(
				[
					"dialogue"	=> (string)$dialogue->_id,
					"status"	=> [
						'$gte'	=> 0
					],
					"user_id"	=> [
						'$ne'	=> (int)$this->auth->getData()->id
					]
				],
				[
					"status"		=> 4,
					"updated_at"	=> $this->mymongo->getDate(),
				]
			);


			$success = $this->lang->get("SentSuccessfully", "Sent successfully");
		}

		if($success)
		{
			$response = [
				"status"		=> "success",
				"description"	=> $success,
			];
		}
		else
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1301,
			];
		}

		header('Content-type: text/json');
		echo json_encode($response, true);
		exit();
	}

	public function listAction()
	{
		$did 			= trim($this->request->get("dialogue_id"));
		$message_id 	= (string)$this->request->get("message_id");
		$sort_type 		= (string)$this->request->get("sort_type");
		$limit 			= (int)$this->request->get("limit");
		if($limit < 5 || $limit > 100)
			$limit = 20;


		if(strlen($did) < 0)
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$dialogue=Dialogues::findById($did))
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		else
		{
			$messages = [];

			$binds = [
				"dialogue"	=> $did,
			];
			$sort = [
				"_id"	=> -1
			];
			if($sort_type == "after" && strlen($message_id) > 6)
			{
				$binds["_id"]	= [
					'$gt' => $this->mymongo->objectId($message_id)
				];
			}
			elseif($sort_type == "before" && strlen($message_id) > 6)
			{
				$binds["_id"]	= [
					'$lt' => $this->mymongo->objectId($message_id)
				];
			}
			$messagesQuery = DialogueMessages::find([
				$binds,
				"sort"	=> $sort,
				"limit"	=> $limit
			]);

			$userIds = [];
			$users = [];
			foreach($messagesQuery as $value)
			{
				if(!in_array((int)$value->user, $userIds))
					$userIds[] = (int)$value->user;
			}
			if(count($userIds) > 0)
			{
				$usersQuery = Users::find([
					[
						'id'	=> [
							'$in' => $userIds
						]
					]
				]);
				foreach($usersQuery as $user)
					$users[(int)$user->id] = $user;
			}


			foreach($messagesQuery as $value)
			{
				$user = $users[(int)$value->user];
				$messages[] = [
					"id"			=> (string)$value->_id,
					"user"	=> [
						"id"		=> (int)$value->user,
						"fullname"	=> htmlspecialchars($user->firstname." ".$user->lastname),
						"avatar"	=> strlen($users[(int)$value->user]->avatar_id) > 0 ?
							'/upload/'.$user->_id.'/'.(string)$user->avatar_id.'/small.jpg':
							'http://placehold.it/80x80',
					],
					"is_me" 		=> $value->user == (int)$this->auth->getData()->id ? true: false,
					"message" 		=> htmlspecialchars($value->body),
					"type" 			=> $value->type,
					"date_text"		=> $this->mymongo->dateFormat($value->created_at, "H:i d.m.Y"),
					"unixtime"		=> $this->mymongo->toSeconds($value->created_at),
				];
			}
			$messages = $this->asortArray($messages);
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $messages,
			];
		}


		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1301,
			];
		}

		header('Content-type: text/json');
		echo json_encode($response, true);
		exit();
	}

	public function asortArray($array){
		krsort($array);
		$newArray = [];
		foreach($array as $value)
			$newArray[] = $value;
		return $newArray;
	}
}