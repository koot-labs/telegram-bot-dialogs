# Upgrade Guide

## Upgrading from 0.13 to 1.0

This is a major release that includes several backward-incompatible changes. Please review this guide thoroughly before upgrading.

### Method Renames

#### Dialog Class

The following methods have been renamed to improve clarity and consistency:

```php
// Before                   // After
$dialog->isStart()          $dialog->isAtStart()
$dialog->isEnd()            $dialog->isCompleted()
$dialog->end()              $dialog->complete()
$dialog->ttl()              $dialog->getTtl()
$dialog->proceed()          $dialog->performStep()  // internal method
```

#### DialogManager Class

```php
// Before                  // After
$manager->proceed()        $manager->processUpdate()
$manager->exists()         $manager->hasActiveDialog()
```

### Removed Methods

The following deprecated methods have been removed:

```php
// Dialog Class
$dialog->start()           // Use DialogManager::processUpdate() instead
$dialog->jump()            // Use nextStep() instead
$dialog->remember()        // Use $dialog->memory directly
$dialog->forget()          // Use $dialog->memory directly
$dialog->beforeAllStep()   // Use beforeFirstStep() instead
$dialog->afterAllStep()    // Use afterLastStep() instead

// Example migration for memory management
// Before
$this->remember('key', 'value');
$value = $this->memory->get('key');
$this->forget('key');

// After
$this->memory->put('key', 'value');
$value = $this->memory->get('key');
$this->memory->forget('key');
```

### Internal Methods Renames

The following internal methods in DialogManager have been renamed:

```php
// Before                     // After
findDialogKeyForStore()       resolveDialogKey()
getDialogInstance()           resolveActiveDialog()
storeDialogState()            persistDialog()
readDialogState()             retrieveDialog()
forgetDialogState()           forgetDialog()
```

Note: These are internal changes and shouldn't affect your application unless you've extended DialogManager.

### Bot-Initiated Dialogs

```php
// Before
$manager->startNewDialogInitiatedByBot($dialog);

// After
$manager->initiateDialog($dialog);
```
