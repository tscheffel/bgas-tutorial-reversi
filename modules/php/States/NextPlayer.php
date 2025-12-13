<?php

declare(strict_types=1);

namespace Bga\Games\tutorialintrotwo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\tutorialintrotwo\Game;

class NextPlayer extends GameState
{

    # hello world

    public function __construct(protected Game $game) {
        parent::__construct($game, 
            id: ST_NEXT_PLAYER, 
            type: StateType::GAME,

            updateGameProgression: true,
        );
    }

    function onEnteringState()
    {
        // Active next player
        $player_id = intval($this->game->activeNextPlayer());

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = $this->game->getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                       FROM board
                                                       GROUP BY board_player", true );

        if( ! isset( $player_to_discs[ null ] ) )  // if no empty squares remaining on the board
        {
            // Index 0 has not been set => there's no more free place on the board !
            // => end of the game
            return ST_END_GAME;
        }
        else if( ! isset( $player_to_discs[ $player_id ] ) )
        {
            // Active player has no more disc on the board => he looses immediately
            return ST_END_GAME;
        }
        
        // Can this player play?

        $possibleMoves = $this->game->getPossibleMoves( $player_id );
        if( count( $possibleMoves ) == 0 )
        {

            // This player can't play
            // Can his opponent play ?
            $opponent_id = (int)$this->game->getUniqueValueFromDb( "SELECT player_id FROM player WHERE player_id!='$player_id' " );
            if( count( $this->game->getPossibleMoves( $opponent_id ) ) == 0 )
            {
                // Nobody can move => end of the game
                return ST_END_GAME;
            }
            else
            {            
                // => pass his turn
                return NextPlayer::class;
            }
        }
        else
        {
            // This player can play. Give him some extra time
            $this->game->giveExtraTime( $player_id );

            return PlayDisc::class;
        }
    }
}