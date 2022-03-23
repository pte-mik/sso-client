<?php namespace PTE\MIK\SSO;

interface SSOClientInterface {
	public function authRedirect(string $returnUrl, string|array|null $group = null): void;
	public function authenticate(string $tokenString): void;
	public function logout(string $returnUrl): void;
}
