<?php

/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * tutorialintrotwo implementation : © Troy W Scheffel / Digital Adventure Systems
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 *
 * Game.php
 *
 * This is the main file for your backend game logic.
 *
 * In this PHP file, you are going to define the rules of the game.
 * 
 */

declare(strict_types=1);

namespace Bga\Games\tutorialintrotwo;

use Bga\Games\tutorialintrotwo\States\PlayDisc;
use Bga\GameFramework\Components\Counters\PlayerCounter;

require_once("constants.inc.php");

class Game extends \Bga\GameFramework\Table
{
    public static array $CARD_TYPES;

    public PlayerCounter $playerEnergy;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If you want to store any type instead of int, use $this->globals instead.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();
        $this->initGameStateLabels([]); // mandatory, even if the array is empty

        $this->playerEnergy = $this->counterFactory->createPlayerCounter('energy');

        self::$CARD_TYPES = [
            1 => [
                "card_name" => clienttranslate('Troll'), // ...
            ],
            2 => [
                "card_name" => clienttranslate('Goblin'), // ...
            ],
            // ...
        ];

        /* example of notification decorator.
        // automatically complete notification args when needed
        $this->notify->addDecorator(function(string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }
        
            if (isset($args['card_id']) && !isset($args['card_name']) && str_contains($message, '${card_name}')) {
                $args['card_name'] = self::$CARD_TYPES[$args['card_id']]['card_name'];
                $args['i18n'][] = ['card_name'];
            }
            
            return $args;
        });*/
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use `DBPREFIX_<table_name>` for all tables
//
//            $sql = "ALTER TABLE `DBPREFIX_xxxxxxx` ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use `DBPREFIX_<table_name>` for all tables
//
//            $sql = "CREATE TABLE `DBPREFIX_xxxxxxx` ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score`, `player_color` `color` FROM `player`"
        );
        $this->playerEnergy->fillResult($result);

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        // Get reversi board token
        $result['board'] = self::getObjectListFromDB( "SELECT board_x x, board_y y, board_player player
            FROM board
            WHERE board_player IS NOT NULL" );

        return $result;
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = []) : string   
    {
        $this->playerEnergy->initDb(array_keys($players), initialValue: 2);

        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();

        // REPLACED this line with the line below for the reversi tutorial: $default_colors = $gameinfos['player_colors'];
        $default_colors = array( "ffffff", "000000" );

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        // REMOVED PER REVERSI TUTORIAL: $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->tableStats->init('table_teststat1', 0);
        // $this->playerStats->init('player_teststat1', 0);

        // TODO: Setup the initial game situation here.

        // Init the board
        $sql = "INSERT INTO board (board_x,board_y,board_player) VALUES ";
        $sql_values = array();
        list( $blackplayer_id, $whiteplayer_id ) = array_keys( $players );
        for( $x=1; $x<=8; $x++ )
        {
            for( $y=1; $y<=8; $y++ )
            {
            $token_value = "NULL";
            if( ($x==4 && $y==4) || ($x==5 && $y==5) )  // Initial positions of white player
            $token_value = "'$whiteplayer_id'";
            else if( ($x==4 && $y==5) || ($x==5 && $y==4) )  // Initial positions of black player
            $token_value = "'$blackplayer_id'";
                            
            $sql_values[] = "('$x','$y',$token_value)";
            }
        }
        $sql .= implode( ',', $sql_values );
        $this->DbQuery( $sql );

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();

        return PlayDisc::class;
    }

    /*
    COPIED FROM REVERSI Game.php's Utility section
    */

    // Get board size - 0 input returns 8 (old table support)
    function getBoardSize(): int {
        return (int)$this->tableOptions->get(100) ?: 8;
    }
    
    function isReverseReversi(): bool {
        return (int)$this->tableOptions->get(101) > 0;
    }

    // Get coordinate texts (x -> alphabet, y -> number)
    function getFormattedCoordinates( $coord_x, $coord_y ): string
    {
        return chr(64 + $coord_x) . $coord_y;
    }

    // Get the list of returned disc when "player" we play at this place ("x", "y"),
    //  or a void array if no disc is returned (invalid move)
    function getTurnedOverDiscs( int $x, int $y, int $player, array $board ): array
    {
        $board_size = $this->getBoardSize();
        $turnedOverDiscs = [];
        
        if( $board[ $x ][ $y ] === null ) // If there is already a disc on this place, this can't be a valid move
        {
            // For each directions...
            $directions = array(
                array( -1,-1 ), array( -1,0 ), array( -1, 1 ), array( 0, -1),
                array( 0,1 ), array( 1,-1), array( 1,0 ), array( 1, 1 )
            );
            
            foreach( $directions as $direction )
            {
                // Starting from the square we want to place a disc...
                $current_x = $x;
                $current_y = $y;
                $bContinue = true;
                $mayBeTurnedOver = [];

                while( $bContinue )
                {
                    // Go to the next square in this direction
                    $current_x += $direction[0];
                    $current_y += $direction[1];
                    
                    if( $current_x < 1 || $current_x > $board_size || $current_y < 1 || $current_y > $board_size )
                        $bContinue = false; // Out of the board => stop here for this direction
                    else if( $board[ $current_x ][ $current_y ] === null )
                        $bContinue = false; // An empty square => stop here for this direction
                    else if( $board[ $current_x ][ $current_y ] != $player )
                    {
                        // There is a disc from our opponent on this square
                        // => add it to the list of the "may be turned over", and continue on this direction
                        $mayBeTurnedOver[] = array( 'x' => $current_x, 'y' => $current_y );
                    }
                    else if( $board[ $current_x ][ $current_y ] == $player )
                    {
                        // This is one of our disc
                        
                        if( count( $mayBeTurnedOver ) == 0 )
                        {
                            // There is no disc to be turned over between our 2 discs => stop here for this direction
                            $bContinue = false;
                        }
                        else
                        {
                            // We found some disc to be turned over between our 2 discs
                            // => add them to the result and stop here for this direction
                            $turnedOverDiscs = array_merge( $turnedOverDiscs, $mayBeTurnedOver );
                            $bContinue = false;
                        }
                    }
                }
            }
        }
        
        return $turnedOverDiscs;
    }
    
    // Get the complete board with a double associative array
    function getBoard(): array
    {
        return $this->getDoubleKeyCollectionFromDB( "SELECT board_x x, board_y y, board_player player
                                                       FROM board", true );
    }

    // Get the list of possible moves (x => y => true)
    function getPossibleMoves( int $player_id ): array
    {
        $result = [];
        
        $board = $this->getBoard();
        $board_size = $this->getBoardSize();
        
        for( $x = 1; $x <= $board_size; $x++ )
        {
            for( $y = 1; $y <= $board_size; $y++ )
            {
                $returned = $this->getTurnedOverDiscs( $x, $y, $player_id, $board );
                if( count( $returned ) == 0 )
                {
                    // No discs returned => not a possible move
                }
                else
                {
                    // Okay => set this coordinate to "true"
                    if( ! isset( $result[$x] ) )
                        $result[$x] = [];
                        
                    $result[$x][$y] = true;
                }
            }
        }
                
        return $result;
    }

    /**
     * DEBUG functions
     * 
     * Functions starting with "debug_" can be triggered in the Studio with a special menu. Start a new game, click on the Bug icon on the top right then click "playToEndGame". 
     * You should see the game randomly playing until it reaches the end game, so it helps you check the animations, and you can see if the final scoring is also working as expected.
     */

    function debug_playAutomatically(int $moves = 50) {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int)$playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }

    function debug_playToFiftyPercent() {
        $count = 0;
        while ($this->gamestate->getCurrentMainStateId() < ST_END_GAME && $this->getGameProgression() <= 50 && $count < 100) {
            $count++;
            foreach($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int)$playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }

    function debug_playToEndGame() {
        $this->debug_playAutomatically(64); // reversi max moves is under 64 for the standard size board
    }

    // function debug_playToEndGame() {
    //     $count = 0;
    //     while ($this->gamestate->getCurrentMainStateId() < ST_END_GAME && $count < 100) {
    //         $count++;
    //         foreach($this->gamestate->getActivePlayerList() as $playerId) {
    //             $playerId = (int)$playerId;
    //             $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
    //         }
    //     }
    // }
}
