# Upgrade Guide

## Upgrading from 0.X.Y to 1.0

This is a major release that includes several backward-incompatible changes. Please review this guide thoroughly before
upgrading.

### Breaking Changes

⚠️ **High Impact Changes**

- Method renames in core classes
- Configuration structure changes
- Removal of deprecated methods
- Changes in `BotInitiatedUpdate` constructor

### Method Renames

#### `Dialog` Class

The following methods have been renamed to improve clarity and consistency:

| Before               | After                    | Impact |
|----------------------|--------------------------|--------|
| `$dialog->isStart()` | `$dialog->isAtStart()`   | Medium |
| `$dialog->isEnd()`   | `$dialog->isCompleted()` | Medium |
| `$dialog->end()`     | `$dialog->complete()`    | Medium |
| `$dialog->ttl()`     | `$dialog->getTtl()`      | Low    |
| `$dialog->proceed()` | `$dialog->performStep()` | Low    |

Example migration:

### Configuration Changes

If you use configured steps (as an array), the structure has changed:

#### Basic Message Configuration

```php
// Before
[
    'name' => 'step-name',
    'response' => 'Hi!',
]

// After
[
    'name' => 'step-name',
    'sendMessage' => 'Hi!',
]
```

#### Advanced Message Configuration

```php
// Before
[
    'name' => 'step-name',
    'response' => 'Hi!',
    'options' => ['parse_mode' => 'HTML'],
]

// After
[
    'name' => 'step-name',
    'sendMessage' => [
        'text' => 'Hi!',
        'parse_mode' => 'HTML',
    ],
]
```

#### Control Flow Configuration

```php
// Before
[
    'name' => 'step-name',
    'response' => 'Hi!',
    'switch' => 'next',
    'nextStep' => 'next',
    'end' => true,
]

// After
[
    'name' => 'step-name',
    'sendMessage' => 'Hi!',
    'control' => [
        'switch' => 'some-step',
        'nextStep' => 'some-step',
        'complete' => true,
    ],
]
```

### Removed Methods

The following deprecated methods have been removed:

| Removed Method             | New API                              | Example                             |
|----------------------------|--------------------------------------|-------------------------------------|
| `$dialog->start()`         | Use `DialogManager::processUpdate()` | `$manager->processUpdate($update)`  |
| `$dialog->jump()`          | Use `nextStep()`                     | `$dialog->nextStep('step-name')`    |
| `$dialog->beforeAllStep()` | Use `beforeFirstStep()`              | `$dialog->beforeFirstStep()`        |
| `$dialog->afterAllStep()`  | Use `afterLastStep()`                | `$dialog->afterLastStep()`          |
| `$dialog->remember()`      | Use `$dialog->memory` directly       | See memory management example below |
| `$dialog->forget()`        | Use `$dialog->memory` directly       | See memory management example below |

#### Memory Management Migration

```php
// Before
$this->remember('key', 'value');
$value = $this->memory->get('key');
$this->forget('key');

// After
$this->memory->put('key', 'value');
$value = $this->memory->get('key');
$this->memory->forget('key');
```

### Internal Changes

⚠️ **Note**: These changes only affect you if you've extended the `DialogManager` class.

| Before                    | After                   |
|---------------------------|-------------------------|
| `findDialogKeyForStore()` | `resolveDialogKey()`    |
| `getDialogInstance()`     | `resolveActiveDialog()` |
| `storeDialogState()`      | `persistDialog()`       |
| `readDialogState()`       | `retrieveDialog()`      |
| `forgetDialogState()`     | `forgetDialog()`        |

## Troubleshooting

### Common Issues

1. **Method Not Found Errors**
    - Check the method renames section and update all occurrences
    - Common in: Dialog class method calls

2. **Configuration Not Working**
    - Verify the new configuration structure
    - Ensure 'sendMessage' is used instead of 'response'
    - Check control flow configuration is under 'control' key

3. **Memory Access Issues**
    - Ensure direct memory access is used instead of remember/forget methods
    - Verify memory operations use the new syntax

For more detailed information, please refer to:

- [Advanced Dialogs Documentation](docs/advanced-dialogs.md)
- [Laravel Simple Example](docs/laravel-simple-example.md)
- [Using Without Framework](docs/using-without-framework.md)
