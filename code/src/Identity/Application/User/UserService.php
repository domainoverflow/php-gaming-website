<?php

namespace Gambling\Identity\Application\User;

use Gambling\Common\Application\ApplicationLifeCycle;
use Gambling\Identity\Application\User\Command\ArriveCommand;
use Gambling\Identity\Application\User\Command\SignUpCommand;
use Gambling\Identity\Domain\Model\User\Credentials;
use Gambling\Identity\Domain\Model\User\Exception\UserAlreadySignedUpException;
use Gambling\Identity\Domain\Model\User\User;
use Gambling\Identity\Domain\Model\User\UserId;
use Gambling\Identity\Domain\Model\User\Users;

final class UserService
{
    /**
     * @var ApplicationLifeCycle
     */
    private $applicationLifeCycle;

    /**
     * @var Users
     */
    private $users;

    /**
     * UserService constructor.
     *
     * @param ApplicationLifeCycle $applicationLifeCycle
     * @param Users                $users
     */
    public function __construct(ApplicationLifeCycle $applicationLifeCycle, Users $users)
    {
        $this->applicationLifeCycle = $applicationLifeCycle;
        $this->users = $users;
    }

    /**
     * A new user arrives.
     *
     * @param ArriveCommand $command
     *
     * @return string
     */
    public function arrive(ArriveCommand $command): string
    {
        return $this->applicationLifeCycle->run(
            function () use ($command) {
                $user = User::arrive();

                $this->users->save($user);

                return $user->id()->toString();
            }
        );
    }

    /**
     * Sign up the user.
     *
     * @param SignUpCommand $command
     *
     * @throws UserAlreadySignedUpException
     */
    public function signUp(SignUpCommand $command): void
    {
        $this->applicationLifeCycle->run(
            function () use ($command) {
                $user = $this->users->get(
                    UserId::fromString($command->userId())
                );

                $user->signUp(
                    new Credentials(
                        $command->username(),
                        $command->password()
                    )
                );

                $this->users->save($user);
            }
        );
    }
}