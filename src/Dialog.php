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
 *     sendMessage: non-empty-string|array{
 *          text: string,
 *          business_connection_id?: string,
 *          message_thread_id?: int,
 *          parse_mode?: 'HTML'|'MarkdownV2'|'Markdown',
 *          entities?: list<array{type: string, offset: int, length: int, url?: string|null, language?: string|null}>,
 *          link_preview_options?: array{is_disabled?: bool, url?: string, prefer_small_media?: bool, prefer_large_media?: bool, show_above_text?: bool},
 *          disable_notification?: bool,
 *          protect_content?: bool,
 *          allow_paid_broadcast?: bool,
 *          message_effect_id?: string,
 *          reply_parameters?: string,
 *          reply_markup?: non-empty-array|string,
 *     },
 *     control: array{
 *          switch?: non-empty-string|null,
 *          nextStep?: non-empty-string|null,
 *          complete?: bool,
 *     },
 * }
 * Where 'sendMessage' is an array of any key-values accepted by https://core.telegram.org/bots/api#sendmessage
 *
 * @psalm-type NormalizedStepConfiguration = array{
 *     name: non-empty-string,
 *     sendMessage: array{
 *         chat_id: int,
 *         text: non-empty-string,
 *         business_connection_id?: string,
 *         message_thread_id?: int,
 *         text?: string,
 *         parse_mode?: 'HTML'|'MarkdownV2'|'Markdown',
 *         entities?: array,
 *         link_preview_options?: array,
 *         disable_notification?: bool,
 *         protect_content?: bool,
 *         allow_paid_broadcast?: bool,
 *         message_effect_id?: string,
 *         reply_parameters?: string,
 *         reply_markup?: non-empty-array|string,
 *     },
 *     control: array{
 *         switch: non-empty-string|null,
 *         nextStep: non-empty-string|null,
 *         complete: bool,
 *     },
 *  }
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

    /** Run code before the first step. */
    protected function beforeFirstStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** Run code after the last step. */
    protected function afterLastStep(Update $update): void
    {
        // override the method to add your logic here
    }

    /** Run code before every step. */
    protected function beforeEveryStep(Update $update, int $stepIndex): void
    {
        // override the method to add your logic here
    }

    /** Run code after every step. */
    protected function afterEveryStep(Update $update, int $stepIndex): void
    {
        // override the method to add your logic here
    }

    /**
     * Sets the next step to be executed in the dialog flow (on next tick).
     * @api
     * @param non-empty-string $stepName
     */
    final protected function nextStep(string $stepName): void
    {
        foreach ($this->steps as $index => $value) {
            if ($value === $stepName || (is_array($value) && $value['name'] === $stepName)) {
                $this->afterProceedJumpToIndex = $index;
                break;
            }
        }
    }

    /**
     * Switch to the particular step of the Dialog.
     * This step will be executed at once with new proceed iteration.
     * @param non-empty-string $stepName
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

    /** Check if Dialog started */
    final public function isAtStart(): bool
    {
        return $this->next === 0;
    }

    /** Check if Dialog on the last step */
    final public function isLastStep(): bool
    {
        return $this->next === count($this->steps) - 1;
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

    /**
     * @param StepConfiguration $stepConfig
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     */
    private function proceedConfiguredStep(array $stepConfig, Update $update, int $currentStepIndex): void
    {
        $normalizedStepConfig = $this->normalizeConfiguredStep($stepConfig);

        $this->beforeEveryStep($update, $currentStepIndex);

        $this->bot->sendMessage($normalizedStepConfig['sendMessage']);

        if (is_string($normalizedStepConfig['control']['switch'])) {
            $this->switch($normalizedStepConfig['control']['switch']);
        }

        if (is_string($normalizedStepConfig['control']['nextStep'])) {
            $this->nextStep($normalizedStepConfig['control']['nextStep']);
        }

        $this->afterEveryStep($update, $currentStepIndex);

        if ($normalizedStepConfig['control']['complete'] === true) {
            $this->complete();
        }
    }

    /**
     * Normalize step configured by an array as it requires some JSON serialization and other sanity checks.
     * It's also a great extending point for a custom functionality.
     * @psalm-param StepConfiguration $rawStepConfig
     * @psalm-return NormalizedStepConfiguration
     * @throws \KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep
     */
    protected function normalizeConfiguredStep(array $rawStepConfig): array
    {
        if (! is_string($rawStepConfig['name'] ?? null)) {
            throw new InvalidDialogStep('Configurable Dialog step does not contain required “name” key.');
        }

        if (! is_string($rawStepConfig['sendMessage'] ?? null) && ! is_string($rawStepConfig['sendMessage']['text']  ?? null)) {
            throw new InvalidDialogStep('Configurable Dialog step does not contain required “sendMessage.text” key.');
        }

        $sendMessage = is_string($rawStepConfig['sendMessage'])
            ? ['text' => $rawStepConfig['sendMessage']]
            : $rawStepConfig['sendMessage'];
        $sendMessage['chat_id'] = $this->getChatId();

        $normalized = [
            'name' => $rawStepConfig['name'],
            'sendMessage' => $sendMessage,
            'control' => [
                'switch' => $rawStepConfig['control']['switch'] ?? null,
                'nextStep' => $rawStepConfig['control']['nextStep'] ?? null,
                'complete' => $rawStepConfig['control']['complete'] ?? false,
            ],
        ];

        if (is_array($normalized['sendMessage']['reply_markup'] ?? null)) {
            $normalized['sendMessage']['reply_markup'] = json_encode($normalized['sendMessage']['reply_markup'], \JSON_THROW_ON_ERROR);
        }

        return $normalized;
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
}
