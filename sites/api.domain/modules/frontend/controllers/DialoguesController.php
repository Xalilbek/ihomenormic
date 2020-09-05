<?php
namespace Controllers;

use Models\DialogueMessages;
use Models\Dialogues;
use Models\DialogueUsers;
use Models\Users;

class DialoguesController extends \Phalcon\Mvc\Controller
{
	public function listAction()
	{
		$skip 		= (int)$this->request->get("skip");
		$limit 		= 20;

		$dialogues = DialogueUsers::find([
			[
				"user_id"	=> (int)$this->auth->getData()->id,
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

		//var_dump((int)$this->auth->getData()->id);exit;

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

		$users 			= [];
		$dialogueTitles = [];
		if(count($dialogues) > 0)
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

			foreach($dialogues as $value)
			{
				$dialogue 	= $dialoguesData[(string)$value->dialogue];

				foreach($dialogue->users as $userId)
				{
					if((int)$userId !== $this->auth->getData()->id && count($dialogueTitles[(string)$dialogue->_id]) < 4)
						$dialogueTitles[(string)$dialogue->_id][] = $users[$userId]->firstname." ".$users[$userId]->lastname;
				}

				$userId = (int)$dialogue->users[0] == $this->auth->getData()->id ? (int)$dialogue->users[1]: $dialogue->users[0];
				$user 		= @$users[$userId];
				$records[] = [
					"id"			=> (string)$value->dialogue,
					"avatar"		=> ($user && $user->avatar_id) ?
						FILE_URL.'/upload/'.$user->_id.'/'.(string)$user->avatar_id.'/small.jpg':
						'https://placehold.it/80x80',
					"title"			=> implode(", ", @$dialogueTitles[(string)$dialogue->_id]),
					"users"			=> $dialogue->users,
					"last_message"	=> (string)@$dialogue->message->body,
					"date"			=> @$this->mymongo->dateFormat($dialogue->updated_at, "d.m.y H:i"),
					"unixtime"		=> @$this->mymongo->toSeconds($dialogue->updated_at) * 1000,
				];
			}

			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> $records,
			];
		}
		else
		{
			$response = [
				"status"		=> "success",
				"description"	=> "",
				"data"			=> [],
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function getAction()
	{
		$error 			= false;
		$users			= [];
		foreach($this->request->get("users") as $value)
			$users[] = (int)$value;

		if (count($users) < 1)
		{
			$error = $this->lang->get("ChooseMsgUser", "Please, Choose users to send message");
		}
		else
		{
			$userQuery = Users::find([
				[
					"id"	=> [
						'$in'	=> $users
					]
				]
			]);
			$user = false;
			foreach($userQuery as $user)
				$dialogueTitles[] = $user->firstname." ".$user->lastname;

			$users[] 	= (int)$this->auth->getData()->id;

			$dialogue 	= Dialogues::getDialogue($users);

			$data = [
				"id"			=> (string)$dialogue->_id,
				"avatar"		=> ($user && $user->avatar_id) ?
					FILE_URL.'/upload/'.$user->_id.'/'.(string)$user->avatar_id.'/small.jpg':
					'https://placehold.it/80x80',
				"title"			=> implode(", ", @$dialogueTitles),
				"users"			=> $dialogue->users,
				"last_message"	=> (string)@$dialogue->message->body,
				"date"			=> @$this->mymongo->dateFormat($dialogue->updated_at, "d.m.y H:i"),
				"unixtime"		=> @$this->mymongo->toSeconds($dialogue->updated_at) * 1000,
			];

			$response = [
				"status"		=> "success",
				"data"			=> $data,
			];
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $this->lang->get("noInformation"),
				"error_code"	=> 1201,
			];
		}

		echo json_encode($response, true);
		exit();
	}

	public function deleteAction()
	{
		$id 		= trim($this->request->get("id"));

		$dialogue = DialogueUsers::find([
			[
				"dialogue"	=> (string)$id,
				"user_id"	=> (int)$this->auth->getData()->id,
			]
		]);
		if($dialogue)
		{
			DialogueUsers::update(
				[
					"dialogue"	=> (string)$id,
					"user_id"	=> (int)$this->auth->getData()->id
				],
				[
					"status"		=> 0,
					"deleted_at"	=> $this->mymongo->getDate(),
				]
			);

			$success = $this->lang->get("DeletedSuccessfully", "Deleted successfully");

			$response = [
				"status"			=> "success",
				"description"		=> $success,
			];
		}
		else
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}

		if($error)
		{
			$response = [
				"status"		=> "error",
				"description"	=> $error,
				"error_code"	=> 1534,
			];
		}
		echo json_encode($response, true);
		exit();
	}




	public function sendAction()
	{
		$did 		= trim($this->request->get("dialogue_id"));
		$message 	= (string)$this->request->get("message");

		if (strlen(trim($message)) < 1 || strlen($message) > 10000)
		{
			$error = $this->lang->get("MessageError", "Message is wrong");
		}
		elseif(strlen($did) < 10)
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$dialogue=Dialogues::findById($did))
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$myDialogue = DialogueUsers::findFirst([
			[
				"dialogue"	=> (string)$did,
				"user_id"	=> (int)$this->auth->getData()->id
			]
		]))
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

			$resData = [
				"id"			=> (string)$msgId,
				"user"	=> [
					"id"		=> (int)$this->auth->getData()->id,
					"fullname"	=> $this->auth->getData()->firstname." ".$this->auth->getData()->lastname,
					"avatar"	=> strlen($this->auth->getData()->avatar_id) > 0 ?
						FILE_URL.'/upload/'.(string)$this->auth->getData()->_id.'/'.(string)$this->auth->getData()->avatar_id.'/small.jpg':
						'https://placehold.it/80x80',
				],
				"message" 		=> $message,
				"unixtime"		=> time()*1000,
			];


			$dialogueUpdate = [
				"updated_at"	=> $this->mymongo->getDate(),
				"message"		=> [
					"id"	=> (string)$msgId,
					"body"	=> strlen($message) > 50 ? substr($message, 0, 50)."...": $message,
					"type"	=> "text",
				]
			];

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

			DialogueUsers::update(
				[
					"dialogue"	=> (string)$dialogue->_id,
					"user_id"	=> (int)$this->auth->getData()->id
				],
				[
					"status"		=> 1,
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
				"data"			=> $resData,
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



	public function messagesAction()
	{
		$did 			= trim($this->request->get("dialogue_id"));
		$limit 			= (int)$this->request->get("limit");
		if($limit < 3 || $limit > 100)
			$limit = 20;


		if(strlen($did) < 0)
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$dialogue=Dialogues::findById($did))
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		elseif(!$myDialogue = DialogueUsers::findFirst([
			[
				"dialogue"	=> (string)$did,
				"user_id"	=> (int)$this->auth->getData()->id
			]
		]))
		{
			$error = $this->lang->get("MessageNotFound", "Message not found");
		}
		else
		{
			$messages = [];

			$binds = [
				"dialogue"	=> $did,
				"created_at"	=> [
					'$gt'	=> $myDialogue->deleted_at
				]
			];

			$sort = 1;
			if(strlen($this->request->get("after")) > 0)
			{
				$binds["_id"]	= [
					'$gt' => $this->mymongo->objectId($this->request->get("after"))
				];
				$sort = 1;
			}
			elseif(strlen($this->request->get("before")) > 0)
			{
				$binds["_id"]	= [
					'$lt' => $this->mymongo->objectId($this->request->get("before"))
				];
			}
			$messagesQuery = DialogueMessages::find([
				$binds,
				"sort"	=> [
					"_id"	=> $sort
				],
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
						"fullname"	=> $user->firstname." ".$user->lastname,
						"avatar"	=> strlen($users[(int)$value->user]->avatar_id) > 0 ?
							FILE_URL.'/upload/'.$user->_id.'/'.(string)$user->avatar_id.'/small.jpg':
							'https://placehold.it/80x80',
					],
					//"is_me" 		=> $value->user == (int)$this->auth->getData()->id ? true: false,
					"message" 		=> $value->body,
					//"type" 			=> $value->type,
					//"date_text"		=> $this->mymongo->dateFormat($value->created_at, "H:s d.m.Y"),
					"unixtime"		=> $this->mymongo->toSeconds($value->created_at) * 1000,
				];
			}
			if($sort == 1)
				$messages = $this->asortArray($messages);
			$response = [
				"status"			=> "success",
				"description"		=> "",
				"first_message_id"	=> ($messages[0]) ? $messages[0]["id"]: false,
				"last_message_id"	=> ($messages[count($messages)-1]) ? $messages[count($messages)-1]["id"]: false,
				"data"				=> $messages,
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