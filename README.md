# omnipulse/tripadvisor

Formerly `omnireview/tripadvisor` (moved on 2026-11-10, when omnireview and omnifund merged into
omnipulse: `Omnireview\Tripadvisor\` is now `Omnipulse\Tripadvisor\`, `TripadvisorGateway` is
`TripadvisorProvider`, `TripadvisorGatewayFactory` is `TripadvisorFactory`; this package replaces
the former name).

**Tripadvisor** for [omnisense/omnipulse](https://github.com/glitchr-omnisense/omnipulse): a
location's rating, its bubbles, its number of reviews, up to five reviews and the link to write
one, through the Tripadvisor Content API.

```php
use Omnipulse\Model\Place;
use Omnipulse\Tripadvisor\TripadvisorFactory;

$provider = (new TripadvisorFactory($httpClient))->create(['api_key' => getenv('TRIPADVISOR_KEY'), 'language' => 'fr']);

$location = new Place('1234567');                     // the location ID: it may be kept
$provider->rating($location);                          // 4.5 from 312, the bubbles' image, the location's page
$provider->reviews($location);                         // up to five
$provider->writeUrl($location);
```

Written from the Content API's reference and display requirements, read on 2026-10-08. **Not
verified in real: no key.**

[Documentation](docs/index.md): the options, the calls, Tripadvisor's display rules, what was
verified.

## Responsibility

This package is an independent, unofficial client: it is not affiliated with, endorsed by or
sponsored by Tripadvisor LLC. It reaches their service on behalf of whoever runs it, and it does not
redistribute their data beyond the short excerpts recorded as test fixtures. Its MIT licence covers
this package's code only and grants no right to the service or to its data.

Whoever installs and runs this package is responsible for complying with the Tripadvisor Content
API's terms ([Content API terms of
use](https://tripadvisor-content-api.readme.io/reference/terms-of-use)), for obtaining whatever they
require (an account, a key, a licence or subscription, an attribution or a citation, a rate limit to
respect), and for any use made of what it returns. The authors publish it free of charge, make no
commercial use of it, and accept no liability for how it is used (see [LICENSE](LICENSE)).

License: MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
