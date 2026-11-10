# omnipulse/tripadvisor

Tripadvisor for [omnisense/omnipulse](https://github.com/glitchr-omnisense/omnipulse): a
location's rating, its bubbles, its number of reviews, up to five reviews and the link to write
one, through the **Tripadvisor Content API** (v1). The provider calls:

| Operation | Request |
|---|---|
| rating, write link | `GET https://api.content.tripadvisor.com/api/v1/location/{locationId}/details?key=&language=` |
| reviews | `GET https://api.content.tripadvisor.com/api/v1/location/{locationId}/reviews?key=&language=&limit=` |

The base address is the constant `TripadvisorProvider::URL`. The Content API has no sandbox.
Replying and inviting are not in the Content API.

## Installation

```sh
composer require omnipulse/tripadvisor
```

Requires PHP 8.2, `omnisense/omnipulse` (1.x) and `symfony/http-client` (6.4, 7 or 8). In a
Symfony application with `OmnipulseBundle`, the factory is registered as soon as the package is
installed.

## Credentials

- A **Content API key**, from the Tripadvisor Developer portal, restricted to the site's domains
  (then set `referer`) or to the server's addresses.
- The **location ID** of the establishment (the `d1234567` of its Tripadvisor address, without the
  `d`): it may be kept.

## Options

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | sent as the `key` query parameter |
| `language` | string | `en` | no | the content's language (`fr`, `de`, `ja`...): every details call, and the reviews when a call gives no `$language` |
| `referer` | string | `null` | no | the `Referer` header sent with every call: for a key restricted to domains |

`Place` fields read: `id` (the location ID).

## Capabilities

| Capability | Supported |
|---|---|
| `RatingInterface` | yes |
| `ReviewsInterface` | yes, 5 at most |
| `ReplyInterface` | no (the provider does not implement it) |
| `InviteInterface` | no (the provider does not implement it) |
| `WriteUrlInterface` | yes |

`capabilities()->contracts()`: `rating, reviews, writeUrl`.

## Behaviour

**Details.** `rating()` and `writeUrl()` read the location's `/details` in the provider's `language`,
asked once per location and language for the provider's lifetime (a PHP process, or a request in a
long-running worker): a widget's rating and write link are one call.

**Rating.** `value` is `rating`, `count` `num_reviews`, `url` `web_url`, `image` `rating_image_url`
(the bubbles, to show from Tripadvisor's address).

**Reviews.** `limit` = `$limit` between 1 and 5, `language` the call's or the option's. Each
review: `id`, `rating`, `text`, `title`, `language` (`lang`), `author` (`user.username`),
`authorPhoto` (`user.avatar.small`, else `thumbnail`), `publishedAt` (`published_date`), `url`
(the review's page), `reply` from `owner_response` (`text`, `published_date`), `translated` from
`is_machine_translated`, `image` the review's own bubbles (`rating_image_url`). No `authorUrl`.

**Write link.** The `write_review` of the details; a location without one is a
`ProviderException` (`The location gives no write_review link.`).

**Every call** sends `Accept: application/json`, the `Referer` when set, a 10-second timeout.

**Terms** (`terms()`), from the Content API's display requirements
([tripadvisor-content-api.readme.io/reference/display-requirements](https://tripadvisor-content-api.readme.io/reference/display-requirements)),
read on 2026-10-08:

| | |
|---|---|
| `cacheFor` | `null`: only as Tripadvisor's Caching Policy permits, a document given to partners and not public - the Twig functions keep nothing |
| `maxReviews` | `5` |
| `attribution` | `Tripadvisor` |
| `authors`, `link` | `true` |
| `noIndex` | `true`: every page that shows Tripadvisor's content kept out of search engines |

and in `rules`: the Tripadvisor logo (Ollie) with a rating, at least 20 pixels high, and the
bubbles the API gives, at least 55 pixels wide, served from Tripadvisor's addresses - never stored
nor drawn anew; a traveller's review quoted within quotation marks, with its date, as "a
Tripadvisor traveler review"; the month and year of any ranking cited; the location ID may be
kept. The core's widget shows the bubbles when given and names Tripadvisor in words, with
`data-nosnippet`; the logo and the page's `<meta name="robots" content="noindex">` are the
application's.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnipulse\Model\Place;
use Omnipulse\Tripadvisor\TripadvisorFactory;
use Symfony\Component\HttpClient\HttpClient;

$tripadvisor = (new TripadvisorFactory(HttpClient::create()))->create([
    'api_key' => getenv('TRIPADVISOR_KEY'),
    'language' => 'fr',
    'referer' => 'https://www.maison-erable.example/',
]);

$location = new Place('1234567');
$rating = $tripadvisor->rating($location);
printf("%.1f from %d reviews - %s\n<img src=\"%s\" width=\"55\">\n", $rating->value, $rating->count, $rating->url, $rating->image);
foreach ($tripadvisor->reviews($location, 5) as $review) {
    printf("\"%s\" - %s, %s (a Tripadvisor traveler review)\n", $review->title, $review->author, $review->publishedAt?->format('F Y'));
}
echo $tripadvisor->writeUrl($location), "\n";      // no second call: the details are kept
```

Not run live (no key). In Symfony:

```yaml
omnipulse:
    providers:
        tripadvisor:
            factory: tripadvisor
            options:
                api_key: '%env(TRIPADVISOR_KEY)%'
                language: fr
                referer: 'https://www.maison-erable.example/'
```

```twig
{% if omnipulse_terms('tripadvisor').noIndex %}<meta name="robots" content="noindex">{% endif %}
{{ omnipulse_reviews_widget('tripadvisor', location_id) }}
```

## Errors

| Case | Exception | `providerCode` |
|---|---|---|
| HTTP 401 or 403 | `InvalidKeyException`, `The key was refused (HTTP <status>): <error.message>` | the HTTP status |
| another 4xx, or a body with `error` or `Message` whatever the status | `ProviderException`, `HTTP <status>: <error.message or error.type>` | `error.code` when given |
| no answer, a 5xx, a 429 | `UnreachableException` | the HTTP status |
| a body that is not JSON | `ProviderException` | - |
| no `write_review` | `ProviderException` | - |
| no `api_key` | `InvalidConfigException` (`The "tripadvisor" provider needs: api_key.`), when the provider is built | - |

## Verified, and not

Tested offline (`Tests/TripadvisorProviderTest.php`, on `MockHttpClient`): the rating with its
bubbles and the write link from one details call, the query (`key`, `language`) and the
`Referer`; the reviews with `limit=5`, their reply, author, title, date and own bubbles; the terms
(`noIndex`, `cacheFor` `null`); a 401 as an `InvalidKeyException`. The answers in `Tests/Fixtures`
are written from the Content API's reference (`/details`, `/reviews`, the error body).

Not verified: **no call was made to Tripadvisor** - no key. The Caching Policy was not read.

## Responsibility

An independent, unofficial client, not affiliated with Tripadvisor LLC. Whoever runs it complies
with the Content API's terms ([Content API terms of
use](https://tripadvisor-content-api.readme.io/reference/terms-of-use)): see the README's
Responsibility section.
