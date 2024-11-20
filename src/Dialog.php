<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use Illuminate\Support\Collection;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use KootLabs\TelegramBotDialogs\Exceptions\UnexpectedUpdateType;
use KootLabs\TelegramBotDialogs\Exceptions\ControlFlow\SwitchToAnotherStep;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Represents a dialog flow with a Telegram chat.
 *
 * A Dialog is a sequence of steps that can be executed in response to Telegram updates.
 * Each step can send messages, process user input, and determine the next step in the flow.
 *
 * @psalm-type StepConfiguration = array{
 *     name: non-empty-string,
 *     response?: string,
 *     switch?: non-empty-string,
 *     nextStep?: non-empty-string,
 *     end?: true,
 *     options?: array{
 *          parse_mode?: 'HTML'|'MarkdownV2'|'Markdown',
 *          ...
 *     }
 * }
 * Where 'options' is an array of any key-values accepted by https://core.telegram.org/bots/api#sendmessage
 */
abstract class Dialog
{
    /**
     * @readonly
     * @var int id of the Chat the Dialog is bounded to
     */
    protected int $chat_id;

    /**
     * @readonly
     * @var int|null id of the User the Dialog is bounded to.
     *               Should be set only for multi-user chat dialogs, where bot should treat messages of every user as a separate dialog.
     */
    protected ?int $user_id = null;

    /** @var \Illuminate\Support\Collection<array-key, mixed> Key-value storage to store data between steps. */
    protected Collection $memory;

    /**
     * @readonly
     * @var int<-1, max> Seconds to store the state of the Dialog after the latest activity on it.
     */
    protected int $ttl = 300;

    /** @var \Telegram\Bot\Api Associated Bot instance that will perform API calls. */
    protected Api $bot;

    /**
     * @readonly
     * @var list<string|StepConfiguration> List of methods to execute. The order defines the sequence.
     */
    protected array $steps = [];

    /** @var int<0, max> Index of the next step. */
    protected int $next = 0;

    /** @var int<0, max>|null Index of the next step that set manually using nextStep() method. */
    private ?int $afterProceedJumpToIndex = null;

    /**
     * @param int $chatId
     * @param \Telegram\Bot\Api|null $bot
     * @param int|null $userId if specified, the Dialog will be bound to the user. Otherwise, it will be bound to the chat. Bounding to a user makes sense for multiuser chats only.
     */
    public function __construct(int $chatId, Api $bot = null, int $userId = null)
    {
        $this->chat_id = $chatId;
        $this->user_id = $userId;

        $this->memory = new Collection();

        if ($bot instanceof Api) {
            $this->bot = $bot;
        }
    }

    /**
     * Specify bot instance (for multi-bot applications).
     * @internal DialogManager is the only user of this method.
     */
    final public function setBot(Api $bot): void
    {
        $this->bot = $bot;
    }

    /**
     * @deprecated Will be removed in v1.0.
     * Start Dialog from the begging.
     */
    final public function start(Update $update): void
    {
        $this->next = 0;
        $this->performStep($update);
    }

    /** @deprecated Will be removed in v1.0. */
    final public function proceed(Update $update): void
    {
        $this->performStep($update);
    }

    /**
     * @internal Should be called by {@see \KootLabs\TelegramBotDialogs\DialogManager::processUpdate},
     * please do not call this method directly.
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    final public function performStep(Update $update): void
    {
        $currentStepIndex = $this->next;

        if ($this->isAtStart()) {
            $this->beforeFirstStep($update);
        }

        if (! array_key_exists($currentStepIndex, $this->steps)) {
            throw new InvalidDialogStep("Undefined step with index {$currentStepIndex}.");
        }
        $stepNameOrConfig = $this->steps[$currentStepIndex];

        if (is_array($stepNameOrConfig)) {
            $this->proceedConfiguredStep($stepNameOrConfig, $update, $currentStepIndex);
        } elseif (is_string($stepNameOrConfig)) {
            $stepMethodName = $stepNameOrConfig;

            if (! method_exists($this, $stepMethodName)) {
                throw new InvalidDialogStep(sprintf('Public method “%s::%s()” is not available.', $this::class, $stepMethodName));
            }

            try {
                $this->beforeEveryStep($update, $currentStepIndex);
                $this->$stepMethodName($update);
                $this->afterEveryStep($update, $currentStepIndex);
            } catch (UnexpectedUpdateType) {
                return; // skip moving to the next step
            }
        } else {
            throw new InvalidDialogStep('Unknown format of the step.');
        }

        if ($this->isLastStep()) {
            $this->afterLastStep($update);
        }

        // Step forward only if did not change inside the step handler
        $hasJumpedIntoAnotherStep = $this->afterProceedJumpToIndex !== null;
        if ($hasJumpedIntoAnotherStep) {
            $this->next = $this->afterProceedJumpToIndex;
            $this->afterProceedJumpToIndex = null;
        } else {
            ++$this->next;
        }
    }

    /**
     * Run code before the first step.
     * @deprecated Will be removed in v1.0. Please use beforeFirstStep() instead.
     */
    protected function beforeAllStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /**
     * Run code after the last step.
     * @deprecated Will be removed in v1.0. Please use afterLastStep() instead.
     */
    protected function afterAllStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code before the first step. */
    protected function beforeFirstStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code after the last step. */
    protected function afterLastStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code before every step. */
    protected function beforeEveryStep(Update $update, int $stepIndex): void
    {
        // override the method to add your logic here
    }

    /** @experimental Run code after every step. */
    protected function afterEveryStep(Update $update, int $stepIndex): void
    {
        // override the method to add your logic here
    }

    /**
     * Jump to the particular step of the Dialog.
     * This step will be executed in the next proceed iteration.
     * @deprecated Use nextStep()
     */
    final protected function jump(string $stepName): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $stepName || (is_array($value) && $value['name'] === $stepName)) {
                $this->afterProceedJumpToIndex = $index;
                break;
            }
        }
    }

    /**
     * Sets the next step to be executed in the dialog flow.
     * @api
     */
    final protected function nextStep(string $stepName): void
    {
        $this->jump($stepName);
    }

    /**
     * Switch to the particular step of the Dialog.
     * This step will be executed at once with new proceed iteration.
     */
    final protected function switch(string $stepName): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $stepName || (is_array($value) && $value['name'] === $stepName)) {
                $this->next = $index;
                throw new SwitchToAnotherStep();
            }
        }
    }

    /** @deprecated Use complete() instead. Will be removed in 1.0 */
    final public function end(): void
    {
        $this->complete();
    }

    /**
     * Move the Dialog’s cursor to the end.
     * If called in a step, this step and all flow after it will be completed, then the Dialog will be forgotten.
     * Works the same if called in last step.
     */
    final public function complete(): void
    {
        if (!$this->isLastStep()) {
            $this->next = count($this->steps);
        }
    }

    /**
     * @api Remember information for next steps.
     * @deprecated Will be removed in v1.0. $this->memory is a Collection, please use it directly.
     */
    final protected function remember(string $key, mixed $value): void
    {
        $this->memory->put($key, $value);
    }

    /**
     * @api Forget information from next steps.
     * @deprecated Will be removed in v1.0. $this->memory is a Collection, please use it directly.
     */
    final protected function forget(string $key): void
    {
        $this->memory->forget($key);
    }

    /** Check if Dialog started */
    final public function isAtStart(): bool
    {
        return $this->next === 0;
    }

    /** @deprecated Use isAtStart() instead. Will be removed in v1.0 */
    final public function isStart(): bool
    {
        return $this->isAtStart();
    }

    /** Check if Dialog on the last step */
    final public function isLastStep(): bool
    {
        return $this->next === count($this->steps) - 1;
    }

    /** @deprecated Use isCompleted() instead. Will be removed in v1.0 */
    final public function isEnd(): bool
    {
        return $this->isCompleted();
    }

    /** Check if Dialog in a finite state (no more steps left) */
    final public function isCompleted(): bool
    {
        return $this->next >= count($this->steps);
    }

    /** Returns Telegram Chat ID */
    final public function getChatId(): int
    {
        return $this->chat_id;
    }

    /** @internal Method can be removed anytime. If you use it for your apps, please consider creating a PR to remove this note. */
    final public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /** Get a number of seconds to store the state of the Dialog after the latest activity on it. */
    final public function getTtl(): int
    {
        return $this->ttl;
    }

    /** @deprecated Use getTtl() instead. Will be removed in v1.0 */
    final public function ttl(): int
    {
        return $this->ttl;
    }

    /**
     * @param StepConfiguration $stepConfig
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     */
    private function proceedConfiguredStep(array $stepConfig, Update $update, int $currentStepIndex): void
    {
        if (! isset($stepConfig['name'])) {
            throw new InvalidDialogStep('Configurable Dialog step does not contain required “name” value.');
        }

        $this->beforeEveryStep($update, $currentStepIndex);

        if (isset($stepConfig['response'])) {
            $params = [
                'chat_id' => $this->getChatId(),
                'text' => $stepConfig['response'],
            ];

            if (is_array($stepConfig['options'] ?? null)) {
                $params = [...$params, ...$stepConfig['options']];
            }

            $this->bot->sendMessage($params);
        }

        if (! empty($stepConfig['switch'])) {
            $this->switch($stepConfig['switch']);
        }

        // @deprecated Use nextStep, @todo remove it in v1.0
        if (! empty($stepConfig['jump'])) {
            $this->nextStep($stepConfig['jump']);
        }

        if (! empty($stepConfig['nextStep'])) {
            $this->nextStep($stepConfig['nextStep']);
        }

        $this->afterEveryStep($update, $currentStepIndex);

        if (isset($stepConfig['end']) && $stepConfig['end'] === true) {
            $this->complete();
        }
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        // serialize non-readonly properties only
        return [
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            'next' => $this->next,
            'memory' => $this->memory,
            'afterProceedJumpToIndex' => $this->afterProceedJumpToIndex,
        ];
    }

    /** @todo remove it in v1.0 */
    public function __unserialize(array $data): void
    {
        // unserialize non-readonly properties only
        $this->chat_id = $data['chat_id'];
        $this->user_id = $data['user_id'];
        $this->next = $data['next'];
        $this->afterProceedJumpToIndex = $data['afterProceedJumpToIndex'];

        // enforce migration from array to Collection, @todo remove it in v1.0
        $this->memory = is_array($data['memory'])
            ? collect($data['memory'])
            : $data['memory'];
    }
}
