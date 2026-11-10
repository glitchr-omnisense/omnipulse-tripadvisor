# Changelog

## Unreleased (1.x)

### Added
- Tripadvisor's usage policy declared as data: `policy.json` (omnisense/contracts' schema), its terms
  read on 2026-11-10, named in `composer.json` (`extra.omnisense.policy`, and
  `omnisense/contracts` required). The provider implements `DeclaresUsagePolicyInterface` and gives
  the policy to its Capabilities; the factory hands the provider's Backoff to its HTTP calls, so
  that a 429 and its `Retry-After` hold every next call. `Tests/PolicyTest.php`.

### Changed
- `omnireview/tripadvisor` is now `omnipulse/tripadvisor` (2026-11-10: omnireview and omnifund
  merged into omnipulse, Marco's decision; this repository's history kept, from
  omnireview-tripadvisor at 85734c8): the namespace `Omnipulse\Tripadvisor`, requiring
  `omnisense/omnipulse`; `composer.json` replaces `omnireview/tripadvisor`, so that a project never
  installs both. `TripadvisorGateway` is `TripadvisorProvider`, `TripadvisorGatewayFactory` is
  `TripadvisorFactory`, implementing the core's `ProviderInterface` and `ProviderFactory` (were
  `GatewayInterface` and `GatewayFactory`); the factory's name is `tripadvisor`. Its Terms are
  unchanged.
- The decoded answers are read with types (`Omnipulse\Support\Data`): the same values, an
  item of a list that is not an object skipped where it stopped the call.

### Added
- CI (glitchr-studio/ci's `php-package.yml`), PHPStan at level max with an empty baseline,
  `phpunit.xml.dist`, and `Tests/BareTest.php`: every class loads with nothing from outside but
  `omnisense/omnipulse` and Symfony's HTTP client contracts.
