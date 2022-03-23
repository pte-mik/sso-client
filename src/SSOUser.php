<?php namespace Application\Missions\Web;

class SSOUser {

	const AVATAR_SIZE_64 = "64";
	const AVATAR_SIZE_128 = "128";
	const AVATAR_SIZE_256 = "256";
	const AVATAR_SIZE_512 = "512";
	const AVATAR_SIZE_1024 = "1024";

	public string $name;
	public string $email;
	public string $guid;
	public string $neptun;
	public string $group;
	/** @var array */
	public array $avatars = [];

	public function hasAvatar():bool{
		return array_key_exists(self::AVATAR_SIZE_64, $this->avatars) && !is_null($this->avatars[self::AVATAR_SIZE_64]);
	}

	public function __construct($data) {
		$this->avatars = $data['avatars'];
		$this->avatars[] = 12;
		$this->name = $data['name'];
		$this->guid = $data['guid'];
		$this->email = $data['email'];
		$this->group = $data['group'];
		$this->neptun = $data['neptun'];
	}
}