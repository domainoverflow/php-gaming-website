<?php

namespace Gambling\ConnectFour\Port\Adapter\Persistence\Projection;

use Gambling\Common\EventStore\StoredEvent;
use Gambling\Common\EventStore\StoredEventSubscriber;
use Predis\Client;

final class PredisGameProjection implements StoredEventSubscriber
{
    const STORAGE_KEY_PREFIX = 'game.';

    /**
     * @var Client
     */
    private $predis;

    /**
     * A list of empty game structures for performance increasing.
     *
     * @var array
     */
    private $emptyGameStructureCache;

    /**
     * This could be a memory leak.
     *
     * @var array
     */
    private $gameStructureCache;

    /**
     * PredisGameProjection constructor.
     *
     * @param Client $predis
     */
    public function __construct(Client $predis)
    {
        $this->predis = $predis;
    }

    /**
     * @inheritdoc
     */
    public function handle(StoredEvent $storedEvent): void
    {
        $this->{'handle' . $storedEvent->name()}($storedEvent);
    }

    /**
     * @inheritdoc
     */
    public function isSubscribedTo(StoredEvent $storedEvent): bool
    {
        return in_array(
            $storedEvent->name(),
            [
                'GameOpened',
                'PlayerMoved',
                'GameWon',
                'GameDrawn',
                'GameAborted',
                'ChatAssigned',
            ]
        );
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleGameOpened(StoredEvent $storedEvent): void
    {
        $payload = json_decode($storedEvent->payload(), true);
        $gameId = $payload['gameId'];
        $width = $payload['width'];
        $height = $payload['height'];

        $gameStructure = $this->createEmptyGameStructure($gameId, $width, $height);

        $this->storeGameStructure($gameId, $gameStructure);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handlePlayerMoved(StoredEvent $storedEvent): void
    {
        $payload = json_decode($storedEvent->payload(), true);
        $gameId = $payload['gameId'];
        $x = $payload['x'];
        $y = $payload['y'];
        $color = $payload['color'];

        $gameStructure = $this->retrieveGameStructure($gameId);

        $gameStructure['fields'][$x . '.' . $y]['color'] = $color;

        $this->storeGameStructure($gameId, $gameStructure);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleGameAborted(StoredEvent $storedEvent): void
    {
        $this->handleGameFinished($storedEvent);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleGameWon(StoredEvent $storedEvent): void
    {
        $this->handleGameFinished($storedEvent);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleGameDrawn(StoredEvent $storedEvent): void
    {
        $this->handleGameFinished($storedEvent);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleChatAssigned(StoredEvent $storedEvent): void
    {
        $payload = json_decode($storedEvent->payload(), true);
        $gameId = $payload['gameId'];
        $chatId = $payload['chatId'];

        $gameStructure = $this->retrieveGameStructure($gameId);

        $gameStructure['chatId'] = $chatId;

        $this->storeGameStructure($gameId, $gameStructure);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function handleGameFinished(StoredEvent $storedEvent): void
    {
        $payload = json_decode($storedEvent->payload(), true);
        $gameId = $payload['gameId'];

        $gameStructure = $this->retrieveGameStructure($gameId);

        $gameStructure['finished'] = true;

        $this->storeGameStructure($gameId, $gameStructure);

        // Unset from cache because no further processing on this game is going on.
        unset($this->gameStructureCache[$gameId]);
    }

    /**
     * Store the game structure in redis and in cache.
     *
     * @param string $gameId
     * @param array  $game
     */
    private function storeGameStructure(string $gameId, array $game): void
    {
        $this->predis->set(
            self::STORAGE_KEY_PREFIX . $gameId,
            json_encode($game)
        );

        $this->gameStructureCache[$gameId] = $game;
    }

    /**
     * Retrieve the game structure from cache or redis.
     *
     * @param string $gameId
     *
     * @return array
     */
    private function retrieveGameStructure(string $gameId): array
    {
        if (!isset($this->gameStructureCache[$gameId])) {
            $this->gameStructureCache[$gameId] = json_decode(
                $this->predis->get(
                    self::STORAGE_KEY_PREFIX . $gameId
                ),
                true
            );
        }

        return $this->gameStructureCache[$gameId];
    }

    /**
     * Creates an empty game structure and cache it.
     *
     * @param string $gameId
     * @param int    $width
     * @param int    $height
     *
     * @return array
     */
    private function createEmptyGameStructure(string $gameId, int $width, int $height): array
    {
        if (!isset($this->emptyGameStructureCache[$height][$width])) {
            $fields = [];

            for ($y = 1; $y <= $height; $y++) {
                for ($x = 1; $x <= $width; $x++) {
                    $fields[$x . '.' . $y] = [
                        'x'     => $x,
                        'y'     => $y,
                        'color' => 0
                    ];
                }
            }

            $this->emptyGameStructureCache[$height][$width] = [
                'gameId'   => '',
                'chatId'   => '',
                'width'    => $width,
                'height'   => $height,
                'finished' => false,
                'fields'   => $fields
            ];
        }

        $game = $this->emptyGameStructureCache[$height][$width];
        $game['gameId'] = $gameId;

        return $game;
    }
}
