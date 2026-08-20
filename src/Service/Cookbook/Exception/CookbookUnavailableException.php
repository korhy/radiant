<?php

declare(strict_types=1);

namespace App\Service\Cookbook\Exception;

/**
 * L'API Cookbook n'a pas pu être jointe, ou a répondu par une erreur serveur.
 *
 * Une dépendance externe indisponible ne doit pas casser une page du
 * portfolio : les appelants attrapent cette exception et rendent un état
 * dégradé. Elle étend \RuntimeException pour rester compatible avec les
 * appelants qui ne distinguent pas encore les modes d'échec.
 */
final class CookbookUnavailableException extends \RuntimeException
{
}
