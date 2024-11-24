[![CI](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/ci.yml/badge.svg)](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/ci.yml)
[![Backward compatibility check](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/backward-compatibility-check.yml/badge.svg)](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/backward-compatibility-check.yml)
[![Type coverage](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs/coverage.svg)](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs)
[![Psalm level](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs/level.svg)](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs)
[![codecov](https://codecov.io/github/koot-labs/telegram-bot-dialogs/graph/badge.svg?token=13A5ARUYDQ)](https://codecov.io/github/koot-labs/telegram-bot-dialogs)

<p align="center"><img src="https://user-images.githubusercontent.com/5278175/176997422-79e5c4c1-ff43-438e-b30e-651bb8e17bcf.png" alt="Dialogs" width="400"></p>

# Dialogs Plugin for Telegram Bot API PHP SDK

A powerful extension for [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk) v3.1+ that enables dialog-based interactions in your Telegram bots.

## Table of Contents
- [About](#about)
- [Features](#features)
- [Installation](#installation)
  - [Laravel Integration](#laravel-integration)
  - [Framework-agnostic Usage](#framework-agnostic-usage)
- [Basic Usage](#basic-usage)
  - [Creating a Dialog](#creating-a-dialog)
  - [Setting up Commands](#setting-up-commands)
  - [Controller Setup](#controller-setup)
- [Advanced Usage](#advanced-usage)
  - [Dialog Class API](#dialog-class-api)
  - [DialogManager API](#dialogmanager-api)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Roadmap](#roadmap)

## About

This package is a maintained fork of the original Telegram Bot Dialogs package, updated to support Telegram Bot API PHP SDK v3, PHP 8+, and modern Laravel features. Our focus is on stability, developer experience, and code readability.

### Why This Fork?

The Original package [is not maintained anymore](https://github.com/koot-labs/telegram-bot-dialogs/commit/e9c7667e56e419a7053125b40c473ce4b8d7f9c8) and does not support Telegram Bot API PHP SDK v3.
The goal of the fork is to maintain the package compatible with the latest [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk),
PHP 8+ and Laravel features, focus on stability, better DX and readability.

## Features

- Framework-agnostic design with enhanced Laravel support
- Dialog-based conversation flow management
- State persistence between messages
- Flexible step navigation
- Support for multiple active dialogs

## Scope of the package

Any bot app basically listens to Updates from Telegram API
(using your webhook endpoint or by pulling these updates on any trigger, like cron) and sends messages back.

This package helps to implement a dialog mode for your bot:
for a given Update, check whether the Update belongs to an already activated Dialog and if there is, run the next step of the Dialog.

This package doesn't solve the task to activate Dialogs for a given Update—you need to implement this logic in your app.
Different apps may have different strategies to activate Dialogs
(e.g. by commands, by message content, by message type, by user_id, etc.).
The package provides an API to activate Dialogs and run the next step for the active Dialog.

## Installation

Install via Composer:

```shell
composer require koot-labs/telegram-bot-dialogs
```

### Laravel Integration

1. The package automatically registers `\KootLabs\TelegramBotDialogs\Laravel\DialogsServiceProvider`

2. Publish the configuration:
```shell
php artisan vendor:publish --tag="telegram-config"
```

This creates `config/telegramdialogs.php` with these environment variables:
- `TELEGRAM_DIALOGS_CACHE_DRIVER` (default: `database`)
- `TELEGRAM_DIALOGS_CACHE_PREFIX` (default: `tg_dialog_`)

### Framework-agnostic Usage

For non-Laravel applications, see our [framework-agnostic guide](./docs/using-without-framework.md).

## Basic Usage

### 1. Creating a Dialog

Create a dialog class extending `Dialog`:

```php
use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

final class HelloDialog extends Dialog
{
    /** @var list<string> List of method to execute. The order defines the sequence */
    protected array $steps = ['sayHello', 'sayOk'];

    public function sayHello(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Hello! How are you?',
        ]);
    }

    public function sayOk(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'I’m also OK :)',
        ]);

        $this->nextStep('sayHello');
    }
}
```

### 2. Setup Webhook Handler

In this example, the Dialog is activated by a command.
You can also activate dialogs based on other triggers (like an Update/Message type, or a work inside a Message).

#### 2.1. Setting up a Telegram Command
Create a command to activate your dialog (Laravel example):

```php
use App\Dialogs\HelloDialog;
use KootLabs\TelegramBotDialogs\Laravel\Facades\Dialogs;
use Telegram\Bot\Commands\Command;

final class HelloCommand extends Command
{
    protected $name = 'hello';
    protected $description = 'Start a hello dialog';

    public function handle(): void
    {
        Dialogs::activate(new HelloDialog($this->update->getChat()->id));
    }
}
```

#### 2.2. Webhook Handler Setup

Handle webhook updates in your controller:

```php
use Telegram\Bot\BotsManager;
use KootLabs\TelegramBotDialogs\DialogManager;

final class TelegramWebhookHandler
{
    public function handle(DialogManager $dialogs, BotsManager $botsManager): void
    {
        // Find a \Telegram\Bot\Commands\Command instance for the Update and execute it
        // for /hello command, it should call HelloCommand that will activate HelloDialog
        $update = $bot->commandsHandler(true);

        $dialogs->hasActiveDialog($update)
            ? $dialogs->processUpdate($update) // Run the next step of the active dialog
            : $botsManager->sendMessage([ // send a fallback message
                'chat_id' => $update->getChat()->id,
                'text' => 'No active dialog. Type /hello to start.',
            ]);
    }
}
```

## Advanced Usage

### Dialog Class API

```php
abstract class Dialog
{
    // Navigation
    public function nextStep(string $stepName): void;
    public function switch(string $stepName): void;
    public function complete(): void;

    // State Management
    public function isAtStart(): bool;
    public function isLastStep(): bool;
    public function isComplete(): bool;

    // Lifecycle Hooks
    protected function beforeEveryStep(Update $update, int $stepIndex): void;
    protected function afterEveryStep(Update $update, int $stepIndex): void;
    protected function beforeFirstStep(Update $update): void;
    protected function afterLastStep(Update $update): void;

    // Properties Access
    public function getChatId(): int;
    public function getUserId(): ?int;
    public function ttl(): int;
}
```

### DialogManager API

The `DialogManager` handles:
- Dialog instance persistence
- Step execution
- Dialog activation and switching

Laravel users can use the `Dialogs` facade:

```php
use KootLabs\TelegramBotDialogs\Laravel\Facades\Dialogs;

// Activate a dialog
Dialogs::activate($dialog);

// Process an update
Dialogs::processUpdate($update);

// Check for active dialog
Dialogs::hasActiveDialog($update);

// Set custom bot instance
Dialogs::setBot($bot);
```

## Contributing

Contributions are welcome!
Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Testing

Run the test suite:

```shell
composer test
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Roadmap

Tasks planned for v1.0:

- [x] Add documentation and examples
- [x] Support for channel bots
- [x] Improve test coverage
- [x] Improve developer experience
- [ ] Reach message type validation
- [ ] Reach API to validate message types and content

## Backward Compatibility Promise

We follow [Semver 2.0](https://semver.org/). Breaking changes are only introduced in major versions.

**Note:**
- Classes marked `@experimental` or `@internal` are not covered by BC promise
- Return value consistency is not guaranteed, only data types
- Argument names (for PHP 8.0+ named arguments) are not part of BC promise
