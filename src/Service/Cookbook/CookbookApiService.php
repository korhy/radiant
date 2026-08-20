<?php

declare(strict_types=1);

namespace App\Service\Cookbook;

use App\Service\Cookbook\Exception\CookbookUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CookbookApiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire(env: 'COOKBOOK_API_URL')]
        private readonly string $apiUrl,
        #[Autowire(env: 'COOKBOOK_API_USERNAME')]
        private readonly string $apiUsername,
        #[Autowire(env: 'COOKBOOK_API_PASSWORD')]
        private readonly string $apiPassword,
        #[Autowire(env: 'COOKBOOK_API_VERSION')]
        private readonly string $apiVersion,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function getToken(): string
    {
        return $this->cache->get('cookbook_api_token', function (ItemInterface $item): string {
            $item->expiresAfter(3500);

            try {
                $response = $this->httpClient->request('POST', $this->apiUrl.'/api/login_check', [
                    'json' => [
                        'username' => $this->apiUsername,
                        'password' => $this->apiPassword,
                    ],
                ]);

                return $response->toArray()['token'];
            } catch (TransportExceptionInterface|ServerExceptionInterface $e) {
                throw new CookbookUnavailableException('Cookbook API unreachable on authentication', previous: $e);
            }
        });
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws CookbookUnavailableException
     */
    private function request(string $method, string $path, array $options = [], bool $retry = true): array
    {
        $options['headers']['Authorization'] = 'Bearer '.$this->getToken();
        $options['headers']['Accept'] ??= 'application/ld+json';

        try {
            $response = $this->httpClient->request($method, $this->apiUrl.$path, $options);

            $statusCode = $response->getStatusCode();

            if (401 === $statusCode && $retry) {
                $this->cache->delete('cookbook_api_token');

                return $this->request($method, $path, $options, false);
            }

            // A server error upstream counts as unavailability, so the caller
            // can degrade instead of returning a 500 of its own.
            if ($statusCode >= 500) {
                throw new CookbookUnavailableException(sprintf('Cookbook API error %d on %s %s', $statusCode, $method, $path));
            }

            if ($statusCode >= 400) {
                throw new \RuntimeException(sprintf('Cookbook API error %d on %s %s', $statusCode, $method, $path));
            }

            return $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw new CookbookUnavailableException(sprintf('Cookbook API unreachable on %s %s', $method, $path), previous: $e);
        }
    }

    /**
     * @param array<string, scalar|null> $filters
     *
     * @return array<string, mixed>
     */
    public function getRecipes(int $page = 1, int $itemsPerPage = 10, array $filters = []): array
    {
        $this->logger->info('Fetching recipes', ['page' => $page, 'itemsPerPage' => $itemsPerPage, 'filters' => $filters]);

        $query = http_build_query([
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            ...$filters,
        ]);

        return $this->request('GET', '/api/'.$this->apiVersion.'/recipes?'.$query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecipe(int $id): array
    {
        return $this->request('GET', '/api/'.$this->apiVersion.'/recipes/'.$id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategories(): array
    {
        return $this->request('GET', '/api/'.$this->apiVersion.'/categories');
    }

    /**
     * L'URL publique de la documentation de l'API consommée. Elle se construisait
     * dans le contrôleur à partir de deux #[Autowire(env:)] dupliqués.
     */
    public function getDocUrl(): string
    {
        return $this->apiUrl.'/api/'.$this->apiVersion.'/docs';
    }
}
