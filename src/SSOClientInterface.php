<?php
namespace Application\Missions\Web;

interface SSOClientInterface {
	public function authRedirect(string $returnUrl): void;
	public function authenticate(string $tokenString): void;
	public function logout(string $returnUrl): void;
}