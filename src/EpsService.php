<?php

class EpsService
{
	private $httpVersion = "HTTP/1.1";
	private $url;
	private $data;
	const ENV_DEV = 'dev';
	const ENV_PROD = 'prod';
	const ENV_TEST = 'test';
	/**
	 * @var string[]
	 */
	private $errorMessages = [
		400 => 'Плохой запрос. Запрос не соответствует спецификации',
		403 => 'Запрос не прошел авторизацию. Неверная цифровая подпись',
		500 => 'Внутренняя ошибка. Сервер 1С не отвечает',
		502 => 'Неопознанная ошибка',
	];
	/**
	 * @var array|false|string
	 */
	private $epsUser;
	/**
	 * @var array|false|string
	 */
	private $epsPass;
	/**
	 * @var
	 */
	private $isDev;
	/**
	 * @var
	 */
	private $isTest;
	/**
	 * @var
	 */
	private $username1c;
	/**
	 * @var
	 */
	private $password1c;

	/**
	 * @var array
	 */

	public function __construct($envParam)
	{
		$this->initEnv($envParam);
	}

	private function initEnv($envParam)
	{
		header("Content-Type:application/json");
		$this->isDev = ($envParam == self::ENV_DEV);
		$this->isTest = ($envParam == self::ENV_TEST);
		$this->url = $this->isDev ? getenv('DEV_URL_1C') : getenv('URL_1C');
		$this->epsUser = $this->isDev ? getenv('DEV_EPS_USER') : getenv('EPS_USER');
		$this->epsPass = $this->isDev ? getenv('DEV_EPS_PASS') : getenv('EPS_PASS');
		$this->username1c = $this->isDev ? getenv('DEV_1C_USER') : getenv('1C_USER');
		$this->password1c = $this->isDev ? getenv('DEV_1C_PASS') : getenv('1C_PASS');
	}

	public function auth() : bool
	{
		$isAuth = false;

		if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
			return $isAuth;
		}
		if (
			trim($_SERVER['PHP_AUTH_USER']) == $this->epsUser
			&&
			trim($_SERVER['PHP_AUTH_PW']) == $this->epsPass
		) {
			$isAuth = true;
		}

		return $isAuth;
	}

	public function validate(array $data) : bool
	{
		$isValid = false;
		if (isset($data['id']) && !empty($data['id'])) {
			$isValid = true;
		}
		return $isValid;
	}

	public function load($data) : bool
	{
		$this->data = $data;
		return true;
	}

	public function send() : bool
	{
		$ch = curl_init( $this->url );
		$payload = json_encode( $this->data );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_USERPWD, $this->username1c . ":" . $this->password1c);
		$result = $this->isTest ? $this->responceData : curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);
		if (!empty($err)){
			$this->response(502, $err);
		}
		print_r($result);
		return true;
	}

	public function response($code, $message = '') : bool
	{
		header($this->httpVersion. " ". $code ." ". $message);
		print_r($this->errorMessages[$code].', message:'.$message, $code);
		die();
	}
	private $responceData = '{
    "OrderNum": "MS06-041910",
    "ОrderDetails": {
        "id": "1234567890ABCDEF00000000",
        "orderitems": [
            {
                "orderitem": "AU105PRG"
            },
            {
                "orderitem": "AU105PRG"
            }
        ],
        "signature": "dfa1376424234fa",
        "Comments": "ООО Тест, test@gmail.com"
    },
    "ErrorСode": 0,
    "ErrorDescr": "Заказ уже был загружен",
    "ErrorsExists": false
}';

}