<?php

declare(strict_types=1);

namespace Bga\Games\tutorialintrotwo\States;

use Bga\GameFramework\StateType;
use Bga\Games\tutorialintrotwo\Game;

class NextPlayer extends \Bga\GameFramework\States\GameState
{
    public function __construct(protected Game $game) {
        parent::__construct($game, 
            id: 90, 
            type: StateType::GAME,
            updateGameProgression: true,
        );
    }

    function onEnteringState() {
        // Activate next player
        $this->game->activeNextPlayer();
        
        // Return to PlayerTurn so the next player can play
        return PlayerTurn::class;
    }
}