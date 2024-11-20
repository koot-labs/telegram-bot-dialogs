<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\DialogRepository;
use KootLabs\TelegramBotDialogs\Tests\TestDialogs\PassiveTestDialog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

#[CoversClass(DialogRepository::class)]
#[CoversClass(Dialog::class)]
final class DialogRepositoryTest extends TestCase
{
    private Psr16Cache $cache;
    private DialogRepository $repository;
    private string $prefix = 'test_prefix_';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Psr16Cache(new ArrayAdapter());
        $this->repository = new DialogRepository($this->cache, $this->prefix);
    }

    #[Test]
    public function put_and_get_dialog(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);

        $this->repository->put($key, $dialog, 1);
        $retrievedDialog = $this->repository->get($key);

        $this->assertInstanceOf(PassiveTestDialog::class, $retrievedDialog);
    }

    #[Test]
    public function has_dialog(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);

        $this->assertFalse($this->repository->has($key));

        $this->repository->put($key, $dialog, 1);

        $this->assertTrue($this->repository->has($key));
    }

    #[Test]
    public function forget_dialog(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);

        $this->repository->put($key, $dialog, 1);
        $this->assertTrue($this->repository->has($key));

        $this->repository->forget($key);
        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function dialog_expiration(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);

        $this->repository->put($key, $dialog, 1);
        $this->assertTrue($this->repository->has($key));

        usleep(1_100_000); // 1.1s
        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function put_with_date_time(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);
        $future = new \DateTime('+1 second');

        $this->repository->put($key, $dialog, $future);
        $this->assertTrue($this->repository->has($key));

        usleep(1_100_000); // 1.1s
        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function put_with_date_interval(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);
        $interval = new \DateInterval('PT1S'); // 1 second

        $this->repository->put($key, $dialog, $interval);
        $this->assertTrue($this->repository->has($key));

        usleep(1_100_000); // 1.1s
        $this->assertFalse($this->repository->has($key));
    }

    #[Test]
    public function key_prefixing(): void
    {
        $key = 'test_key';
        $dialog = new PassiveTestDialog(123);

        $this->repository->put($key, $dialog, 1);

        $this->assertTrue($this->cache->has($this->prefix . $key));
    }
}
