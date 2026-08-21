<?php

declare(strict_types=1);

/*
 * A stand-in for the Cookbook API, started by playwright.config.js.
 *
 * The recipe list is server-rendered from this data, so without it the page under audit
 * shows its "service unavailable" state and no card is ever exercised. Three pages of six
 * recipes, deliberately uneven: some without a thumbnail, without a category or without a
 * duration, plus a title carrying quotes and angle brackets — the shapes the card has to
 * absorb.
 */

$thumbnail = 'data:image/svg+xml;utf8,'.rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
    .'<rect width="400" height="300" fill="#f59e0b"/></svg>'
);

$path = (string) parse_url((string) $_SERVER['REQUEST_URI'], \PHP_URL_PATH);
parse_str((string) parse_url((string) $_SERVER['REQUEST_URI'], \PHP_URL_QUERY), $query);

header('Content-Type: application/ld+json');

if (str_contains($path, 'login_check')) {
    echo json_encode(['token' => 'e2e-token'], \JSON_THROW_ON_ERROR);

    exit;
}

if (str_contains($path, '/categories')) {
    echo json_encode(['member' => [
        ['id' => 1, 'name' => 'Dessert'],
        ['id' => 2, 'name' => 'Plat'],
    ]], \JSON_THROW_ON_ERROR);

    exit;
}

$titles = [
    'Tarte aux pommes',
    'L\'entrecôte "façon <chef>"',
    'Salade tiède',
    'Bœuf bourguignon',
    'Crème brûlée',
    'Soupe à l\'oignon',
];

$page = max(1, (int) ($query['page'] ?? 1));
$recipes = [];

for ($i = 0; $i < 6; ++$i) {
    $n = ($page - 1) * 6 + $i;

    $recipes[] = [
        'id' => $n + 1,
        'title' => $titles[$i].' #'.($n + 1),
        'thumbnail' => 0 === $i % 3 ? null : $thumbnail,
        'category' => 1 === $i % 4 ? null : ['id' => 1, 'name' => 'Dessert'],
        'duration' => 2 === $i % 5 ? null : 15 + $n * 5,
    ];
}

if (!empty($query['title'])) {
    $needle = mb_strtolower((string) $query['title']);
    $recipes = array_values(array_filter(
        $recipes,
        static fn (array $recipe): bool => str_contains(mb_strtolower($recipe['title']), $needle)
    ));
}

$body = ['member' => $recipes];

// Three pages in all, and none once a search narrows the set.
if ($page < 3 && empty($query['title'])) {
    $body['view'] = ['next' => '/api/v1/recipes?page='.($page + 1)];
}

echo json_encode($body, \JSON_THROW_ON_ERROR);
