<?PHP
//auth service index.php

require_once ('../config.php');
$app = new \Slim\Slim();

$appdb = NewADOConnection($basedsn . "authentication");
ADOdb_Active_Record::SetDatabaseAdapter($appdb);

class user extends ADOdb_Active_Record{}
class token extends ADOdb_Active_Record{}
$user = new user('user');
$token = new token('token');
//ADODB_Active_Record::ClassHasMany('user', 'token','user_id');
ADODB_Active_Record::TableHasMany('user', 'token', 'user_id');
//ADODB_Active_Record::ClassBelongsTo('token', 'user', 'user_id', 'id');
ADODB_Active_Record::TableBelongsTo('token','user','user_id','id');


//Register a new account
$app->post('/', function () use ($app, $user, $appdb, $salt) {
	$parameters = $app->request->params();
	if (isset($parameters['userName'],$parameters['email'],$parameters['password'])) {
		
		if ($user->Load("username=?", array($parameters['userName']))) {
			//userName already in use 409
			$app->response->setStatus(409);
			echo "The username is already registered";
		} elseif (!filter_var($parameters['email'], FILTER_VALIDATE_EMAIL)) {
			//email address is malformed 400
			$app->response->setStatus(400);
			echo "Email address provided is malformed";
					
		} else {
			//Account successfully registered
			$user->username = $parameters['userName'];
			$user->email = $parameters['email'];
			//password is hashed with sha256 plus the super secret salt defined in config.php
			$user->password = hash("sha256", $salt . $parameters['password']);
			$user->create_time = $appdb->BindTimeStamp(time());
			$user->Save();
			echo "Account successfully registered.";
			$app->response->setStatus(200);	
		}
	} else {
		//missing parameters 400
		$app->response->setStatus(400);
		echo "All of userName, email and password parameters are required to register an account";
	}

});
//Provide usage information for API
$app->options('/', function () use ($app){
	$app->response->setStatus(200);
	echo "This should return information on how to use the API.";
});

//get method not supported at base uri
$app->get('/', function () use ($app){
	$app->response->setStatus(405);
	echo "The GET method is not used at the base URI.";
});

//Authenticate an account
$app->post('/:userName', function ($userName) use ($app, $user, $token, $appdb, $salt){
	$parameters = $app->request->params();
	
	if (isset($parameters['password'])) {
		if (!$user->Load("username=?", array($userName))) {
			//userName not found 404
			$app->response->setStatus(400);
			echo "userName not found";
		} elseif (hash("sha256", $salt . $parameters['password']) != $user->password) {
			//incorrect password 400
			$app->response->setStatus(400);
			echo "Incorrect password";
		} else {
			//userName and password correct - create a token 200
			$token->create_time = $appdb->BindTimeStamp(time());
			$token->value = bin2hex(openssl_random_pseudo_bytes(32));
			$token->user_id = $user->id;
			$token->Save();
			$app->response->setStatus(200);
			
			$app->setCookie("storedToken",$token->value,"2 days");
			$app->setCookie("storedUserName",$userName,"2 days");
			echo $token->value;

		}
	} else {
		//missing parameters 400
		$app->response->setStatus(400);
		echo "Password is required to authenticate an account";
	}

});

//Request reset of a forgotten password
$app->get('/:userName', function ($userName) use ($app, $user, $token, $appdb){
	
	if (!$user->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName not found";

	} else {
		//Temporary password will be emailed to the user 204
		//Not implemented yet.
		$app->response->setStatus(204);
	}

});

//Change password
$app->put('/:userName', function ($userName) use ($app, $user, $token, $appdb,$salt){
	$parameters = $app->request->params();
	if (!$user->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName not found";

	} elseif (!isset($parameters['password'],$parameters['newPassword'],$parameters['repeatNewPassword'])) {
		//missing parameters 400
		$app->response->setStatus(400);
		echo "All of password, newPassword and repeatNewPassword parameters are required";
	} elseif ($parameters['newPassword'] != $parameters['repeatNewPassword']) {
		//newPassword and repeatNewPassword do not match 400
		$app->response->setStatus(400);
		echo "newPassword and repeatNewPassword do not match";
	} elseif ($user->password != hash("sha256", $salt.$parameters['password'])) {
		//Current password is incorrect
		$app->response->setStatus(400);
		echo "Current password is incorrect";
	} else {
		//Change password
		$user->password = hash("sha256", $salt.$parameters['newPassword']);
		$user->Save();
		$app->response->setStatus(204);
	}

});

//Verify if token is currently associated with the userName
$app->get('/:userName/:token', function ($userName,$submittedToken) use ($app, $user, $token, $appdb){

	if (!$user->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName or token not found";

	} elseif (!$token->Load("user_id=? AND value=?", array($user->id,$submittedToken))) {
		//token and user not associated 404
		$app->response->setStatus(404);
		echo "userName or token not found";
	} else {
		//Token and user are associated 204
		$app->response->setStatus(204);
	}


});

//Disassociates token with userName
$app->delete('/:userName/:token', function ($userName,$submittedToken) use ($app, $user, $token, $appdb){

	if (!$user->Load("username=?", array($userName))) {
		//userName not found 404
		$app->response->setStatus(404);
		echo "userName or token not found";

	} elseif (!$token->Load("user_id=? AND value=?", array($user->id,$submittedToken))) {
		//token and user not associated 400
		$app->response->setStatus(404);
		echo "userName or token not found";
	} else {
		//Token and user are no longer associated 204
		$token->Delete();
		$app->response->setStatus(204);
	}
});



$app->run();
