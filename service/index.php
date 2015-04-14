<?PHP
//service directory index.php

require_once ('../config.php');
$app = new \Slim\Slim();

$appdb = NewADOConnection($basedsn . "service");
ADOdb_Active_Record::SetDatabaseAdapter($appdb);

class service extends ADOdb_Active_Record{}
$service = new service('service');



//Register a new service in the directory
$app->post('/', function () use ($app,$service,$appdb) {
	$parameters = $app->request->params();
	if (isset($parameters['serviceName'],$parameters['url'],$parameters['status'],$parameters['serviceType'])) {
		
		if ($service->Load("name=?", array($parameters['serviceName']))) {
			//userName already in use 409
			$app->response->setStatus(409);
			echo "The serviceName is already registered";
		} elseif (!filter_var($parameters['url'], FILTER_VALIDATE_URL)) {
			//URL is malformed 400
			$app->response->setStatus(400);
			echo "URL provided is malformed";
		} else {
			//Service successfully registered
			$service->name = $parameters['serviceName'];
			$service->url = $parameters['url'];
			$service->status = $parameters['status'];
			$service->type = $parameters['serviceType'];
			if (isset($parameters['description'])) {
				$service->description = $parameters['description'];
			}
			$service->Save();
			$app->response->setStatus(201);	
		}
	} else {
		//missing parameters 400
		$app->response->setStatus(400);
		echo "All of serviceName, url, status and serviceType parameters are required to register a service";
	}

});

//Provide usage information for API
$app->options('/', function () use ($app){
	$app->response->setStatus(200);
		echo "This should return information on how to use the API.";
});

//Retrieve a list of services in the directory
$app->get('/', function () use ($app,$service,$appdb){
	$parameters = $app->request->params();
	$services = array();
	if (isset($parameters['status'],$parameters['type'])){
		//both status and type are set
		$services = $service->Find("status=? AND type=?", array($parameters['status'],$parameters['type']));
	} elseif (isset($parameters['type'])) {
		//type is set
		$services = $service->Find("type=?", array($parameters['type'])); 
	} elseif (isset($parameters['status'])) {
		//status is set
		$services = $service->Find("status=?", array($parameters['status']));
	} else {
		//no query parameters
		$services = $service->Find("TRUE");
	}
	//convert to JSON
	foreach ($services as $row){
		$results[] = array("id"=>$row->id,"name"=>$row->name,"description"=>$row->description,"url"=>$row->url,"type"=>$row->type,"status"=>$row->status);
	}
	echo json_encode($results,JSON_UNESCAPED_SLASHES);
	$app->response->setStatus(200);
});

//Retrieve the information of a specific service
$app->get('/:serviceName', function ($serviceName) use ($app,$service,$appdb){
	
	if (!$service->Load("name=?", array($serviceName))) {
		//serviceName not found 404
		$app->response->setStatus(404);
		echo "serviceName not found";
	} else {
		//Details for the specified service are returned 200
		$app->response->setStatus(200);
		echo json_encode(array("id"=>$service->id,"name"=>$service->name,"description"=>$service->description,"url"=>$service->url,"type"=>$service->type,"status"=>$service->status),JSON_UNESCAPED_SLASHES);
		
	}
});

//Update the information of a specific service
$app->put('/:serviceName', function ($serviceName) use ($app,$service,$appdb){
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	if (!$service->Load("name=?", array($serviceName))) {
		//serviceName not found 404
		$app->response->setStatus(404);
		echo "serviceName not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Updating the specified service is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Updating the specified service is not permitted with the current credentials";
		//currently allows any registered user to change the services...
	} else {
		//The service information has been updated 204
		$app->response->setStatus(204);
		if (isset($parameters['description'])) {
			$service->description = $parameters['description'];
		}
		if (isset($parameters['url']) && filter_var($parameters['url'], FILTER_VALIDATE_URL)) {
			$service->url = $parameters['url'];
		}
		if (isset($parameters['status'])) {
			$service->status = $parameters['status'];
		}		
		if (isset($parameters['serviceType'])) {
			$service->type = $parameters['serviceType'];
		}
	}


});

//Remove the specified service from the directory
$app->delete('/:serviceName', function ($serviceName) use ($app,$service,$appdb){
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	if (!$service->Load("name=?", array($serviceName))) {
		//serviceName not found 404
		$app->response->setStatus(404);
		echo "serviceName not found";
	} elseif (!authCheck($cookies['storedUserName'],$cookies['storedToken'])) {
		//Removing the specified service is not permitted with the current credentials 403
		$app->response->setStatus(403);
		echo "Removing the specified service is not permitted with the current credentials";
		//currently allows any registered user to change the services...
	} else {
		//TThe specified service has been removed from the directory listing 204
		$app->response->setStatus(204);
		$service->Delete();
	}


});









$app->run();
