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

	abstract protected function userFactory(SSOUser $ssoUser):AuthenticableInterface;

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

	#[NoReturn] public function authRedirect(string $returnUrl): void {
		$response = new RedirectResponse($this->authUrl . self::AUTH_REQUEST_URL . '?token=' . $this->createToken($returnUrl));
		$response->send();
		die();
	}

	protected function createToken(string $returnUrl): string {
		$jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->secret));

		return $jwtConfig->builder()
		                 ->issuedBy($this->appKey)
		                 ->withClaim(self::RETURN_URL_KEY, $returnUrl)
		                 ->expiresAt(\DateTimeImmutable::createFromFormat('U', time() + $this->requestTimeout))
		                 ->getToken($jwtConfig->signer(), $jwtConfig->signingKey())
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
		$response = new RedirectResponse($this->authUrl . self::LOGOUT_URL . '?token=' . $this->createToken($returnUrl));
		$this->sessionAuthenticator->logout($response);
		$response->send();
		die();
	}

}
