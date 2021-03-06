<?php
declare(strict_types=1);

namespace Gaming\ConnectFour\Application\Game\Query;

use Gaming\ConnectFour\Application\Game\Query\Model\OpenGames\OpenGames;
use Gaming\ConnectFour\Application\Game\Query\Model\OpenGames\OpenGamesFinder;

final class OpenGamesHandler
{
    /**
     * @var OpenGamesFinder
     */
    private $openGamesFinder;

    /**
     * OpenGamesHandler constructor.
     *
     * @param OpenGamesFinder $openGamesFinder
     */
    public function __construct(OpenGamesFinder $openGamesFinder)
    {
        $this->openGamesFinder = $openGamesFinder;
    }

    /**
     * @param OpenGamesQuery $query
     *
     * @return OpenGames
     */
    public function __invoke(OpenGamesQuery $query): OpenGames
    {
        return $this->openGamesFinder->all();
    }
}
