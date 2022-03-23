<?php namespace PTE\MIK\SSO;

interface SSOClientInterface {
	public function authRedirect(string $returnUrl): void;
	public function authenticate(string $tokenString): void;
	public function logout(string $returnUrl): void;
}
