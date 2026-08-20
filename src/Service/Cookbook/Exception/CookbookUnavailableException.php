<?php

declare(strict_types=1);

namespace App\Service\Cookbook\Exception;

/**
 * The Cookbook API could not be reached, or answered with a server error.
 *
 * Extends \RuntimeException so callers that do not tell failure modes apart
 * keep working unchanged.
 */
final class CookbookUnavailableException extends \RuntimeException
{
}
