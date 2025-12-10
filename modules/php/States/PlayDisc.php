<?php

declare(strict_types=1);

namespace Bga\Games\tutorialintrotwo\States;

use Bga\GameFramework\StateType;
use Bga\Games\tutorialintrotwo\Game;

class PlayDisc extends \Bga\GameFramework\States\GameState
{
    public function __construct(protected Game $game) {
        parent::__construct($game, 
            id: 10, 
            type: StateType::ACTIVE_PLAYER,

            description: clienttranslate('${actplayer} must play a disc'),
            descriptionMyTurn: clienttranslate('${you} must play a disc'),
        );
    }

    function zombie(int $playerId) {
    }
}