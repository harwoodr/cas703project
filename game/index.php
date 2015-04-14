<?PHP
//game directory index.php

require_once ('../config.php');
$app = new \Slim\Slim();

$appdb = NewADOConnection($basedsn . "game");
ADOdb_Active_Record::SetDatabaseAdapter($appdb);

class game extends ADOdb_Active_Record{}
$game = new game('game');


//Retrieve a list of games in the directory
$app->get('/', function () use ($app,$appdb,$game) {
	$parameters = $app->request->params();
	$games = array();
	if (isset($parameters['genre'])){
		//genre is set
		$games = $game->Find("genre=?", array($parameters['genre']));
	} else {
		//no query parameters
		$games = $game->Find("TRUE");
	}
	//convert to JSON
	foreach ($games as $row){
		$results[] = array("id"=>$row->id,"name"=>$row->name,"description"=>$row->description,"url"=>$row->url,"genre"=>$row->genre);
	}
	echo json_encode($results,JSON_UNESCAPED_SLASHES);

	$app->response->setStatus(200);

});

//Register a new game in the directory
$app->post('/', function () use ($app,$appdb,$game) {
	$parameters = $app->request->params();
	if (isset($parameters['gameName'],$parameters['url'],$parameters['genre'])) {
		
		if ($game->Load("name=?", array($parameters['gameName']))) {
			//userName already in use 409
			$app->response->setStatus(409);
			echo "The gameName is already registered";
		} elseif (!filter_var($parameters['url'], FILTER_VALIDATE_URL)) {
			//URL is malformed 400
			$app->response->setStatus(400);
			echo "URL provided is malformed";
		} else {
			//Game successfully registered
			$game->name = $parameters['gameName'];
			$game->url = $parameters['url'];
			$game->genre = $parameters['genre'];
			if (isset($parameters['description'])) {
				$game->description = $parameters['description'];
			}
			$game->Save();
			$app->response->setStatus(201);	
		}
	} else {
		//missing parameters 400
		$app->response->setStatus(400);
		echo "All of gameName, url and genre parameters are required to register a game";
	}



});

//Provide usage information for API
$app->options('/', function () use ($app){
	$app->response->setStatus(200);
		echo "This should return information on how to use the API.";
});


//Retrieve the information of a specific game
$app->get('/:gameName', function ($gameName) use ($app,$appdb,$game){
	if (!$game->Load("name=?", array($game))) {
		//gameName not found 404
		$app->response->setStatus(404);
		echo "gameName not found";
	} else {
		//Details for the specified game are returned 200
		$app->response->setStatus(200);
		echo json_encode(array("id"=>$game->id,"name"=>$game->name,"description"=>$game->description,"url"=>$game->url,"type"=>$game->genre),JSON_UNESCAPED_SLASHES);
		
	}
});

//Update the information of a specific game
$app->put('/:gameName', function ($gameName) use ($app,$appdb,$game){

	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	if (!$game->Load("name=?", array($gameName))) {
		//gameName not found 404
		$app->response->setStatus(404);
		echo "gameName not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Updating the specified game is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Updating the specified game is not permitted with the current credentials";
		//currently allows any registered user to change the game...
	} else {
		//The service information has been updated 204
		$app->response->setStatus(204);
		if (isset($parameters['description'])) {
			$game->description = $parameters['description'];
		}
		if (isset($parameters['url']) && filter_var($parameters['url'], FILTER_VALIDATE_URL)) {
			$game->url = $parameters['url'];
		}
		if (isset($parameters['genre'])) {
			$game->status = $parameters['status'];
		}		

	}


});

//Remove the specified game from the directory
$app->delete('/:gameName', function ($gameName) use ($app,$appdb,$game){
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	if (!$game->Load("name=?", array($gameName))) {
		//gameName not found 404
		$app->response->setStatus(404);
		echo "gameName not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Removing the specified service is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Removing the specified game is not permitted with the current credentials";
		//currently allows any registered user to change the games...
	} else {
		//TThe specified game has been removed from the directory listing 204
		$app->response->setStatus(204);
		$game->Delete();
	}

});




$app->run();
