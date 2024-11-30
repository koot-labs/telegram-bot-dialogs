<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use KootLabs\TelegramBotDialogs\Exceptions\ControlFlow\SwitchToAnotherStep;
use KootLabs\TelegramBotDialogs\Tests\Fakes\FakeBot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Telegram\Bot\Objects\Update;

#[CoversClass(\KootLabs\TelegramBotDialogs\Dialog::class)]
final class DialogConfigurableStepsTest extends TestCase
{
    use CreatesUpdate;
    use FakeBot;

    private const RANDOM_CHAT_ID = 42;

    #[Test]
    public function it_throws_an_exception_when_step_does_not_have_name(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @inheritDoc */
            protected array $steps = [
                [
                    // 'name' => 'first',
                    'response' => 'Hello!',
                ],
            ];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->performStep($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_switches_to_another_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            public array $stepsExecuted = [];

            /** @inheritDoc */
            protected array $steps = [
                [
                    'name' => 'first',
                    'sendMessage' => 'Hi!',
                    'control' => ['switch' => 'third'],
                ],
                [
                    'name' => 'second',
                    'sendMessage' => 'Hi again (2)',
                ],
                [
                    'name' => 'third',
                    'sendMessage' => 'Hi again (3)',
                ],
            ];

            protected function afterEveryStep(Update $update, int $stepIndex): void
            {
                $this->stepsExecuted[] = $stepIndex;
            }
        };

        try {
            $dialog->performStep($this->buildUpdateOfRandomType());
        } catch (SwitchToAnotherStep) {
            $dialog->performStep($this->buildUpdateOfRandomType());
        }

        // Only step 2 (third) should be in stepsExecuted because the first step
        // was interrupted by the switch before afterEveryStep could be called
        $this->assertSame([2], $dialog->stepsExecuted);
        // Verify we're at the third step
        $this->assertSame(2, $dialog->stepsExecuted[0]);
    }

    #[Test]
    public function it_moves_to_next_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            public array $stepsExecuted = [];

            protected array $steps = [
                [
                    'name' => 'first',
                    'sendMessage' => 'Hi!',
                    'control' => ['nextStep' => 'third'],
                ],
                ['name' => 'second', 'sendMessage' => 'this is second'],
                ['name' => 'third', 'sendMessage' => 'this is third'],
            ];

            protected function afterEveryStep(Update $update, int $stepIndex): void
            {
                $this->stepsExecuted[] = $stepIndex;
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertSame([0, 2], $dialog->stepsExecuted);
    }

    #[Test]
    public function it_ends_dialog(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            /** @inheritDoc */
            protected array $steps = [
                [
                    'name' => 'first',
                    'sendMessage' => 'Hi!',
                    'control' => ['complete' => true],
                ],
                [
                    'name' => 'second',
                ],
            ];
        };

        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isCompleted());
    }

    #[Test]
    public function it_calls_before_and_after_every_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            public bool $beforeStepCalled = false;
            public bool $afterStepCalled = false;

            /** @inheritDoc */
            protected array $steps = [
                [
                    'name' => 'first',
                    'sendMessage' => 'Hi!',
                ],
            ];

            protected function beforeEveryStep(Update $update, int $stepIndex): void
            {
                $this->beforeStepCalled = true;
            }

            protected function afterEveryStep(Update $update, int $stepIndex): void
            {
                $this->afterStepCalled = true;
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->beforeStepCalled);
        $this->assertTrue($dialog->afterStepCalled);
    }
}
