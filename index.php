<?PHP
//WEB FRONT END

require 'vendor/autoload.php';

$app = new \Slim\Slim();
$baseurl = "http://techserv.ece.mcmaster.ca/~harwood/project/";

$app->get('/', function () use ($app,$baseurl){
	$app->render('header.php',array('title'=>'Front Page'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	$app->render('footer.php',array());
});


//get login form
$app->get('/login', function () use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Login'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in

		echo "You are already logged in.";
			

	} else {
		//get login form
		$app->render('loginform.php',array());
	}
	$app->render('footer.php',array());
});

//process login
$app->post('/login', function () use ($app,$baseurl) {
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Login'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in
		echo "You are already logged in.";
	} elseif (isset($parameters['userName'],$parameters['password'])) {
		//process login
		$authentication = authentication($parameters['userName'],$parameters['password']);
		if($authentication){
			//success
			$app->setCookie("storedToken",$authentication	,"2 days");
			$app->setCookie("storedUserName",$parameters['userName'],"2 days");
			echo "Successfully logged in.";	
		} else {
			//failure
			echo "Login Failed";
		}
	}
	$app->render('footer.php',array());	
});

//Get player list
$app->get('/profile', function () use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Players'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		$apicall = curlget($baseurl."player/".$cookies['storedUserName']);
		if ($apicall['info']['http_code'] != "404") {
			echo "<a href='". $baseurl . "profile/" . $cookies['storedUserName'] ."'>Edit profile</a><br>";
		} else {
			echo "<a href='". $baseurl . "profile/" . $cookies['storedUserName'] ."'>Create profile</a><br>";
		}
	}
	$apicall = curlget($baseurl."player/");
	echo "<table border='1'>";
	echo "<tr><td>User Name</td><td>Player Name</td><td>Home Page</td></tr>";
	foreach (json_decode($apicall['response']) as $row) {
		echo "<tr><td>";
		echo $row->username ."</td><td>";
		echo $row->name ."</td><td>";
		echo "<a href='".$row->homepage."'>".$row->homepage."</a></td><td>";

		echo "</td></tr>";
	}
	$app->render('footer.php',array());
});

//Get specific profile
$app->get('/profile/:userName', function ($userName) use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Players'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	$apicall = curlget($baseurl."player/".$userName);
	$response =json_decode($apicall['response']);
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		
		if (isset($response->name) || isset($response->homepage)){
			$app->render('profileform.php',array("name"=>$response->name,"homepage"=>$response->homepage,"baseurl"=>$baseurl));
		} else {
			$app->render('profileform.php',array("name"=>"","homepage"=>"","baseurl"=>$baseurl));
		}

	} else {
		echo "You are not logged in.";
	}
	

	$app->render('footer.php',array());
});

$app->post('/profile', function () use ($app,$baseurl) {
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Create/Update Profile'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	$postVars = array();
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		if (isset($parameters['name'])){
			$postVars['name'] = $parameters['name'];
		}
		if (isset($parameters['homepage'])){
			$postVars['homepage'] = $parameters['homepage'];
		}

		if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
			$apicall = curlget($baseurl."player/".$cookies['storedUserName']);
			if ($apicall['info']['http_code'] != "404") {
				$apicall = curlput($baseurl."player/".$cookies['storedUserName'],array('storedUserName'=>$cookies['storedUserName'],'storedToken'=>$cookies['storedToken']),$postVars);
				echo "Profile updated.";
			} else {
				$apicall = curlpost($baseurl."player/",array('storedUserName'=>$cookies['storedUserName'],'storedToken'=>$cookies['storedToken']),$postVars);
				echo "Profile created.";
			}
		}

	} else {
		echo "You are not logged in.";

	}
	$app->render('footer.php',array());

});

//process logout
$app->get('/logout', function () use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Login'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		deauthenticate($cookies['storedUserName'],$cookies['storedToken']);
		$app->deleteCookie('storedToken');
		$app->deleteCookie('storedUserName');
		echo "You are no longer logged in.";
	} else {
		echo "You are not logged in.";
	}
	$app->render('footer.php',array());
});

//get registration form
$app->get('/register', function () use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Register'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in
		echo "You don't need to register, you're already logged in.";
	} else {
		//get registration form
		$app->render('regform.php',array());
	}
	$app->render('footer.php',array());	
});

//process registration
$app->post('/register', function () use ($app,$baseurl) {
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Register'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in
		echo "You don't need to register, you're already logged in.";
	} else {
		//process registration
		if (isset($parameters['userName'],$parameters['password'],$parameters['email'])){
			$registration = registration($parameters['userName'],$parameters['password'],$parameters['email']);
			if ($registration) {
				echo "Registration succeeded.";
			} else {
				//more checking and reporting should be added here
				echo "Registration failed.";
			}
		} else {
			echo "All of User Name, E-mail and Password are required to register.";
		}
	}
	$app->render('footer.php',array());
});

//request a password change/reset
$app->get('/password', function () use ($app,$baseurl) {
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Password'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in offer password change form
		$app->render('passwordform.php',array());
	} else {
		//offer password reset
		$app->render('passwordreset.php',array());
	}
	$app->render('footer.php',array());
});

//process password change
$app->post('/password', function () use ($app,$baseurl) {
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Password'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	if (authCheck($cookies['storedUserName'],$cookies['storedToken'])){
		//already logged in process password change

		if (pwchange($cookies['storedUserName'],$parameters['password'],$parameters['newPassword'],$parameters['repeatNewPassword'])) {
			echo "Password change successful.";
		} else {
			//more checking and reporting should be added here
			echo " - Password change failed.";
		}
		
	} else {
		//report password reset
		if (pwreset($parameters['userName'])){
			echo "Password reset and emailed to user.";
		} else {
			echo " - There was a problem with the password reset.";
		}
	}
	$app->render('footer.php',array());
});

//get a list of games
$app->get('/games', function () use ($app,$baseurl) {
	$app->render('header.php',array('title'=>'Games List'));
	$app->render('menu.php',array("baseurl"=>$baseurl));
	//get list of games
	$gamelist = gamelist();
	if ($gamelist) {
		echo "<table border='1'>";
		echo "<tr><td>Game</td><td>Description</td><td>Genre</td><td>URL</td></tr>";
		
		foreach (json_decode($gamelist) as $row) {
			echo "<tr><td>";
			echo $row->name ."</td><td>";
			echo $row->description ."</td><td>";
			echo $row->genre ."</td><td>";
			echo "<a href = '".$row->url."'>".$row->url ."</a></td></tr>";
		}
		echo "</table>";
		//echo $gamelist;
	} else {
		echo "Error fetching game list.";
	}
	$app->render('footer.php',array());
});

$app->get('/admin/', function () use ($app,$baseurl) {
	$app->render('header.php',array('title'=>'Admin'));
	$app->render('amenu.php',array("baseurl"=>$baseurl));

	$app->render('footer.php',array());
});

$app->get('/admin/service', function () use ($app,$baseurl) {
	//list services
	$app->render('header.php',array('title'=>'Service Admin'));
	$app->render('amenu.php',array("baseurl"=>$baseurl));
	//get list of services
	$servicelist = servicelist();
	if ($servicelist) {
		echo "<table border='1'>";
		echo "<tr><td>Service</td><td>Description</td><td>Status</td><td>Type</td><td>URL</td><td>Delete</td></tr>";
		
		foreach (json_decode($servicelist) as $row) {
			echo "<tr><td>";
			echo $row->name ."</td><td>";
			echo $row->description ."</td><td>";
			echo $row->status ."</td><td>";
			echo $row->type ."</td><td>";
			echo "<a href = '".$row->url."'>".$row->url ."</a></td><td>";
			echo "<form action='service' method='POST'><input type='hidden' name='action' value='delete'><input type='hidden' name='serviceName' value='".$row->name."'><input type='submit' value='Delete'></form></td><td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<hr><u>Register New Service</u><br><form action='service' method='POST'><input type='hidden' name='action' value='register'>";
		echo "Service Name:<br><input type='text' name='serviceName'><br>";
		echo "Service Description:<br><input type='text' name='description'><br>";
		echo "Service Status:<br><select name='status'><option value='open'>open</option><option value='closed'>closed</option></select><br>";
		echo "Service Type:<br><select name='serviceType'><option value='user'>user</option><option value='subdirectory'>subdirectory</option><option value='game'>game</option><option value='other'>other</option></select><br>";
		echo "Service URL:<br><input type='text' name='url'><br>";
		echo "<br><input type='submit' value='Register Service'></form><br>";
	} else {
		echo "Error fetching service list.";
	}
	$app->render('footer.php',array());
});

$app->post('/admin/service', function () use ($app,$baseurl) {
	//admin services
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'Service Admin'));
	$app->render('amenu.php',array("baseurl"=>$baseurl));
	if ($parameters['action'] == "register") {
		regservice($parameters['serviceName'],$parameters['url'],$parameters['status'],$parameters['serviceType'],$parameters['description'],$cookies['storedUserName'],$cookies['storedToken']);
		echo "Service Registered.";
	} elseif ($parameters['action'] == "delete") {
		delservice($parameters['serviceName'],$cookies['storedUserName'],$cookies['storedToken']);
		echo "Service Deleted.";
	} else {


	}

	$app->render('footer.php',array());
});

$app->get('/admin/game', function () use ($app,$baseurl) {
	$app->render('header.php',array('title'=>'Games Admin'));
	$app->render('amenu.php',array("baseurl"=>$baseurl));
	//get list of games
	$gamelist = gamelist();
	if ($gamelist) {
		echo "<table border='1'>";
		echo "<tr><td>Game</td><td>Description</td><td>Genre</td><td>URL</td><td>Delete</td></tr>";
		
		foreach (json_decode($gamelist) as $row) {
			echo "<tr><td>";
			echo $row->name ."</td><td>";
			echo $row->description ."</td><td>";
			echo $row->genre ."</td><td>";
			echo "<a href = '".$row->url."'>".$row->url ."</a></td><td>";
			echo "<form action='game' method='POST'><input type='hidden' name='action' value='delete'><input type='hidden' name='gameName' value='".$row->name."'><input type='submit' value='Delete'></form></td><td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<hr><u>Register New Game</u><br><form action='game' method='POST'><input type='hidden' name='action' value='register'>";
		echo "Game Name:<br><input type='text' name='gameName'><br>";
		echo "Game Description:<br><input type='text' name='description'><br>";
		echo "Game Genre:<br><select name='genre'><option value='board'>board</option><option value='card'>card</option><option value='other'>other</option></select><br>";
		echo "Game URL:<br><input type='text' name='url'><br>";
		echo "<br><input type='submit' value='Register game'></form><br>";
	} else {
		echo "Error fetching game list.";
	}
	$app->render('footer.php',array());
});

$app->post('/admin/game', function () use ($app,$baseurl) {
	//admin games
	$parameters = $app->request->params();
	$cookies = $app->request->cookies;
	$app->render('header.php',array('title'=>'game Admin'));
	$app->render('amenu.php',array("baseurl"=>$baseurl));
	if ($parameters['action'] == "register") {
		reggame($parameters['gameName'],$parameters['url'],$parameters['genre'],$parameters['description'],$cookies['storedUserName'],$cookies['storedToken']);
		echo "Game Registered.";
	} elseif ($parameters['action'] == "delete") {
		delgame($parameters['gameName'],$cookies['storedUserName'],$cookies['storedToken']);
		echo "Game Deleted.";
	} else {


	}

	$app->render('footer.php',array());
});

$app->get('/docs', function () use ($app,$baseurl) {
	$app->render('header.php',array('title'=>'System Documentation'));
	$app->render('dmenu.php',array("baseurl"=>$baseurl));

	$app->render('footer.php',array());
});


$app->get('/docs/raml', function () use ($app,$baseurl) {
	$app->render('header.php',array('title'=>'RAML Specifications'));
	$app->render('dmenu.php',array("baseurl"=>$baseurl));
	$app->render('raml.php',array("baseurl"=>$baseurl));
	$app->render('footer.php',array());
});

$app->run();

function authCheck($userName,$token){
	//quick and dirty
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/".$userName."/".$token);
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return ($info['http_code']=='204');
	} else {
		return FALSE;
	}
}

function authentication($userName,$password){
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/".$userName);
		curl_setopt($ch, CURLOPT_POST, 1);
		//should really be used only over https... 
		curl_setopt($ch, CURLOPT_POSTFIELDS,"password=".$password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info['http_code']=='200') {
			return $response;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

function registration($userName,$password,$email){
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/");
		curl_setopt($ch, CURLOPT_POST, 1);
		//should really be used only over https... 
		curl_setopt($ch, CURLOPT_POSTFIELDS,"password=".$password."&userName=".$userName."&email=".$email);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code']=='200') {
			return $response;
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

function deauthenticate($userName,$token){
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/".$userName."/".$token);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); 
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return ($info['http_code']=='204');
	} else {
		return FALSE;
	}
}

function pwreset($userName){
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/".$userName);
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return ($info['http_code']=='204');
	} else {
		return FALSE;
	}
}

function pwchange($userName,$password,$newPassword,$repeatNewPassword){
	global $baseurl;
	if ($userName != "") {
		$ch = curl_init($baseurl."auth/".$userName);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
		curl_setopt($ch, CURLOPT_POSTFIELDS,"password=".$password."&newPassword=".$newPassword."&repeatNewPassword=".$repeatNewPassword);
		curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return ($info['http_code']=='204');
	} else {
		return FALSE;
	}
}

function gamelist() {
	global $baseurl;
	$ch = curl_init($baseurl."game/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	if ($info['http_code']=='200') {
		return $response;
	} else {

		return FALSE;
	}

}

function servicelist(){
	global $baseurl;
	$ch = curl_init($baseurl."service/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	if ($info['http_code']=='200') {
		return $response;
	} else {

		return FALSE;
	}

}

function regservice($serviceName,$url,$status,$serviceType,$description,$userName,$token){
	global $baseurl;
	$ch = curl_init($baseurl."service/");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_COOKIE, "storedUserName=".$userName."; storedToken=".$token);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"serviceName=".$serviceName."&url=".$url."&status=".$status."&serviceType=".$serviceType."&description=".$description);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code']=='201') {
		return $response;
	} else {

		return FALSE;
	}

}

function delservice($serviceName,$userName,$token){
	global $baseurl;
	$ch = curl_init($baseurl."service/".$serviceName);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($ch, CURLOPT_COOKIE, "storedUserName=".$userName."; storedToken=".$token);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code']=='204') {
		return $response;
	} else {

		return FALSE;
	}

}

function reggame($gameName,$url,$genre,$description,$userName,$token){
	global $baseurl;
	$ch = curl_init($baseurl."game/");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_COOKIE, "storedUserName=".$userName."; storedToken=".$token);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"gameName=".$gameName."&url=".$url."&genre=".$genre."&description=".$description);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code']=='201') {
		return $response;
	} else {

		return FALSE;
	}

}

function delgame($gameName,$userName,$token) {
	global $baseurl;
	$ch = curl_init($baseurl."game/".$gameName);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($ch, CURLOPT_COOKIE, "storedUserName=".$userName."; storedToken=".$token);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if ($info['http_code']=='204') {
		return $response;
	} else {

		return FALSE;
	}

}

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
