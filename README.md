# sso-client

## Configuration

```php
// config/sso-client.php
return [
	"sso-client" => [
		'auth-url' => "http://id.mik.pte.hu",
		'auth-timeout'  => 60 * 60 * 1,
	],
];
```

### Atomino.ini

```
sso-client.app-key = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
sso-client.secret = "xxxxxxxxxxxxx"
```

## DI

```php
// di/sso-client.php
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

## SSOClient

```php
// src/services/sso-client.php
//
class SSOClient extends AbstractSSOClient {
	public function userFactory(SSOUser $ssoUser):AuthenticableInterface{
		return User::search(Filter::where(User::guid($ssoUser->guid)))->pick();
	}
}
```
