<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\MemberRepository;
use App\Domain\Member;

/** HU-05 CA-05.1 */
final class CreateMemberService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly IdGenerator $ids,
    ) {
    }

    public function execute(string $fullName, string $documentNumber): string
    {
        $member = Member::create($this->ids->newId(), $fullName, $documentNumber);
        $this->members->add($member);

        return $member->id;
    }
}
