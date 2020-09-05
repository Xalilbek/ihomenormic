<?php
namespace Controllers;

use Models\DialogueMessages;
use Models\Dialogues;
use Models\DialogueUsers;
use Models\Users;

class DialoguesController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= 20;

		$dialogues = Dialogues::find([
			[
				"is_deleted" => [
					'$ne'	=> 1
				]
			],
			"sort"	=> [
				"updated_at"	=> -1
			],
			"skip"	=> $skip,
			"limit"	=> $limit,
		]);

		$count = Dialogues::count([
			[
				"is_deleted" => [
					'$ne'	=> 1
				]
			],
		]);

		$userIds = [];
		$usersArr = [];
		foreach($dialogues as $value)
			$usersArr = array_merge($usersArr, $value->users);
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
			foreach($userQuery as $value)
			{
				$users[$value->id] = $value;
			}

			foreach($dialogues as $dialogue)
			{
				foreach($dialogue->users as $userId)
				{
					if(count($dialogueTitles[(string)$dialogue->_id]) < 4)
						$dialogueTitles[(string)$dialogue->_id][] = htmlspecialchars($users[$userId]->firstname." ".$users[$userId]->lastname);
				}
			}
		}

		$pagination = $this->lib->navigator($skip, $count, $limit, "?skip=");

		$this->view->setVar("dialogues", $dialogues);
		$this->view->setVar("dialogueTitles", $dialogueTitles);
		$this->view->setVar("pagination", $pagination);
	}

	public function addAction()
	{
		$error 			= false;
		$success 		= false;
		$message 		= $this->request->get("message");
		$citizens 		= $this->request->get("citizen");
		$employees 		= $this->request->get("employee");
		$partners 		= $this->request->get("partner");
		$moderators 	= $this->request->get("moderator");
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
				foreach($moderators as $value)
					$users[] = (int)$value;
				foreach($citizens as $value)
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
		}
		$this->view->setVar("error", $error);
		$this->view->setVar("success", $success);
		$this->view->setVar("moderators", Users::find([["type" => "moderator", "is_deleted" => 0]]));
		$this->view->setVar("citizens", Users::find([["type" => "citizen", "is_deleted" => 0]]));
		$this->view->setVar("employees", Users::find([["type" => "employee", "is_deleted" => 0]]));
		$this->view->setVar("partners", Users::find([["type" => "partner", "is_deleted" => 0]]));
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