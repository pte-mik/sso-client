<?php namespace PTE\MIK\SSO;

use Atomino\Bundle\Authenticate\AuthenticableInterface;
use Atomino\Bundle\Authenticate\SessionAuthenticator;
use JetBrains\PhpStorm\NoReturn;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class AbstractSSOClient implements SSOClientInterface {

	const RETURN_URL_KEY = "return-url";
	const AUTH_REQUEST_URL = "/auth-request";
	const LOGOUT_URL = "/logout";
	const GROUPS_KEY = "groups";

	abstract protected function userFactory(SSOUser $ssoUser): AuthenticableInterface;

	public function __construct(
		protected SessionAuthenticator $sessionAuthenticator,
		protected string               $appKey,
		protected string               $secret,
		protected string               $authUrl,
		protected int                  $authTimeout = 60 * 60 * 1,
		protected int                  $requestTimeout = 30,
	) {
		$this->authUrl = trim($this->authUrl, "/");
	}

	#[NoReturn] public function authRedirect(string $returnUrl, string|array|null $group = null): void {
		if(is_string($group)) $group = [$group];
		$response = new RedirectResponse($this->authUrl . self::AUTH_REQUEST_URL . '?token=' . $this->createToken([self::RETURN_URL_KEY => $returnUrl, self::GROUPS_KEY => $group]));
		$response->send();
		die();
	}

	protected function createToken(array $claims = []): string {
		$jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->secret));

		$builder = $jwtConfig->builder()
		                     ->issuedBy($this->appKey)
		                     ->expiresAt(\DateTimeImmutable::createFromFormat('U', time() + $this->requestTimeout));
		foreach ($claims as $key => $value) $builder->withClaim($key, $value);
		return $builder->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
		               ->toString()
		;
	}

	public function authenticate(string $tokenString): void {
		$jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->secret));
		$token = $jwtConfig->parser()->parse($tokenString);
		$isValidRequest = $jwtConfig->validator()->validate($token, new SignedWith($jwtConfig->signer(), $jwtConfig->signingKey()));
		if ($isValidRequest) {
			$userData = $token->claims()->get("user");
			$ssoUser = new SSOUser($userData);
			$user = $this->userFactory($ssoUser);
			$this->sessionAuthenticator->deployAuthToken($this->sessionAuthenticator->getAuthenticator()->createAuthToken($user, $this->authTimeout));
		}
	}

	#[NoReturn] public function logout(string $returnUrl): void {
		$response = new RedirectResponse($this->authUrl . self::LOGOUT_URL . '?token=' . $this->createToken([self::RETURN_URL_KEY => $returnUrl]));
		$this->sessionAuthenticator->logout($response);
		$response->send();
		die();
	}

}
