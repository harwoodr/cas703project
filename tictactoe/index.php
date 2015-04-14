<?PHP
//tictactoe API index.php

require_once ('../config.php');
$app = new \Slim\Slim();
$baseurl = $serverurl . "tictactoe/";
$appdb = NewADOConnection($basedsn . "tictactoe");
ADOdb_Active_Record::SetDatabaseAdapter($appdb);

class tictactoe extends ADOdb_Active_Record{}

class cell extends ADOdb_Active_Record{}
$tictactoe = new tictactoe('tictactoe');
$cell = new cell('cell');

ADODB_Active_Record::TableHasMany('tictactoe', 'cell', 'tictactoe_id');
ADODB_Active_Record::TableBelongsTo('cell','tictactoe','tictactoe_id','id');

$app->get('/play/', function () use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Front Page'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));

	$app->render('footer.php',array());

});

$app->get('/play/list', function () use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Game List'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));
	$apicall = curlget($baseurl);

	echo "<table border='1'>";
	echo "<tr><td>Status</td><td>First Player</td><td>Second Player</td><td>Player's Turn</td><td>Action</td></tr>";
	foreach (json_decode($apicall['response']) as $row) {
		echo "<tr><td>";
		echo $row->status ."</td><td>";
		echo $row->firstplayername ."</td><td>";
		echo $row->secondplayername ."</td><td>";
		if ($row->status == "playing" && (($row->secondplayername == $cookies['storedUserName'] && $row->playerturn == "second") || ($row->firstplayername == $cookies['storedUserName'] && $row->playerturn == "first")) ) {
			echo "<a href='view/".$row->id."'>Yours!</a>";
		} else {
			echo $row->playerturn ;		
		}
		echo "</td><td>";
		if ($row->status == "offered" && $row->firstplayername == $cookies['storedUserName']) {
			//delete offer
			echo "<a href='delete/".$row->id."'>Delete</a> - ";
		} elseif ($row->status == "offered") {
			//join game
			echo "<a href='join/".$row->id."'>Join</a> - ";
		} elseif ($row->status == "playing" && ($row->firstplayername == $cookies['storedUserName'] || $row->secondplayername == $cookies['storedUserName']) ) {
			//concede game
			echo "<a href='delete/".$row->id."'>Concede</a> - ";
		} 
		//view game
		echo "<a href='view/".$row->id."'>View</a>";


		echo "</td></tr>";
	}
	$app->render('footer.php',array());

});

$app->get('/play/delete/:id', function ($id) use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Delete/Concede Game'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));
	$apicall = curldelete($baseurl.$id,array('storedUserName'=>$cookies->storedUserName,'storedToken'=>$cookies->storedToken));
	echo "Game deleted or conceded.";
	$app->render('footer.php',array());

});

$app->get('/play/view/:id', function ($id) use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'View Board'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));

	$apicall = curlget($baseurl.$id);
	$response = json_decode($apicall['response']);
	$board = array();
	foreach ($response->cells as $cell) {
		$board[$cell->id] = $cell->mark;
	}	

	echo "<table>";
	for ($i=1;$i<=9;$i++) {
		if ($i%3 == 1) {
			echo "<tr>";
		}
		echo celldisplay($i);
		if (!isset($board[$i])) {
			if (($cookies['storedUserName'] == $response->firstplayername && $response->playerturn == "first") && $response->status == "playing") {
				echo "<a href='../../play/mark/".$id."/".$i."'><h1>#</h1></a></td>";
			} elseif (($cookies['storedUserName'] == $response->secondplayername && $response->playerturn == "second") && $response->status == "playing") {
				echo "<a href='../../play/mark/".$id."/".$i."'><h1>#</h1></a></td>";
			} else {
				echo "<h1>#</h1></td>";
			}

		} else {
			echo "<h1>".strtoupper($board[$i])."</h1>";
		}
	
		if ($i%3 == 0) {
			echo "</tr>";
		}	 
	}

	if ($cookies['storedUserName'] == $response->firstplayername) {

	} elseif ($cookies['storedUserName'] == $response->secondplayername) {

	} else {

	}	

	$app->render('footer.php',array());

});

$app->get('/play/mark/:id/:cellId', function ($id,$cellId) use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Make a Mark'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));
	$apicall = curlput($baseurl.$id."/".$cellId,array('storedUserName'=>$cookies['storedUserName'],'storedToken'=>$cookies['storedToken']));

	$app->redirect($baseurl.'/play/view/'.$id);
	$app->render('footer.php',array());

});

$app->get('/play/offer', function () use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Offer a Game'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));
//
	$apicall = curlpost($baseurl,array("storedUserName"=>$cookies['storedUserName'],"storedToken"=>$cookies['storedToken']));

	$app->redirect($baseurl.'/play/list');
	$app->render('footer.php',array());

});

$app->get('/play/join/:id', function ($id) use ($app,$appdb,$tictactoe,$baseurl) {
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	$app->render('tttheader.php',array('title'=>'Join a Game'));
	$app->render('tttmenu.php',array("baseurl"=>$baseurl));

	$apicall = curlput($baseurl.$id,array("storedUserName"=>$cookies['storedUserName'],"storedToken"=>$cookies['storedToken']));
	$app->redirect($baseurl.'/play/list');
	$app->render('footer.php',array());

});

//Retrieve a list of game instances
$app->get('/', function () use ($app,$appdb,$tictactoe) {
	$parameters = $app->request->params();
	$results = array();
	if (isset($parameters['status'],$parameters['playerName'])){
		//status and playerName are set
		$tictactoes = $tictactoe->Find("status=? AND (firstplayername = ? OR secondplayername = ?)", array($parameters['status'],$parameters['playerName'],$parameters['playerName']));
	} elseif (isset($parameters['status'])){
		//status is set
		$tictactoes = $tictactoe->Find("status=?", array($parameters['status']));
	} elseif (isset($parameters['playerName'])){
		//playerName is set
		$tictactoes = $tictactoe->Find("(firstplayername = ? OR secondplayername = ?)", array($parameters['playerName'],$parameters['playerName']));
	} else {
		//no query parameters
		$tictactoes = $tictactoe->Find("TRUE");
	}
	//convert to JSON
	foreach ($tictactoes as $row){
		$results[] = array("id"=>$row->id,"status"=>$row->status,"firstplayername"=>$row->firstplayername,"secondplayername"=>$row->secondplayername,"playerturn"=>$row->playerturn);
	}
	echo json_encode($results,JSON_UNESCAPED_SLASHES);

	$app->response->setStatus(200);

});

//Offer a new instance of the game
$app->post('/', function () use ($app,$appdb,$tictactoe) {
	$cookies = $app->request->cookies;
	if (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Registering of games is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Registering of games is not permitted with the current credentials";
	} else {
		//A listing for the game has been created 201
		$tictactoe->status = "offered";
		$tictactoe->firstplayername = $cookies['storedUserName'];
		$tictactoe->playerturn = "first";
		$tictactoe->Save();
		$app->response->setStatus(201);
	}
	


});

//Provide usage information for API
$app->options('/', function () use ($app){
	$app->response->setStatus(200);
		echo "This should return information on how to use the API.";
});


//Retrieve the information of a specific game instance
$app->get('/:instanceId', function ($instanceId) use ($app,$appdb,$tictactoe,$cell){
	if (!$tictactoe->Load("id=?", array($instanceId))) {
		//instanceId not found 404
		$app->response->setStatus(404);
		echo "instanceId not found";
	} else {
		//Returns game information 200
		$tictactoe->LoadRelations('cell');
		$cells = array();
		foreach($tictactoe->cell as $row){
			$cells[] = array("id"=>$row->id,"tictactoe_id"=>$row->tictactoe_id,"mark"=>$row->mark);

		}
		$result = array("id"=>$tictactoe->id,"status"=>$tictactoe->status,"firstplayername"=>$tictactoe->firstplayername,"secondplayername"=>$tictactoe->secondplayername,"playerturn"=>$tictactoe->playerturn,"cells"=>$cells);
		$app->response->setStatus(200);
		echo json_encode($result,JSON_UNESCAPED_SLASHES);
	}		
});

//Join a game
$app->put('/:instanceId', function ($instanceId) use ($app,$appdb,$tictactoe){
	$cookies = $app->request->cookies;
	if (!$tictactoe->Load("id=?", array($instanceId))) {
		//instanceId not found 404
		$app->response->setStatus(404);
		echo "instanceId not found";
	} elseif ($tictactoe->secondplayername != NULL) {
		//Game instance is not accepting players 403
		$app->response->setStatus(403);
		echo "Game instance is not accepting players";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Joining games is not permitted with the current credentials 403

		$app->response->setStatus(403);
		echo "Joining games is not permitted with the current credentials";

	} else {
		//Game instance joined 204
		$tictactoe->secondplayername = $cookies['storedUserName'];
		$tictactoe->status = "playing";
		$tictactoe->Save();
		$app->response->setStatus(204);
	}
	//you can currently join a game that you offered...
});

//Concede a game or withdraw a game offer
$app->delete('/:instanceId', function ($instanceId) use ($app,$appdb,$tictactoe){
	$cookies = $app->request->cookies;
	if (!$tictactoe->Load("id=?", array($instanceId))) {
		//instanceId not found 404
		$app->response->setStatus(404);
		echo "instanceId not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken']) || ($tictactoe->firstplayername != $cookies['storedUserName'] && $tictactoe->secondplayername != $cookies['storedUserName'])) {
		//Ending this game is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Ending this game is not permitted with the current credentials";
	} elseif ($tictactoe->secondplayername != NULL) {
		// Game instance conceded 204
		if ($cookies['storedUserName'] == $tictactoe->firstplayername){
			$tictactoe->status = "won second";
		} else {
			$tictactoe->status = "won first";
		}
		$tictactoe->Save();
		$app->response->setStatus(204);
	} else {
		// Game instance offer withdrawn 204
		dumpit($tictactoe->Delete());
		$app->response->setStatus(204);
	}

});
//Get contents of a specific place on the board
$app->get('/:instanceId/:cellId', function ($instanceId,$cellId) use ($app,$appdb,$tictactoe,$cell){
	$cookies = $app->request->cookies;

	if (!$tictactoe->Load("id=?", array($instanceId))) {
		//instanceId not found 404
		$app->response->setStatus(404);
		echo "instanceId not found";
	} elseif (!$cell->Load("tictactoe_id=? AND id=?", array($instanceId,$cellId))) {
		//cellId not found 404
		$app->response->setStatus(404);
		echo "cellId not found";
	} else {	
		$app->response->setStatus(200);
		$result[$cellId] = $cell->mark;
		echo json_encode($result,JSON_UNESCAPED_SLASHES);
	}
});

//Make a mark
$app->put('/:instanceId/:cellId', function ($instanceId,$cellId) use ($app,$appdb,$tictactoe,$cell){
	$cookies = $app->request->cookies;

	if (!$tictactoe->Load("id=?", array($instanceId))) {
		//instanceId not found 404
		$app->response->setStatus(404);
		echo "instanceId not found";
	} elseif ($cell->Load("tictactoe_id=? AND id=?", array($instanceId,$cellId)) || $cellId <1 || $cellId >9) {
		//Illegal Move 409
		$app->response->setStatus(409);
		if ($cellId <1 || $cellId >9) {
			echo "cellId out of range (1-9)";
		} else {
			echo "cellId already contains a mark";
		}
	} elseif ($tictactoe->status == "offered") {
		//The game hasn't started 400
		$app->response->setStatus(400);
		echo "This game hasn't started";
	} elseif ($tictactoe->status != "playing") {
		//This game has ended 400
		$app->response->setStatus(400);
		echo "This game has ended";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken']) || ($tictactoe->firstplayername != $cookies['storedUserName'] && $tictactoe->secondplayername != $cookies['storedUserName'])) {
		//Making moves in this game is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Making moves in this game is not permitted with the current credentials";
	} elseif (($tictactoe->playerturn == "first" && $cookies['storedUserName']!= $tictactoe->firstplayername) || ($tictactoe->playerturn == "second" && $cookies['storedUserName']!= $tictactoe->secondplayername)) {
		//Not current player's turn 409
		$app->response->setStatus(409);
		echo "Not current player's turn";
	} else {	

		//make mark
		if ($tictactoe->playerturn == "first") {
			$mark = "x";
		} else {
			$mark = "o";
		}
		$cell->id = $cellId;
		$cell->tictactoe_id = $instanceId;
		$cell->mark = $mark;
		$cell->Save();
		$cells = array();
		foreach ($tictactoe->cell as $row) {
			$cells[$row->id] = $row->mark;
		}
		for ($i=1;$i<=9;$i++) {
			if (isset($cells[$i])) {
				$board[$i] = $cells[$i];
			} else {
				$board[$i] = "";
			}

		}
		if (checkforwin($board)) {
			//current player has won
			$app->response->setStatus(200);
			echo "Current player has won";
			if ($tictactoe->playerturn == "first") {
				$tictactoe->status = "won first";
			} else {
				$tictactoe->status = "won second";
			}
			$tictactoe->Save();
		} elseif (count($cells) == 9) {
			//the game has ended in a draw
			$app->response->setStatus(200);
			echo "The game has ended in a draw";
			$tictactoe->status = "draw";
			$tictactoe->Save();
		} else {
			//mark made
			$app->response->setStatus(204);
			if ($tictactoe->playerturn == "first") {
				$tictactoe->playerturn = "second";
			} else {
				$tictactoe->playerturn = "first";
			}
			$tictactoe->Save();
		}

		
	}
});
function checkforwin ($board) {

	if (threeinarow($board[1],$board[2],$board[3]) ||
	threeinarow($board[4],$board[5],$board[6]) ||
	threeinarow($board[7],$board[8],$board[9]) ||
	threeinarow($board[1],$board[4],$board[7]) ||
	threeinarow($board[2],$board[5],$board[8]) ||
	threeinarow($board[3],$board[6],$board[9]) ||
	threeinarow($board[1],$board[5],$board[9]) ||
	threeinarow($board[3],$board[5],$board[7]) ) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function threeinarow ($a,$b,$c) {
	if ($a == $b && $b == $c && $a !="") {
		return TRUE;
	} else {
		return FALSE;
	}

}

$app->run();

function curlget($url,$cookies = array()){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	if (count($cookies) > 0) {
		$curlcookies = "";
		foreach ($cookies as $key=>$value){
			$curlcookies .= $key ."=". $value ."; ";
		}
		
		curl_setopt($ch, CURLOPT_COOKIE, rtrim($curlcookies,"; "));
	}

	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	return array ('response'=>$response,'info'=>$info);
}

function curlpost($url,$cookies = array(),$postVars = array()){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	if (count($cookies) > 0) {
		$curlcookies = "";
		foreach ($cookies as $key=>$value){
			$curlcookies .= $key ."=". $value ."; ";
		}
		
		curl_setopt($ch, CURLOPT_COOKIE, rtrim($curlcookies,"; "));
	}
	if (count($postVars) > 0) {
		$curlpostVars = "";
		foreach ($postVars as $key=>$value){
			$curlpostVars .= $key ."=". $value ."&";
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS,rtrim($curlpostVars,"&"));
	}
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	return array ('response'=>$response,'info'=>$info);

}

function curlput($url,$cookies = array(),$putVars = array()){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	if (count($cookies) > 0) {
		$curlcookies = "";
		foreach ($cookies as $key=>$value){
			$curlcookies .= $key ."=". $value ."; ";
		}
		
		curl_setopt($ch, CURLOPT_COOKIE, rtrim($curlcookies,"; "));
	}
	if (count($putVars) > 0) {
		$curlputVars = "";
		foreach ($putVars as $key=>$value){
			$curlputVars .= $key ."=". $value ."&";
		}
		
		curl_setopt($ch, CURLOPT_POSTFIELDS,rtrim($curlputVars,"&"));
	}
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	return array ('response'=>$response,'info'=>$info);
}

function curldelete($url,$cookies){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	if (count($cookies) > 0) {
		$curlcookies = "";
		foreach ($cookies as $key=>$value){
			$curlcookies .= $key ."=". $value ."; ";
		}
		
		curl_setopt($ch, CURLOPT_COOKIE, rtrim($curlcookies,"; "));
	}

	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	return array ('response'=>$response,'info'=>$info);

}

function celldisplay($cell) {
	switch ($cell) {
		case 1:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-right: 1px solid;'>";
			break;
		case 2:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-right: 1px solid;border-left: 1px solid;'>";
			break;
		case 3:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-left: 1px solid;'>";
			break;
		case 4:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-top: 1px solid;border-right: 1px solid;'>";
			break;
		case 5:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-top: 1px solid;border-right: 1px solid;border-left: 1px solid;'>";
			break;
		case 6:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-bottom: 1px solid;border-top: 1px solid;border-left: 1px solid;'>";
			break;
		case 7:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-top: 1px solid;border-right: 1px solid;'>";
			break;
		case 8:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-top: 1px solid;border-right: 1px solid;border-left: 1px solid;'>";
			break;
		case 9:
			$td = "<td valign='baseline' style='text-align: center;width: 3em;border-top: 1px solid;border-left: 1px solid;'>";
			break;
	}
	return $td;
}
