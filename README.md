# SkyWars-v2
# SkyWars - PocketMine plugin
![skywars](https://raw.githubusercontent.com/svilex/res/master/skywars.png)
---
### Donate
_Making a **donation** is an act of generosity. Your support, however modest it might be, is necessary._<br/>
_Your **donations** helps me to continue creating plugins and improve this project!_<br/>
_This plugin helped you? Do you like it? **Support it by donating!**_<br/>
**NOTE:** Donations go to the main owner of the repo (Svilex). This specific fork is NOT being maintained by svilex, you may still donate to svilex if you are feeling generous.

- ![Paypal](https://raw.githubusercontent.com/svilex/res/master/paypal.png) Paypal: [**Donate**](https://www.paypal.me/svile) :money_with_wings:

---
### Releases - Downloads
_Click [**here**](https://poggit.pmmp.io/ci/Muqsit/SkyWars-PocketMine) to browse new releases_.
---
### How to use

##### Installation
**1.** Download a plugin release (the last is recommended) from the link above.<br/>
**2.** Drop the `SkyWars-PocketMine.phar` into the **plugins/** folder of your server and restart it.<br/>
**3.** Done, you can now join the game and create arenas _(SkyWars\_mini-games)_.

##### How to create an arena
**1.** Teleport yourself in the world where you would like to create it (not default one).<br/>
**2.** Now you can use the command `/sw create [SWname] [slots] [countdown] [maxGameTime]` to create an arena.<br/>
**3.** Go back in the arena world and depending on its spawns/slots use the command `/sw setspawn [slot]` x times.<br/>
**4.** Place a sign with `sw` in the 1st line and `SWname` in the 2nd.<br/>
**5.** Done, now players can tap the sign to join the game!

##### Commands
###### These commands cannot be used in console.
Command | Description
-----------|-----------
/sw        | SW main command, shows the usage (subcommands)
/sw create **[**SWname**]** **[**slots**]** **[**countdown**]** **[**maxGameTime**]** | It's used for creating arenas.<br/>- **SWname** indicates the name of the arena, is used for distinguish arenas, for example on join signs.<br/>- **slots** indicates the number of spawns of the arena<br/>- **countdown** is the time in seconds before the game starts.<br/>- **maxGameTime** is the time in seconds after the countdown, if go over this, the game will finish.
/sw setspawn **[**slot**]** | It's used to set each spawn using the CommandSender position.<br/>- **slot** indicates the number of the slot. Example: an arena with 4 slots need 4 different spawns; to set these 4 spawns you need to run this command 4 times: `/sw setspawn 1`, `/sw setspawn 2`, `/sw setspawn 3`, `/sw setspawn 4`.<br/>*If you set spawns above glass, it will be broken once the game starts.*
/sw list  | Displays the list of loaded arenas with the corresponding world + players playing in them. Example: `TestArena [5/16] => TestWorld` etc.
/sw delete **[**SWname**]** | This command just deletes an arena.<br/>- **SWname** is the name of the arena that you must give to delete it
/sw signdelete **[**SWname**\|**all**]** | Do you want to delete a join sign but you forgot where you placed it? This command can help you.<br/>- **SWname** is the arena name, if gived, all the signs pointing to the given arena will be deleted.<br/>- **all** If used as the arena name like `/sw signdelete all`, all the SW signs wil be deleted.<br/>_Are you thinking this command is useless? You'll change your idea about it when you'll have the need._:laughing:
/sw join **[**SWname**]** [PlayerName] | Anyone except ops can use this command to join SW games.<br/>- **PlayerName** can be used only by CONSOLE to force the player to join the specified arena.
/sw quit | Anyone except ops can use this command to left the current SW game.

###### _Feel free to make pull requests and to contact me for any help_.
---
### License
This plugin is licensed under the [GPLv3](http://www.gnu.org/licenses/gpl-3.0.html)

>This program is free software: you can redistribute it and/or modify<br/>
>it under the terms of the GNU General Public License as published by<br/>
>the Free Software Foundation, either version 3 of the License, or<br/>
>(at your option) any later version.<br/>
>
>This program is distributed in the hope that it will be useful,<br/>
>but WITHOUT ANY WARRANTY; without even the implied warranty of<br/>
>MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the<br/>
>GNU General Public License for more details.<br/>
>
>You should have received a copy of the GNU General Public License<br/>
>along with this program.  If not, see http://www.gnu.org/licenses/

