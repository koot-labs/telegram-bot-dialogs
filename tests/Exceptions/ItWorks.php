<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Exceptions;

use KootLabs\TelegramBotDialogs\Exceptions\DialogException;

final class ItWorks extends \LogicException implements DialogException {}