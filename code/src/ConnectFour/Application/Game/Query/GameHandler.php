<?php
declare(strict_types=1);

namespace Gaming\ConnectFour\Application\Game\Query;

use Gaming\ConnectFour\Application\Game\Query\Exception\GameNotFoundException;
use Gaming\ConnectFour\Application\Game\Query\Model\Game\Game;
use Gaming\ConnectFour\Application\Game\Query\Model\Game\GameFinder;
use Gaming\ConnectFour\Domain\Game\Games;

final class GameHandler
{
    /**
     * @var GameFinder
     */
    private $gameFinder;

    /**
     * @var Games
     */
    private $games;

    /**
     * GameHandler constructor.
     *
     * @param GameFinder $gameFinder
     * @param Games      $games
     */
    public function __construct(GameFinder $gameFinder, Games $games)
    {
        $this->gameFinder = $gameFinder;
        $this->games = $games;
    }

    /**
     * Query the game finder.
     *
     * @param GameQuery $query
     *
     * @return Game
     * @throws GameNotFoundException
     */
    public function __invoke(GameQuery $query): Game
    {
        return $this->gameFinder->find(
            $query->gameId()
        );
    }
}
