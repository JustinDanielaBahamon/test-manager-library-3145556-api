<?php

namespace App\Providers;

use App\Adapters\Persistence\DbBookRepository;
use App\Adapters\Persistence\DbCategoryRepository;
use App\Adapters\Persistence\DbLoanRepository;
use App\Adapters\Persistence\DbMemberRepository;
use App\Adapters\Persistence\DbReportReader;
use App\Adapters\Persistence\DbUserRepository;
use App\Adapters\Storage\LocalCoverStorage;
use App\Adapters\Security\Argon2idPasswordHasher;
use App\Adapters\Security\HmacJwtTokenIssuer;
use App\Adapters\System\SystemClock;
use App\Adapters\System\UuidV4Generator;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\CoverStorage;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\ReportReader;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Article III — the single composition point: the only place where a port meets its adapter.
 */
final class PortBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(IdGenerator::class, UuidV4Generator::class);
        $this->app->singleton(PasswordHasher::class, Argon2idPasswordHasher::class);
        $this->app->singleton(TokenIssuer::class, fn ($app) => new HmacJwtTokenIssuer(
            (string) env('JWT_SECRET', ''),
            $app->make(Clock::class),
        ));
        $this->app->singleton(UserRepository::class, DbUserRepository::class);
        $this->app->singleton(CategoryRepository::class, DbCategoryRepository::class);
        $this->app->singleton(BookRepository::class, DbBookRepository::class);
        $this->app->singleton(MemberRepository::class, DbMemberRepository::class);
        $this->app->singleton(LoanRepository::class, DbLoanRepository::class);
        $this->app->singleton(ReportReader::class, DbReportReader::class);
        $this->app->singleton(CoverStorage::class, fn () => new LocalCoverStorage((string) env('COVERS_DIR', 'covers')));
    }
}
