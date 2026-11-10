<?php

namespace Omnipulse\Tripadvisor;

use Omnipulse\Config;
use Omnipulse\ProviderFactory;
use Omnipulse\ProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tripadvisor's Content API.
 *
 *   options:
 *     api_key: '%env(TRIPADVISOR_KEY)%'      # required: the partner key (restricted to the site's domains or addresses)
 *     language: fr                           # en by default
 *     referer: https://www.example.org/      # the Referer sent, for a key restricted to domains
 */
final class TripadvisorFactory extends ProviderFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnipulse.factory_name' => 'tripadvisor',
            'omnipulse.factory_title' => 'Tripadvisor',
            'omnipulse.required_options' => ['api_key'],
            'language' => 'en',
            'referer' => null,
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new TripadvisorProvider($this->http ?? HttpClient::create(), (string) $c->string('api_key'), $c->string('language') ?? 'en', $c->string('referer'));
    }
}
