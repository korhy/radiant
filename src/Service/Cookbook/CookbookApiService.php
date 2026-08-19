<?php

declare(strict_types=1);

namespace App\Service\Cookbook;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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

            $response = $this->httpClient->request('POST', $this->apiUrl.'/api/login_check', [
                'json' => [
                    'username' => $this->apiUsername,
                    'password' => $this->apiPassword,
                ],
            ]);

            return $response->toArray()['token'];
        });
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = [], bool $retry = true): array
    {
        $options['headers']['Authorization'] = 'Bearer '.$this->getToken();
        $options['headers']['Accept'] ??= 'application/ld+json';

        $response = $this->httpClient->request($method, $this->apiUrl.$path, $options);

        $statusCode = $response->getStatusCode();

        if (401 === $statusCode && $retry) {
            $this->cache->delete('cookbook_api_token');

            return $this->request($method, $path, $options, false);
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Cookbook API error %d on %s %s', $statusCode, $method, $path));
        }

        return $response->toArray(false);
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
