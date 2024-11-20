<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
use Telegram\Bot\Objects\Update;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use function PHPUnit\Framework\assertSame;

#[CoversClass(\KootLabs\TelegramBotDialogs\Dialog::class)]
final class DialogTest extends TestCase
{
    use CreatesUpdate;

    private const RANDOM_CHAT_ID = 42;

    #[Test]
    public function it_end_after_process_of_a_single_step_dialog(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['existingMethod'];

            public function existingMethod(): void {}
        };

        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isCompleted());
    }

    #[Test]
    public function it_end_after_process_of_a_multi_step_dialog(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['existingMethodA', 'existingMethodB'];

            public function existingMethodA(): void {}

            public function existingMethodB(): void {}
        };

        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isCompleted());
    }

    #[Test]
    public function it_throws_custom_exception_when_method_not_defined(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['unknownMethodName'];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->performStep($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_throws_custom_exception_when_method_not_defined_even_if_magic_call_defined(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['unknownMethodName'];

            public function __call(string $method, array $args) {}
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->performStep($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_can_store_variables_between_steps(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['step1', 'step2'];

            public function step1(): void
            {
                $this->memory->put('key1', 'A');
            }

            public function step2(): void
            {
                assertSame('A', $this->memory->get('key1')); // hack to test protected method
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_can_rejump_to_the_same_step(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            public int $count = 0;

            /** @var list<non-empty-string> */
            protected array $steps = ['step1'];

            public function step1(): void
            {
                ++$this->count;
                $this->nextStep('step1');
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->assertSame(3, $dialog->count);
    }

    #[Test]
    public function it_throws_custom_exception_when_afterLastStep_called_in_dialog_end(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['step1', 'step2', 'step3'];

            public function step1(): void {}

            public function step2(): void {}

            public function step3(): void
            {
                $this->nextStep("step2");
            }

            protected function afterLastStep(Update $update): void
            {
                throw new \LogicException(__METHOD__ . " is called for testing purposes.");
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());
        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->expectException(\LogicException::class);
        $dialog->performStep($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_throws_custom_exception_when_afterLastStep_called_when_end_is_called_in_the_last_step(): void
    {
        $dialog = new class (self::RANDOM_CHAT_ID) extends Dialog {
            /** @var list<non-empty-string> */
            protected array $steps = ['step1', 'step2'];

            public function step1(): void {}

            public function step2(): void
            {
                $this->complete();
            }

            protected function afterLastStep(Update $update): void
            {
                throw new \LogicException(__METHOD__ . " is called for testing purposes.");
            }
        };

        $dialog->performStep($this->buildUpdateOfRandomType());

        $this->expectException(\LogicException::class);
        $dialog->performStep($this->buildUpdateOfRandomType());
    }
}
