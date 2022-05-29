<?php

require_once("EpsService.php");
require_once("DotEnv.php");

(new DotEnv(__DIR__ . '/.env'))->load();

$data = json_decode(file_get_contents("php://input"), true);

$service = new EpsService(getenv('APP_ENV'));

if (empty($data)) {
	return $service->response(405);
}

if(!$service->auth()){
	return $service->response(401);
}

if(!$service->validate($data)){
	return $service->response(400);
}

if(!$service->load($data)){
	return $service->response(412);
}

if(!$service->send()){
	return $service->response(500);
}


