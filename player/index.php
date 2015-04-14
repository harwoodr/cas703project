<?PHP
//player service index.php

require_once ('../config.php');
$app = new \Slim\Slim();

$appdb = NewADOConnection($basedsn . "player");
ADOdb_Active_Record::SetDatabaseAdapter($appdb);

class player extends ADOdb_Active_Record{}
$player = new player('player');




//Get a list of players
$app->get('/', function () use ($app,$appdb,$player){

	$players = $player->Find("TRUE");
	$results = array();
	foreach ($players as $row){
		$results[] = array("id"=>$row->id,"username"=>$row->username,"name"=>$row->name,"homepage"=>$row->homepage);
	}
	//Returns player information 200
	echo json_encode($results,JSON_UNESCAPED_SLASHES);
	$app->response->setStatus(200);
});

$app->post('/', function () use ($app,$appdb,$player){
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	if (!authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//not properly logged in 403
		$app->response->setStatus(403);
		echo "Not logged in";
	} elseif ($player->Load("username=?", array($cookies['storedUserName']))) {
		//profile already exists 409
		$app->response->setStatus(409);
		echo "Player profile already exists";
	} elseif (isset($parameters['homepage']) && !filter_var($parameters['homepage'], FILTER_VALIDATE_URL)) {
		//Malformed URL for homepage 400
		$app->response->setStatus(400);
		echo "Malformed URL for homepage";
	} else {
		if (isset($parameters['homepage'])) {
			//set homepage
			$player->homepage = $parameters['homepage'];
		}
		if (isset($parameters['name'])) {
			//set public name
			$player->name = $parameters['name'];
		}		
		$player->username = $cookies['storedUserName'];
		$player->Save();
		//profile created 200
		$app->response->setStatus(200);
		echo "Player profile created";
	}
});

//Provide usage information for API
$app->options('/', function () use ($app){
	$app->response->setStatus(200);
	echo "This should return information on how to use the API.";
});

//Get the profile of a specific player
$app->get('/:userName', function ($userName) use ($app,$appdb,$player){
	
	if (!$player->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName not found";
	} else {
		//Returns player information 200
		$result = array("id"=>$player->id,"username"=>$player->username,"name"=>$player->name,"homepage"=>$player->homepage);
		$app->response->setStatus(200);
		echo json_encode($result,JSON_UNESCAPED_SLASHES);
	}


});


//Modify a player profile
$app->put('/:userName', function ($userName) use ($app,$appdb,$player){
	$cookies = $app->request->cookies;
	$parameters = $app->request->params();
	if (!$player->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Authentication token invalid 400
		$app->response->setStatus(403);
		echo "Authentication token invalid";
	} elseif ($player->username != $cookies['storedUserName'])  {
		//Request not by owner 403
		$app->response->setStatus(403);
		echo "Request not by owner";
	} elseif (isset($parameters['homepage']) && !filter_var($parameters['homepage'], FILTER_VALIDATE_URL)) {
		//Malformed URL for homepage 400
		$app->response->setStatus(400);
		echo "Malformed URL for homepage";
	} else {
		//Profile updated 204
		if (isset($parameters['homepage'])) {
			//set homepage
			$player->homepage = $parameters['homepage'];
		}
		if (isset($parameters['name'])) {
			//set public name
			$player->name = $parameters['name'];
		}				
		$player->Save();
		$app->response->setStatus(204);
	}
	

	

});

//Get a list of games a player is participating in
$app->get('/:userName/games', function () use ($app){
		
	//userName not found 404

	//Returns game information 200


});

//FRONT END

//get user's profile
$app->get('/profile', function () use ($app) {

});

//create or update user's profile
$app->post('/profile', function () use ($app) {

});

//get a list of user profiles
$app->get('/profiles', function () use ($app) {

});

//get a specific user's profile
$app->get('/profile/:userName', function ($userName) use ($app) {

});


$app->run();

function checkGames($username) {


}
