<?php

declare(strict_types=1);

namespace Bga\Games\tutorialintrotwo\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\GameFramework\StateType;
use Bga\Games\tutorialintrotwo\Game;

class PlayDisc extends GameState
{
    public function __construct(protected Game $game) {
        parent::__construct($game, 
            id: ST_PLAYER_PLAY_DISC, 
            type: StateType::ACTIVE_PLAYER,

            description: clienttranslate('${actplayer} must play a disc'),
            descriptionMyTurn: clienttranslate('${you} must play a disc'),
        );
    }

    function getArgs(int $activePlayerId): array {
        return [
            'possibleMoves' => $this->game->getPossibleMoves($activePlayerId)
        ];
    }

    #[PossibleAction]
    function actPlayDisc(int $x, int $y, int $activePlayerId, array $args) {        
        // check if this is a possible move
        if (!array_key_exists($x, $args['possibleMoves']) || !array_key_exists($y, $args['possibleMoves'][$x])) {
            throw new \BgaUserException(clienttranslate("Impossible move"));
        }

        $board = $this->game->getBoard();
        $board_size = $this->game->getBoardSize();
        $turnedOverDiscs = $this->game->getTurnedOverDiscs( $x, $y, $activePlayerId, $board );
        
        if( count( $turnedOverDiscs ) === 0 ) {
            throw new \BgaSystemException(clienttranslate("Impossible move"));
        }
            
        // Let's place a disc at x,y and return all "$returned" discs to the active player
        
        $sql = "UPDATE board SET board_player='$activePlayerId'
                WHERE ( board_x, board_y) IN ( ";
        
        foreach( $turnedOverDiscs as $turnedOver ) {
            $sql .= "('".$turnedOver['x']."','".$turnedOver['y']."'),";
        }
        $sql .= "('$x','$y') ) ";
                    
        $this->game->DbQuery( $sql );
        
        // Statistics
        $disc_count = count( $turnedOverDiscs );
        $this->playerStats->inc('turnedOver', $disc_count, $activePlayerId);
        $updatedStat = 'discPlayedOnCenter';
        if( ($x==1 && $y==1) || ($x==$board_size && $y==1) || ($x==1 && $y==$board_size) || ($x==$board_size && $y==$board_size) ) {
            $updatedStat = 'discPlayedOnCorner';
        } else if( $x==1 || $x==$board_size || $y==1 || $y==$board_size ) {
            $updatedStat = 'discPlayedOnBorder';
        }
        $this->game->playerStats->inc($updatedStat, 1, $activePlayerId);
        
        // Notify
        $this->notify->all( "playDisc", $disc_count == 1 ? clienttranslate( '${player_name} plays a disc on ${coordinates} and turns over 1 disc' ) : clienttranslate( '${player_name} plays a disc on ${coordinates} and turns over ${returned_nbr} discs' ), [
            'player_id' => $activePlayerId,
            'player_name' => $this->game->getPlayerNameById($activePlayerId),
            'coordinates' => $this->game->getFormattedCoordinates($x, $y),
            'returned_nbr' => $disc_count,
            'x' => $x,
            'y' => $y,
        ]);

        $this->notify->all( "turnOverDiscs", '', [
            'player_id' => $activePlayerId,
            'turnedOver' => $turnedOverDiscs,
        ]);
        
        // Update scores according to the number of disc on board
        // If Reverse Reversi mode is on, set the value to negative
        $playerIds = array_keys($this->game->loadPlayersBasicInfos());
        foreach ($playerIds as $playerId) {
            $tokens = (int)$this->game->getUniqueValueFromDB("SELECT COUNT(*) FROM `board` WHERE `board_player` = $playerId");
            $score = $this->game->isReverseReversi() ? -$tokens : $tokens;
            $this->playerScore->set($playerId, $score); // this will update the JS counter automatically
        }
        
        // Then, go to the next state
        return NextPlayer::class;
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     * 
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, 
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     */
    function zombie(int $playerId) {
        // Zombie level 1
        $possibleMoves = $this->game->getPossibleMoves($playerId);

        // transform the 2 dimensional array into a flat array of possible [$x, $y]
        $possibleMovesArray = [];
        foreach($possibleMoves as $x => $ys) {
            foreach($ys as $y => $valid) {
                $possibleMovesArray[] = [$x, $y];
            }
        }

        $zombieChoice = $this->getRandomZombieChoice($possibleMovesArray);
        return $this->actPlayDisc($zombieChoice[0], $zombieChoice[1], $playerId, $this->getArgs($playerId));
    }
}