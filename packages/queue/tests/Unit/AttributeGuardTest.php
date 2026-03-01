<?php

declare(strict_types=1);

namespace Waaseyaa\Queue\Tests\Unit;

use Waaseyaa\Queue\Attribute\RateLimited;
use Waaseyaa\Queue\Attribute\UniqueJob;
use Waaseyaa\Queue\AttributeGuard;
use Waaseyaa\Queue\Job;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeGuard::class)]
final class AttributeGuardTest extends TestCase
{
    private AttributeGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new AttributeGuard();
    }

    // -----------------------------------------------------------------------
    // Plain messages (no attributes) — always allowed
    // -----------------------------------------------------------------------

    #[Test]
    public function plainMessageIsAlwaysAllowed(): void
    {
        $message = new class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($message));
        $this->assertTrue($this->guard->allows($message));
    }

    // -----------------------------------------------------------------------
    // #[UniqueJob] — class-name key
    // -----------------------------------------------------------------------

    #[Test]
    public function uniqueJobFirstDispatchIsAllowed(): void
    {
        $job = new #[UniqueJob(lockSeconds: 3600)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
    }

    #[Test]
    public function uniqueJobSecondDispatchIsSkipped(): void
    {
        $job = new #[UniqueJob(lockSeconds: 3600)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));
    }

    #[Test]
    public function uniqueJobThirdDispatchAlsoSkipped(): void
    {
        $job = new #[UniqueJob(lockSeconds: 3600)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));
    }

    #[Test]
    public function uniqueJobWithCustomKeyUsesKeyNotClassName(): void
    {
        // Two distinct anonymous classes sharing the same custom key must
        // block each other.
        $job1 = new #[UniqueJob(lockSeconds: 3600, key: 'shared-key')] class extends Job {
            public function handle(): void {}
        };
        $job2 = new #[UniqueJob(lockSeconds: 3600, key: 'shared-key')] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job1));
        // job2 has the same custom key — must be blocked.
        $this->assertFalse($this->guard->allows($job2));
    }

    #[Test]
    public function uniqueJobDistinctCustomKeysAreIndependent(): void
    {
        $job1 = new #[UniqueJob(lockSeconds: 3600, key: 'key-a')] class extends Job {
            public function handle(): void {}
        };
        $job2 = new #[UniqueJob(lockSeconds: 3600, key: 'key-b')] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job1));
        $this->assertTrue($this->guard->allows($job2));
    }

    #[Test]
    public function uniqueJobLockExpiry(): void
    {
        $job = new #[UniqueJob(lockSeconds: 0)] class extends Job {
            public function handle(): void {}
        };

        // Lock duration is 0 s — the lock expires immediately, so even a tiny
        // sleep allows a second dispatch.  We use usleep to avoid flakiness.
        $this->assertTrue($this->guard->allows($job));
        usleep(1_000); // 1 ms — more than enough for a 0-second lock
        $this->assertTrue($this->guard->allows($job));
    }

    #[Test]
    public function releaseLockAllowsRedispatch(): void
    {
        $job = new #[UniqueJob(lockSeconds: 3600, key: 'release-test')] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));

        $this->guard->releaseLock('release-test');

        // After an explicit release the job should be allowed again.
        $this->assertTrue($this->guard->allows($job));
    }

    // -----------------------------------------------------------------------
    // #[RateLimited]
    // -----------------------------------------------------------------------

    #[Test]
    public function rateLimitedFirstDispatchIsAllowed(): void
    {
        $job = new #[RateLimited(maxAttempts: 3, decaySeconds: 60)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
    }

    #[Test]
    public function rateLimitedAllowsUpToMaxAttempts(): void
    {
        $job = new #[RateLimited(maxAttempts: 3, decaySeconds: 60)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertTrue($this->guard->allows($job));
        $this->assertTrue($this->guard->allows($job));
    }

    #[Test]
    public function rateLimitedBlocksAfterMaxAttempts(): void
    {
        $job = new #[RateLimited(maxAttempts: 2, decaySeconds: 60)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));
    }

    #[Test]
    public function rateLimitedMaxAttemptsOfOne(): void
    {
        $job = new #[RateLimited(maxAttempts: 1, decaySeconds: 60)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));
    }

    #[Test]
    public function rateLimitedWindowExpiry(): void
    {
        $job = new #[RateLimited(maxAttempts: 1, decaySeconds: 0)] class extends Job {
            public function handle(): void {}
        };

        // decaySeconds is 0 — the window is already expired for any previous
        // attempt recorded at the same moment.  usleep ensures clock advances.
        $this->assertTrue($this->guard->allows($job));
        usleep(1_000); // 1 ms
        $this->assertTrue($this->guard->allows($job));
    }

    // -----------------------------------------------------------------------
    // reset()
    // -----------------------------------------------------------------------

    #[Test]
    public function resetClearsUniqueJobLocks(): void
    {
        $job = new #[UniqueJob(lockSeconds: 3600)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));

        $this->guard->reset();

        $this->assertTrue($this->guard->allows($job));
    }

    #[Test]
    public function resetClearsRateLimitBuckets(): void
    {
        $job = new #[RateLimited(maxAttempts: 1, decaySeconds: 60)] class extends Job {
            public function handle(): void {}
        };

        $this->assertTrue($this->guard->allows($job));
        $this->assertFalse($this->guard->allows($job));

        $this->guard->reset();

        $this->assertTrue($this->guard->allows($job));
    }
}
