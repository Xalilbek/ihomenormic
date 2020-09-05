<?php
namespace Controllers;

use Models\Users;

class IndexController extends \Phalcon\Mvc\Controller
{
	public function indexAction()
	{
		exit("Index");
		set_time_limit(0);
		$start = microtime(true);
		for($i=0;$i<10;$i++){
			$insert = [
				"id"	=> rand(1111,9999),
				"name"	=> "Asd asd ",
				"fullname"	=> " askdj alksdk asjdlkja skdjaslkdjlk",
				"asda"	=> " askdj alksdk asjdlkja skdjaslkdjlk",
				"password"	=> rand(111111,999999),
				"date"	=> $this->mongo->getDate(),
			];
			//for($ii=0;$ii<100;$ii++)$insert["key_".rand(11111,999999999)] = "asd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj dasd asdasd asdad asdad adaswww asjdhajs hjashdkjahsdj hasdjhaksj d";
			$id = Users::insert($insert);
			//var_dump($id);exit;
			//Users::findById($id);
			Users::update(["id" => rand(1111,9999)], ["asdas" => "asdasdsa"]);

			$data = Users::find([[], "limit"	=> 10]);
		}
		echo "Exec time: ".(microtime(true) - $start)."<br/>";

		//$data = $this->mongo->findFirst([[], "sort"	=> ["id" => -1]]);
		$data = Users::find([[], "sort"	=> ["id" => -1], "limit"	=> 100]);

		foreach($data as $value)
		{
			echo "id: ".$value->id.", password: ".$value->password.", date: ";
			if(@$value->date) echo $this->mongo->dateFormat($value->date);
			echo "<br/>";
		}
		//var_dump($data);
		exit();
	}
}