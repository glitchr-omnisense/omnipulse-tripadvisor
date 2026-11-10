<?php

namespace Omnipulse\Tripadvisor;

use Omnipulse\Capabilities;
use Omnipulse\Exception\InvalidKeyException;
use Omnipulse\Exception\ProviderException;
use Omnipulse\Http\Answer;
use Omnipulse\Model\Place;
use Omnipulse\Model\Rating;
use Omnipulse\Model\Reply;
use Omnipulse\Model\Review;
use Omnipulse\Model\Terms;
use Omnipulse\RatingInterface;
use Omnipulse\ReviewsInterface;
use Omnipulse\Support\Data;
use Omnipulse\WriteUrlInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tripadvisor's Content API: a location's details (rating, num_reviews,
 * web_url, write_review, the bubbles' picture) and its reviews - up to five,
 * the most recent. The partner key in the query.
 */
final class TripadvisorProvider implements RatingInterface, ReviewsInterface, WriteUrlInterface
{
    public const URL = 'https://api.content.tripadvisor.com/api/v1/location/';

    /** @var array<string, Data> the details asked in this request, by location and language */
    private array $details = [];

    public function __construct(
        private readonly HttpClientInterface $http,
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly string $language = 'en',
        private readonly ?string $referer = null,
    ) {
    }

    public function getName(): string
    {
        return 'tripadvisor';
    }

    public function getTitle(): string
    {
        return 'Tripadvisor';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(rating: true, reviews: true, reply: false, invite: false, writeUrl: true);
    }

    public function terms(): Terms
    {
        return new Terms(
            cacheFor: null,
            maxReviews: 5,
            attribution: 'Tripadvisor',
            authors: true,
            link: true,
            noIndex: true,
            rules: [
                'Show the Tripadvisor logo (Ollie) with a rating, at least 20 pixels high, and the bubble ratings the API gives (rating_image_url), at least 55 pixels wide, served from Tripadvisor\'s addresses - never stored nor drawn anew.',
                'Quote a traveller\'s review within quotation marks, with its date, as "a Tripadvisor traveler review".',
                'Cite the month and year of any ranking.',
                'Make every page that shows Tripadvisor\'s content non-indexable by search engines.',
                'Cache only as Tripadvisor\'s Caching Policy permits; the location ID may be kept.',
            ],
            source: 'https://tripadvisor-content-api.readme.io/reference/display-requirements',
        );
    }

    public function rating(Place $place): Rating
    {
        $details = $this->details($place, $this->language);

        return new Rating('tripadvisor', $details->float('rating') ?? 0.0, $details->int('num_reviews') ?? 0, url: self::str($details, 'web_url'), image: self::str($details, 'rating_image_url'));
    }

    public function reviews(Place $place, int $limit = 5, ?string $language = null): array
    {
        $data = $this->call($place->id.'/reviews', ['language' => $language ?? $this->language, 'limit' => max(1, min(5, $limit))]);

        return \array_slice(array_map(static fn (Data $r) => new Review(
            'tripadvisor',
            self::str($r, 'id') ?? '',
            $r->float('rating') ?? 0.0,
            self::str($r, 'text'),
            self::str($r, 'title'),
            self::str($r, 'lang'),
            self::str($r, 'user.username'),
            authorPhoto: self::str($r, 'user.avatar.small') ?? self::str($r, 'user.avatar.thumbnail'),
            publishedAt: self::date($r->raw('published_date')),
            url: self::str($r, 'url'),
            reply: null !== ($reply = self::str($r, 'owner_response.text')) ? new Reply($reply, self::date($r->raw('owner_response.published_date'))) : null,
            translated: (bool) $r->raw('is_machine_translated'),
            image: self::str($r, 'rating_image_url'),
        ), array_values(array_filter($data->list('data'), static fn (Data $r): bool => [] !== $r->values))), 0, $limit);
    }

    public function writeUrl(Place $place): string
    {
        return self::str($this->details($place, $this->language), 'write_review') ?? throw ProviderException::from('tripadvisor', 'The location gives no write_review link.');
    }

    private function details(Place $place, string $language): Data
    {
        return $this->details[$place->id.'|'.$language] ??= $this->call($place->id.'/details', ['language' => $language]);
    }

    /** @param array<string, mixed> $query */
    private function call(string $path, array $query): Data
    {
        $answer = Answer::send($this->http, 'tripadvisor', 'GET', self::URL.$path, ['query' => ['key' => $this->apiKey] + $query, 'headers' => array_filter(['Accept' => 'application/json', 'Referer' => $this->referer])]);
        $data = Data::of(json_decode($answer->body, true));
        $error = $data->raw('error') ?? $data->raw('Message');
        $details = Data::of($error);
        $said = \is_scalar($error) ? (string) $error : null;
        if (\in_array($answer->status, [401, 403], true)) {
            throw InvalidKeyException::from('tripadvisor', \sprintf('The key was refused (HTTP %d): %s', $answer->status, \is_array($error) ? (self::str($details, 'message') ?? '') : (string) $said), (string) $answer->status);
        }
        if ($answer->status >= 400 || null !== $error) {
            throw ProviderException::from('tripadvisor', \sprintf('HTTP %d: %s', $answer->status, \is_array($error) ? (self::str($details, 'message') ?? self::str($details, 'type') ?? '') : ($said ?? mb_substr(trim($answer->body), 0, 200))), self::str($details, 'code'));
        }

        return new Data($answer->json());
    }

    /** A value of the answer as it is, when it is a string or a number. */
    private static function str(Data $data, string $path): ?string
    {
        $value = $data->raw($path);

        return \is_scalar($value) ? (string) $value : null;
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        try {
            return \is_string($value) && '' !== $value ? new \DateTimeImmutable($value) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
