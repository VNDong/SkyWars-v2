<?php

/*
 *                _   _
 *  ___  __   __ (_) | |   ___
 * / __| \ \ / / | | | |  / _ \
 * \__ \  \ / /  | | | | |  __/
 * |___/   \_/   |_| |_|  \___|
 *
 * SkyWars plugin for PocketMine-MP & forks
 *
 * @Author: svile
 * @Kik: _svile_
 * @Telegram_Group: https://telegram.me/svile
 * @E-mail: thesville@gmail.com
 * @Github: https://github.com/svilex/SkyWars-PocketMine
 *
 * Copyright (C) 2016 svile
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace svile\sw;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\sound\{ClickSound, EndermanTeleportSound, Sound};
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\utils\{Config, TextFormat};

class SWarena {

    //Player states
    const PLAYER_NOT_FOUND = 0;
    const PLAYER_PLAYING = 1;
    const PLAYER_SPECTATING = 2;

    //Game states
    const STATE_COUNTDOWN = 0;
    const STATE_RUNNING = 1;
    const STATE_NOPVP = 2;

    /** @var PlayerSnapshot[] */
    private $playerSnapshots = [];//store player's inventory, health etc pre-match so they don't lose it once the match ends

    /** @var int */
    public $GAME_STATE = SWarena::STATE_COUNTDOWN;

    /** @var SWmain */
    private $plugin;

    /** @var string */
    private $SWname;

    /** @var int */
    private $slot;

    /** @var string */
    private $world;

    /** @var int */
    private $countdown = 60;//Seconds to wait before the game starts

    /** @var int */
    private $maxtime = 300;//Max seconds after the countdown, if go over this, the game will finish

    /** @var int */
    public $void = 0;//This is used to check "fake void" to avoid fall (stunck in air) bug

    /** @var array */
    private $spawns = [];//Players spawns

    /** @var int */
    private $time = 0;//Seconds from the last reload | GAME_STATE

    /** @var string[] */
    private $players = [];//[rawUUID] => int(player state)

    /** @var array[] */
    private $playerSpawns = [];

    /**
     * @param SWmain $plugin
     * @param string $SWname
     * @param int $slot
     * @param string $world
     * @param int $countdown
     * @param int $maxtime
     * @param int $void
     */
    public function __construct(SWmain $plugin, string $SWname = "sw", int $slot = 0, string $world = "world", int $countdown = 60, int $maxtime = 300, int $void = 0)
    {
        $this->plugin = $plugin;
        $this->SWname = $SWname;
        $this->slot = $slot;
        $this->world = $world;
        $this->countdown = $countdown;
        $this->maxtime = $maxtime;
        $this->void = $void;

        if (!$this->reload($error)) {
            $logger = $this->plugin->getLogger();
            $logger->error("An error occured while reloading the arena: " . TextFormat::YELLOW . $this->SWname);
            $logger->error($error);
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
        }
    }

    final public function getName() : string
    {
        return $this->SWname;
    }

    /**
     * @return bool
     */
    private function reload(&$error = null) : bool
    {
        //Map reset
        if (!is_file($file = $this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/" . $this->world . ".tar") && !is_file($file = $this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/" . $this->world . ".tar.gz")) {
            $error = "Cannot find world backup file $file";
            return false;
        }

        $server = $this->plugin->getServer();

        if ($server->isLevelLoaded($this->world)) {
            $server->unloadLevel($server->getLevelByName($this->world));
        }

        if ($this->plugin->configs["world.reset.from.tar"]) {
            $tar = new \PharData($file);
            $tar->extractTo($server->getDataPath() . "worlds/" . $this->world, null, true);
        }

        $server->loadLevel($this->world);
        $server->getLevelByName($this->world)->setAutoSave(false);

        $config = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML, [//TODO: put descriptions
            "name" => $this->SWname,
            "slot" => $this->slot,
            "world" => $this->world,
            "countdown" => $this->countdown,
            "maxGameTime" => $this->maxtime,
            "void_Y" => $this->void,
            "spawns" => []
        ]);

        $this->SWname = $config->get("name");
        $this->slot = (int) $config->get("slot");
        $this->world = $config->get("world");
        $this->countdown = (int) $config->get("countdown");
        $this->maxtime = (int) $config->get("maxGameTime");
        $this->spawns = $config->get("spawns");
        $this->void = (int) $config->get("void_Y");

        $this->players = [];
        $this->time = 0;
        $this->GAME_STATE = SWarena::STATE_COUNTDOWN;

        //Reset Sign
        $this->plugin->refreshSigns($this->SWname, 0, $this->slot);
        return true;
    }

    public function getState() : string
    {
        if ($this->GAME_STATE !== SWarena::STATE_COUNTDOWN || count(array_keys($this->players, SWarena::PLAYER_PLAYING, true)) >= $this->slot) {
            return TextFormat::RED . TextFormat::BOLD . "Running";
        }

        return TextFormat::WHITE . "Tap to join";
    }

    public function getSlot(bool $players = false) : int
    {
        return $players ? count($this->players) : $this->slot;
    }

    public function getWorld() : string
    {
        return $this->world;
    }

    /**
     * @param Player $player
     * @return int
     */
    public function inArena(Player $player) : int
    {
        return $this->players[$player->getRawUniqueId()] ?? SWarena::PLAYER_NOT_FOUND;
    }

    public function setPlayerState(Player $player, ?int $state) : void
    {
        if ($state === null || $state === SWarena::PLAYER_NOT_FOUND) {
            unset($this->players[$player->getRawUniqueId()]);
            return;
        }

        $this->players[$player->getRawUniqueId()] = $state;
    }

    /**
     * @param Player $player
     * @param int $slot
     * @return bool
     */
    public function setSpawn(Player $player, int $slot = 1) : bool
    {
        if ($slot > $this->slot) {
            $player->sendMessage(TextFormat::RED . "This arena have only got " . TextFormat::WHITE . $this->slot . TextFormat::RED . " slots");
            return false;
        }

        $config = new Config($this->plugin->getDataFolder() . "arenas/" . $this->SWname . "/settings.yml", Config::YAML);

        if (empty($config->get("spawns", []))) {
            $config->set("spawns", array_fill(1, $this->slot, [
                "x" => "n.a",
                "y" => "n.a",
                "z" => "n.a",
                "yaw" => "n.a",
                "pitch" => "n.a"
            ]));
        }
        $s = $config->get("spawns");
        $s[$slot] = [
            "x" => floor($player->x),
            "y" => floor($player->y),
            "z" => floor($player->z),
            "yaw" => $player->yaw,
            "pitch" => $player->pitch
        ];

        $config->set("spawns", $s);
        $this->spawns = $s;

        if (!$config->save() || count($this->spawns) !== $this->slot) {
            $player->sendMessage(TextFormat::RED . "An error occured setting the spawn, please contact the developer.");
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function checkSpawns() : bool
    {
        if (empty($this->spawns)) {
            return false;
        }

        foreach ($this->spawns as $key => $val) {
            if (!is_array($val) || count($val) !== 5 || $this->slot !== count($this->spawns) || in_array("n.a", $val, true)) {
                return false;
            }
        }
        return true;
    }

    private function refillChests() : void
    {
        $contents = $this->plugin->getChestContents();

        foreach ($this->plugin->getServer()->getLevelByName($this->world)->getTiles() as $tile) {
            if ($tile instanceof Chest) {

                $inventory = $tile->getInventory();
                $inventory->clearAll(false);

                if (empty($contents)) {
                    $contents = $this->plugin->getChestContents();
                }

                foreach (array_shift($contents) as $key => $val) {
                    $inventory->setItem($key, Item::get($val[0], 0, $val[1]), false);
                }

                $inventory->sendContents($inventory->getViewers());
            }
        }
    }

    public function tick() : void
    {
        $config = $this->plugin->configs;

        switch ($this->GAME_STATE) {
            case SWarena::STATE_COUNTDOWN:
                $player_cnt = count($this->players);

                if ($player_cnt < $config["needed.players.to.run.countdown"]) {
                    return;
                }

                if (($config["start.when.full"] && $this->slot <= $player_cnt) || $this->time >= $this->countdown) {
                    $this->start();
                    return;
                }

                if ($this->time % 30 === 0) {
                    $this->sendMessage(str_replace("{N}", date("i:s", ($this->countdown - $this->time)), $this->plugin->lang["chat.countdown"]));
                }

                $this->sendPopup(str_replace("{N}", date("i:s", ($this->countdown - $this->time)), $this->plugin->lang["popup.countdown"]));
                $this->sendSound(ClickSound::class);
                break;
            case SWarena::STATE_RUNNING:
                $player_cnt = count(array_keys($this->players, SWarena::PLAYER_PLAYING, true));
                if ($player_cnt < 2 || $this->time >= $this->maxtime) {
                    $this->stop();
                    return;
                }

                if ($config["chest.refill"] && ($this->time % $config["chest.refill.rate"] === 0)) {
                    $this->sendMessage($this->plugin->lang["game.chest.refill"]);
                }
                break;
            case SWarena::STATE_NOPVP:
                if ($this->time <= $config["no.pvp.countdown"]) {
                    $this->sendPopup(str_replace("{COUNT}", $config["no.pvp.countdown"] - $this->time + 1, $this->plugin->lang["no.pvp.countdown"]));
                } else {
                    $this->GAME_STATE = SWarena::STATE_RUNNING;
                }
                break;
        }

        ++$this->time;
    }

    public function join(Player $player, bool $sendErrorMessage = true) : bool
    {
        if ($this->GAME_STATE !== SWarena::STATE_COUNTDOWN) {
            if ($sendErrorMessage) {
                $player->sendMessage($this->plugin->lang["sign.game.running"]);
            }
            return false;
        }

        if (count($this->players) >= $this->slot || empty($this->spawns)) {
            if ($sendErrorMessage) {
                $player->sendMessage($this->plugin->lang["sign.game.full"]);
            }
            return false;
        }

        //Sound
        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);

        //Removes player things
        $player->setGamemode(Player::SURVIVAL);
        $this->playerSnapshots[$player->getId()] = new PlayerSnapshot($player, $this->plugin->configs["clear.inventory.on.arena.join"], $this->plugin->configs["clear.effects.on.arena.join"]);
        $player->setMaxHealth($this->plugin->configs["join.max.health"]);

        if ($player->getAttributeMap() != null) {//just to be really sure
            if (($health = $this->plugin->configs["join.health"]) > $player->getMaxHealth() || $healt
