[![CI](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/ci.yml/badge.svg)](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/ci.yml)
[![Backward compatibility check](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/backward-compatibility-check.yml/badge.svg)](https://github.com/koot-labs/telegram-bot-dialogs/actions/workflows/backward-compatibility-check.yml)
[![Type coverage](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs/coverage.svg)](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs)
[![Psalm level](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs/level.svg)](https://shepherd.dev/github/koot-labs/telegram-bot-dialogs)
[![codecov](https://codecov.io/github/koot-labs/telegram-bot-dialogs/graph/badge.svg?token=13A5ARUYDQ)](https://codecov.io/github/koot-labs/telegram-bot-dialogs)

<p align="center"><img src="https://user-images.githubusercontent.com/5278175/176997422-79e5c4c1-ff43-438e-b30e-651bb8e17bcf.png" alt="Dialogs" width="400"></p>

# Dialogs plugin for Telegram Bot API PHP SDK

The extension for [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk) v3.1+ that allows to implement dialogs for telegram bots.


## About this fork

The Original package [is not maintained anymore](https://github.com/koot-labs/telegram-bot-dialogs/commit/e9c7667e56e419a7053125b40c473ce4b8d7f9c8) and does not support Telegram Bot API PHP SDK v3.
The goal of the fork is to maintain the package compatible with the latest [Telegram Bot API PHP SDK](https://github.com/irazasyed/telegram-bot-sdk),
PHP 8+ and Laravel features, focus on stability, better DX and readability.

## Scope of the package

Any bot app basically listens to Updates from Telegram API
(using your webhook endpoint or by pulling these updates on any trigger, e.g. cron) and sends messages back.

This package helps to implement a dialog mode for your bot:
For a given Update, check whether the Update belongs to an already activated Dialog and if there is, run the next step of the Dialog.

This package doesn't solve the problem to activate Dialogs for a given Update—you need to implement this logic in your app.
Different apps may have different strategies to activate Dialogs (e.g. by commands, by message content, by user_id, etc.).
The package provides an API to activate Dialogs and run the next step for the active Dialog.


## Installation

You can install the package via Composer:

```shell
composer require koot-labs/telegram-bot-dialogs
```

### Laravel
While this [**package is framework-agnostic**](./docs/using-without-framework.md):
there are some additional features for Laravel.

1. It automatically registers the service provider `\KootLabs\TelegramBotDialogs\Laravel\DialogsServiceProvider`
2. You can publish the config file with:
    ```shell
    php artisan vendor:publish --tag="telegram-config"
    ```
    It will create `config/telegram.php` file that uses the following env variables:
     - `TELEGRAM_DIALOGS_CACHE_DRIVER`: `database` is default value
     - `TELEGRAM_DIALOGS_CACHE_PREFIX`: `tg_dialog_` is default value

## Usage

1. Create a Dialog class
2. [Create a Telegram command](https://telegram-bot-sdk.com/docs/guides/commands-system) to activate Dialog from the Command.
3. Setup your controller class to process active Dialog on income webhook request.


### 1. Create a Dialog class

Each dialog should be implemented as class that extends basic `Dialog` as you can see in [HelloExampleDialog](https://github.com/koot-labs/telegram-bot-dialogs/blob/master/src/Dialogs/HelloExampleDialog.php) or the code bellow:

```php
use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

final class HelloDialog extends Dialog
{
    /** @var list<string> List of method to execute. The order defines the sequence */
    protected array $steps = ['sayHello', 'sayOk',];

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

### 2. Create a Telegram command

> [!IMPORTANT]
> Note, Telegram command is just one of examples of triggers that can activate Dialog.
> You can use any other trigger to activate Dialog (e.g. by message content, by user_id, etc.).
> If a user already initiated a chat with your bot,
> you can activate a Dialog anytime from your server (e.g. by cron), see `\KootLabs\TelegramBotDialogs\Objects\BotInitiatedUpdate`.

To initiate a dialog please use `DialogManager` (or, if you use Laravel, `Dialogs` Facade) — it will care about storing and recovering `Dialog` instance state between steps/requests.
To execute the first and next steps please call `Dialogs::proceed()` method with update object as an argument.
Also, it is possible to use dialogs with Telegram commands and DI through type hinting.

```php
use App\Dialogs\HelloExampleDialog;
use KootLabs\TelegramBotDialogs\Laravel\Facades\Dialogs;
use Telegram\Bot\Commands\Command;

final class HelloCommand extends Command
{
    /** @var string Command name */
    protected $name = 'hello';

    /** @var string Command description */
    protected $description = 'Just say "Hello" and ask few questions in a dialog mode.';

    public function handle(): void
    {
        Dialogs::activate(new HelloExampleDialog($this->update->getChat()->id));
    }
}
```


### 3. Setup your controller

Process request inside your Laravel webhook controller:

```php
use Telegram\Bot\BotsManager;
use KootLabs\TelegramBotDialogs\DialogManager;

final class TelegramWebhookController
{
    public function handle(DialogManager $dialogs, BotsManager $botsManager): void
    {
        $update = $bot->commandsHandler(true);

        $dialogs->hasActiveDialog($update)
            ? $dialogs->processUpdate($update) // proceed an active dialog (activated in HelloCommand)
            : $botsManager->sendMessage([ // fallback message
                'chat_id' => $update->getChat()->id,
                'text' => 'There is no active dialog at this moment. Type /hello to start a new dialog.',
            ]);
    }
}
```

### `Dialog` class API

```php
abstract class Dialog
{
    // Navigation
    public function nextStep(string $stepName): void
    public function switchToStep(string $stepName): void
    public function complete(): void

    // State Management
    public function isAtStart(): bool
    public function isLastStep(): bool
    public function isComplete(): bool

    // Lifecycle Hooks
    protected function beforeEveryStep(Update $update, int $stepIndex): void
    protected function afterEveryStep(Update $update, int $stepIndex): void
    protected function beforeFirstStep(Update $update): void
    protected function afterLastStep(Update $update): void

    // Properties Access
    public function getChatId(): int
    public function getUserId(): ?int
    public function ttl(): int
}
```

### `DialogManager` class API

`DialogManager` is in charge of:
 - storing and recovering Dialog instances between steps/requests
 - running Dialog steps (using Dialog public API)
 - switching/activating Dialogs

For Laravel apps, the package provides `Dialogs` Facade, that proxies calls to `DialogManager` class.

`DialogManager` public API:
- `activate(\KootLabs\TelegramBotDialogs\Dialog $dialog)` - Activate a new Dialog (without running it). The same user/chat may have few open Dialogs; DialogManager should know which one is active.
- `processUpdate(\Telegram\Bot\Objects\Update $update)` - Run the next step handler for the active Dialog (if exists)
- `hasActiveDialog(\Telegram\Bot\Objects\Update $update)` - Check for existing Dialog for a given Update (based on `chat_id` and optional `user_id`)
- `setBot(\Telegram\Bot\Api $bot)` - Use non-default Bot for Telegram Bot API calls (useful for multi-bot apps)


## ToDo

Tasks to do for v1.0:

- [x] Add documentation and examples
- [x] Support for channel bots
- [x] Improve test coverage
- [x] Improve developer experience (cleaner API (similar method in `Dialog` and `DialogManager`))
- [ ] Reach message type validation
- [ ] Reach API to validate message types and content
- [ ] Support `\Iterator`s and/or `\Generator`s for Dialog steps


## Backward compatibility promise

The package uses [Semver 2.0](https://semver.org/). This means that versions are tagged with MAJOR.MINOR.PATCH.
Only a new major version will be allowed to break backward compatibility (BC).

Classes marked as `@experimental` or `@internal` are not included in our backward compatibility promise.
You are also not guaranteed that the value returned from a method is always the same.
You are guaranteed that the data type will not change.

PHP 8 introduced [named arguments](https://wiki.php.net/rfc/named_params), which increased the cost and reduces flexibility for package maintainers.
The names of the arguments for methods in the package are not included in our BC promise.
