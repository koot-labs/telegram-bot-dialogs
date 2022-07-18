<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Exceptions\ControlFlow;

/**
 * Used to ignore some Update types: when thrown, the cursor will not be moved to the next step.
 * @internal
 */
final class UnexpectedUpdateType extends \DomainException implements DialogControlFlowException
{
}
