# Advanced Dialog techniques

## Step Configuration as array

You can define default text answers for your dialog steps.
For this, you have to define the step as an array with `name` and `response` fields.

```php
final class HelloDialog extends Dialog
{
    /** @var list<string|array{name: string, response?: string, options?:array}> List of method to execute. The order defines the sequence */
    protected array $steps = [
        [
            'name' => 'hello_step',
            'response' => 'Hello my friend!',
            'options' => ['parse_mode' => 'html'], // optional
        ],
        'fine',
        'bye',
    ];

    // ...
}
```
In this case, if you don't need any logic inside the step handler - you can don't define it.
Just put the response inside the step definition. It works good for welcome messages, messages with tips/advices and so on.

### Options
If you want format response with Markdown, just set `parse_mode` field of the `options` to `MarkdownV2`.

Where 'options' is an array of any key-values accepted by https://core.telegram.org/bots/api#sendmessage

### Step control
Also, you can control the dialog direction in a step by defining `nextStep`, `switch` and `end` fields.
- `nextStep` acts as `nextStep()` method - dialog jumps to a particular step.
- `switch` acts as `switch()` method - dialog switch to a particular step.
- `end` field, is set to `true`, ends dialog after a current step.
