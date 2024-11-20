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
            protected array $steps = [
                [
                    // 'name' => 'first',
                    'response' => 'Hello!',
                ],
            ];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->proceed($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_switches_to_another_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            public array $stepsExecuted = [];

            protected array $steps = [
                [
                    'name' => 'first',
                    'switch' => 'third',
                ],
                [
                    'name' => 'second',
                ],
                [
                    'name' => 'third',
                ],
            ];

            protected function afterEveryStep(Update $update, int $step): void
            {
                $this->stepsExecuted[] = $step;
            }
        };

        try {
            $dialog->proceed($this->buildUpdateOfRandomType());
        } catch (SwitchToAnotherStep) {
            $dialog->proceed($this->buildUpdateOfRandomType());
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
                    'nextStep' => 'third',
                ],
                [
                    'name' => 'second',
                ],
                [
                    'name' => 'third',
                ],
            ];

            protected function afterEveryStep(Update $update, int $step): void
            {
                $this->stepsExecuted[] = $step;
            }
        };

        $dialog->proceed($this->buildUpdateOfRandomType());
        $dialog->proceed($this->buildUpdateOfRandomType());

        $this->assertSame([0, 2], $dialog->stepsExecuted);
    }

    #[Test]
    public function it_ends_dialog(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            protected array $steps = [
                [
                    'name' => 'first',
                    'end' => true,
                ],
                [
                    'name' => 'second',
                ],
            ];
        };

        $dialog->proceed($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isEnd());
    }

    #[Test]
    public function it_calls_before_and_after_every_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();

        $dialog = new class (self::RANDOM_CHAT_ID, $bot) extends Dialog {
            public bool $beforeStepCalled = false;
            public bool $afterStepCalled = false;

            protected array $steps = [
                [
                    'name' => 'first',
                ],
            ];

            protected function beforeEveryStep(Update $update, int $step): void
            {
                $this->beforeStepCalled = true;
            }

            protected function afterEveryStep(Update $update, int $step): void
            {
                $this->afterStepCalled = true;
            }
        };

        $dialog->proceed($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->beforeStepCalled);
        $this->assertTrue($dialog->afterStepCalled);
    }
}
