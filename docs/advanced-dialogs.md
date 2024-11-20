# Advanced Dialog Techniques

This guide covers advanced features and techniques for creating more sophisticated dialog flows in your Telegram bot.

## Table of Contents
- [Step Configuration](#step-configuration)
  - [Basic Configuration](#basic-configuration)
  - [Available Options](#available-options)
  - [Flow Control](#flow-control)
- [Complex Dialog Patterns](#complex-dialog-patterns)
  - [Branching Dialogs](#branching-dialogs)
  - [Dynamic Responses](#dynamic-responses)
- [Best Practices](#best-practices)

## Step Configuration

### Basic Configuration

You can define steps either as simple method names or as configuration arrays. Array configuration allows you to specify default responses and behavior without writing method handlers.

```php
use KootLabs\TelegramBotDialogs\Dialog;

final class WelcomeDialog extends Dialog
{
    /**
     * @var list<string|array{
     *   name: string,
     *   response?: string,
     *   options?: array<string, mixed>,
     *   nextStep?: string,
     *   switch?: string,
     *   end?: bool
     * }>
     */
    protected array $steps = [
        // Simple method name
        'introduction',

        // Configured step with default response
        [
            'name' => 'greeting',
            'response' => 'Hello! How can I help you today?',
            'options' => ['parse_mode' => 'HTML'],
        ],

        // Step with flow control
        [
            'name' => 'menu',
            'response' => 'Please choose an option:',
            'options' => [
                'reply_markup' => [
                    'keyboard' => [
                        ['Help', 'About'],
                        ['Exit'],
                    ],
                    'resize_keyboard' => true,
                ],
            ],
        ],
    ];
}
```

### Available Options

The `options` array supports all parameters from the [Telegram sendMessage API](https://core.telegram.org/bots/api#sendmessage), including:

```php
[
    'parse_mode' => 'HTML|MarkdownV2|Markdown',
    'disable_web_page_preview' => true|false,
    'disable_notification' => true|false,
    'reply_markup' => [
        // Inline keyboard, custom reply keyboard, etc.
    ],
    // ... other Telegram API options
]
```

Example with formatted text:

```php
[
    'name' => 'formatted_message',
    'response' => '<b>Bold text</b> and <i>italic text</i>',
    'options' => [
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ],
]
```

### Flow Control

Control dialog flow using these configuration options:

```php
protected array $steps = [
    // Jump to specific step after completion
    [
        'name' => 'start',
        'response' => 'Welcome!',
        'nextStep' => 'menu',  // Equivalent to $this->nextStep('menu')
    ],

    // Switch to another step immediately
    [
        'name' => 'help',
        'response' => 'Showing help...',
        'switch' => 'help_details',  // Equivalent to $this->switchToStep('help_details')
    ],

    // End dialog after step
    [
        'name' => 'goodbye',
        'response' => 'Thank you for using our bot!',
        'end' => true,  // Equivalent to $this->complete()
    ],
];
```

## Complex Dialog Patterns

### Branching Dialogs

Create dialogs with multiple paths based on user input:

```php
final class SurveyDialog extends Dialog
{
    protected array $steps = ['askAge', 'processAge'];
    private int $userAge;

    public function askAge(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'What is your age?',
        ]);
    }

    public function processAge(Update $update): void
    {
        $this->userAge = (int) $update->getMessage()->getText();

        if ($this->userAge < 18) {
            $this->switchToStep('underageFlow');
        } else {
            $this->switchToStep('adultFlow');
        }
    }

    public function underageFlow(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Sorry, this service is for adults only.',
        ]);
        $this->complete();
    }

    public function adultFlow(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Welcome! Let\'s continue with the survey.',
        ]);
        $this->nextStep('questions');
    }
}
```

### Dynamic Responses

Create steps that generate responses dynamically:

```php
use KootLabs\TelegramBotDialogs\Dialog;

final class WeatherDialog extends Dialog
{
    protected array $steps = ['askLocation', 'showWeather'];
    private WeatherService $weatherService;

    public function __construct(int $chatId, WeatherService $weatherService)
    {
        parent::__construct($chatId);
        $this->weatherService = $weatherService;
    }

    public function askLocation(): void {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Can you please share your location?',
        ]);
    }

    public function showWeather(Update $update): void
    {
        $location = $update->getMessage()->getLocation();
        $weather = $this->weatherService->getWeather(
            $location->getLatitude(),
            $location->getLongitude()
        );

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => $this->formatWeatherResponse($weather),
            'parse_mode' => 'HTML',
        ]);
    }

    private function formatWeatherResponse(array $weather): string
    {
        return sprintf(
            "Current weather:\n" .
            "Temperature: <b>%d°C</b>\n" .
            "Conditions: <i>%s</i>",
            $weather['temp'],
            $weather['conditions']
        );
    }
}
```

## Best Practices

1. **Step Organization**
   - Keep steps focused on single responsibilities
   - Use meaningful names for step methods
   - Consider extracting complex logic into separate services

2. **Error Handling**
   - Implement validation in each step
   - Provide clear error messages to users
   - Use try-catch blocks for external service calls

3. **State Management**
   - Use class properties to store dialog state
   - Clear sensitive data in `afterLastStep`
   - Consider using a separate state manager for complex dialogs

4. **Performance**
   - Avoid heavy computations in step methods
   - Cache external API responses when possible
   - Use configured steps for simple responses

5. **Testing**
   - Write unit tests for each step
   - Mock external services
   - Test different flow paths
