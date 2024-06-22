<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Exceptions\ControlFlow;

/**
 * @experimental
 * @api
 * Used when needed to switch to another step of current dialog.
 */
final class SwitchToAnotherStep extends \LogicException implements DialogControlFlowException {}
