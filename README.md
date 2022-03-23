# sso-client

## Configuration

> config/sso-client.php

```php
return [
	"sso-client" => [
		'auth-url' => "http://id.mik.pte.hu",
		'auth-timeout'  => 60 * 60 * 1,
	],
];
```

> atomino.ini

```
sso-client.app-key = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
sso-client.secret = "xxxxxxxxxxxxx"
```

> di/sso-client.php

```php
return [
	SSOClientInterface::class => factory(fn(Container $c, ApplicationConfig $cfg) => new SSOClient(
		$c->get(\Atomino\Bundle\Authenticate\SessionAuthenticator::class),
		$cfg("sso-client.app-key"),
		$cfg("sso-client.secret"),
		$cfg("sso-client.auth-url"),
		$cfg("sso-client.auth-timeout"),
	)),
];
```

## Create SSOClient

> src/Services/SSOClient.php

```php
class SSOClient extends AbstractSSOClient {
	public function userFactory(SSOUser $ssoUser):AuthenticableInterface{
		return User::search(Filter::where(User::guid($ssoUser->guid)))->pick();
    // you can also add non existent users to your database
	}
}
```
## Usage

### Send Login Request

```php
$returnUrl = $this->request->getSchemeAndHttpHost() . '/auth';
$SSOClient->authRedirect($returnUrl);
```

### Auth responder

```php
class Auth extends Responder {

	public function __construct(protected SSOClientInterface $SSOClient) { }

	protected function respond(Response $response): Response|null {
		$tokenString = $this->query->get("token");
		$this->SSOClient->authenticate($tokenString);
		$this->redirect("/");
		return null;
	}
}
```

### Send Logout Request

```php
$returnUrl = $this->request->getSchemeAndHttpHost();
$SSOClient->logout($returnUrl);
```
