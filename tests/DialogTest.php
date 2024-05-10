<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\InvalidDialogStep;
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
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            protected array $steps = ['existingMethod'];

            public function existingMethod() {}
        };

        $dialog->start($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isEnd());
    }

    #[Test]
    public function it_end_after_process_of_a_multi_step_dialog(): void
    {
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            protected array $steps = ['existingMethodA', 'existingMethodB'];

            public function existingMethodA() {}

            public function existingMethodB() {}
        };

        $dialog->start($this->buildUpdateOfRandomType());
        $dialog->proceed($this->buildUpdateOfRandomType());

        $this->assertTrue($dialog->isEnd());
    }

    #[Test]
    public function it_throws_custom_exception_when_method_not_defined(): void
    {
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            protected array $steps = ['unknownMethodName'];
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->start($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_throws_custom_exception_when_method_not_defined_even_if_magic_call_defined(): void
    {
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            protected array $steps = ['unknownMethodName'];

            public function __call(string $method, array $args) {}
        };

        $this->expectException(InvalidDialogStep::class);

        $dialog->start($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_can_store_variables_between_steps(): void
    {
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            protected array $steps = ['step1', 'step2'];

            public function step1()
            {
                $this->remember('key1', 'A');
            }

            public function step2()
            {
                assertSame('A', $this->memory['key1']); // hack to test protected method
            }
        };

        $dialog->proceed($this->buildUpdateOfRandomType());
        $dialog->proceed($this->buildUpdateOfRandomType());
    }

    #[Test]
    public function it_can_rejump_to_the_same_step(): void
    {
        $dialog = new class ($this->buildUpdateOfRandomType()) extends Dialog {
            public int $count = 0;
            protected array $steps = ['step1'];

            public function step1()
            {
                ++$this->count;
                $this->jump('step1');
            }
        };

        $dialog->proceed($this->buildUpdateOfRandomType());
        $dialog->proceed($this->buildUpdateOfRandomType());
        $dialog->proceed($this->buildUpdateOfRandomType());

        $this->assertSame(3, $dialog->count);
    }
}
