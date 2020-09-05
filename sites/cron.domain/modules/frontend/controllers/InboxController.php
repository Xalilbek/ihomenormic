<?php
namespace Controllers;

use Models\Mailbox;
use Models\MailboxLogs;
use Models\Users;

class InboxController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		$error 		= false;
		$showMail 	= false;
		$mail 		= false;
		$hash 		= trim($this->request->get("hash"));
		$code 		= trim($this->request->get("code"));

		if(strlen($hash) < 5)
		{
			$error = "Link was corrupted";
		}
		else if(!$mail=Mailbox::findById($hash))
		{
			$error = "Mail not found";
		}
		else if((int)$mail->is_deleted == 1)
		{
			$error = "Link was expired";
		}
		else if($this->request->get("submit"))
		{
			if(MailboxLogs::count([["mail_id" => (string)$mail->_id, "type" => "error", "created_at" => ['$gt' => $this->mymongo->getDate(time()-3600)]]]) > 6)
			{
				$error = "You reached attempting limit to enter verification code. Please try 1 hour later";
			}
			else if($mail->code == $code)
			{
				Mailbox::update(
					[
						"_id" => $mail->_id,
					],
					[
						"is_read"	=> 1,
						"ip"		=> $this->lib->getIp(),
						"browser"	=> $this->request->getServer("HTTP_USER_AGENT"),
						"read_at"	=> $this->mymongo->getDate(),
					]
				);

				$log = [
					"mail_id"			=> 	(string)$mail->_id,
					"type"				=> 	"read",
					"ip"				=> 	$this->lib->getIp(),
					"browser"			=> 	$this->request->getServer("HTTP_USER_AGENT"),
					"created_at"		=> 	$this->mymongo->getDate(),
				];

				MailboxLogs::insert($log);

				exit($mail->content);
			}
			else
			{
				$error = "Verification code is incorrect";
				$log = [
					"mail_id"			=> 	(string)$mail->_id,
					"type"				=> 	"error",
					"error_code"		=> 	7777,
					"description"		=> 	$error,
					"code"				=> 	$code,
					"ip"				=> 	$this->lib->getIp(),
					"browser"			=> 	$this->request->getServer("HTTP_USER_AGENT"),
					"created_at"		=> 	$this->mymongo->getDate(),
				];
				MailboxLogs::insert($log);
			}
		}
		else
		{
			$log = [
				"mail_id"			=> 	(string)$mail->_id,
				"type"				=> 	"open",
				"ip"				=> 	$this->lib->getIp(),
				"browser"			=> 	$this->request->getServer("HTTP_USER_AGENT"),
				"created_at"		=> 	$this->mymongo->getDate(),
			];
			MailboxLogs::insert($log);
		}

		$this->view->setVar("error", $error);
		$this->view->setVar("mail", $mail);
		$this->view->partial("inbox/index");
		exit;
	}
}