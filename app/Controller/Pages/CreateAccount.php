<?php
/**
 * CreateAccount Class
 *
 * @package   CanaryAAC
 * @author    Lucas Giovanni <lucasgiovannidesigner@gmail.com>
 * @copyright 2022 CanaryAAC
 */

namespace App\Controller\Pages;

use App\Model\Entity\Worlds as EntityWorlds;
use App\Model\Entity\CreateAccount as EntityCreateAccount;
use App\Model\Entity\ServerConfig as EntityServerConfig;
use App\Model\Entity\Player as EntityPlayer;
use App\Model\Functions\Server as FunctionServer;
use \App\Utils\View;
use App\Model\Functions\Player as FunctionsPlayer;

class CreateAccount extends Base{

    public static function getActiveVocation()
    {
        $activeVocations = EntityServerConfig::getInfoWebsite()->fetchObject();
        $active = $activeVocations->player_voc;
        return $active;
    }

    public static function getCreateAccount($request, $status = null)
    {
        $content = View::render('pages/account/createaccount', [
            'status' => $status,
            'worlds' => FunctionServer::getWorlds(),
            'activevoc' => self::getActiveVocation(),
        ]);
        return parent::getBase('Create Account', $content, 'createaccount');
    }

    public static function createAccount($request)
    {
        $postVars = $request->getPostVars();
        $account_email = $postVars['email'] ?? '';
        $account_password1 = $postVars['password1'] ?? '';
        $account_password2 = $postVars['password2'] ?? '';
        $character_name = $postVars['name'] ?? '';
        $character_sex = $postVars['sex'] ?? '';
        $character_vocation = $postVars['vocation'] ?? '';
        $character_world = $postVars['world'] ?? '';
        $account_agreeagreements = $postVars['agreeagreements'] ?? '';

        if(!filter_var($account_email, FILTER_VALIDATE_EMAIL)){
            return self::getCreateAccount($request, 'Please enter your email address!');
        }
        $filter_email = filter_var($account_email, FILTER_SANITIZE_SPECIAL_CHARS);
        $verifyAccountEmail = EntityPlayer::getAccount('email = "'.$filter_email.'"')->fetchObject();
        if(!empty($verifyAccountEmail)){
            return self::getCreateAccount($request, 'Please enter your email address!');
        }

        if($account_password1 != $account_password2){
            return self::getCreateAccount($request, 'Please enter the password again!');
        }
        $filter_password = filter_var($account_password1, FILTER_SANITIZE_SPECIAL_CHARS);
        $convertPassword = sha1($filter_password);
        
        $filter_name = filter_var($character_name, FILTER_SANITIZE_SPECIAL_CHARS);
        if(empty($filter_name)){
            return self::getCreateAccount($request, 'You need to define a name.');
        }
        $CountName = strlen($filter_name);
        if($CountName < 5){
            return self::getCreateAccount($request, 'The name must be at least 5 characters long.');
        }
        if($CountName > 29){
            return self::getCreateAccount($request, 'The name must be at least 29 characters long.');
        }
        $verifyPlayerName = EntityPlayer::getPlayer('name = "'.$filter_name.'"')->fetchObject();
        if($verifyPlayerName == true){
            return self::getCreateAccount($request, 'This character name is already being used.');
        }

        $filter_sex = filter_var($character_sex, FILTER_SANITIZE_SPECIAL_CHARS);
        if($filter_sex > 1){
            return self::getCreateAccount($request, 'Choose a gender for the character.');
        }

        $activeVocations = EntityServerConfig::getInfoWebsite()->fetchObject();
        if($activeVocations->player_voc == 1){
            $filter_vocation = filter_var($character_vocation, FILTER_SANITIZE_SPECIAL_CHARS);
            $verifyVocation = EntityCreateAccount::getPlayerSamples('vocation = "'.$filter_vocation.'"')->fetchObject();
            if($verifyVocation == false){
                return self::getCreateAccount($request, 'Please select your character vocation!');
            }
        } else {
            $filter_vocation = 0;
        }

        $filter_world = filter_var($character_world, FILTER_SANITIZE_SPECIAL_CHARS);
        $selectWorlds = EntityWorlds::getWorlds('id = "'.$filter_world.'"')->fetchObject();
        if($selectWorlds == false){
            return self::getCreateAccount($request, 'Select a valid world.');
        }

        if($account_agreeagreements != 'true'){
            return self::getCreateAccount($request, 'You need to read and accept the rules.');
        }

        $account = [
            'name' => '',
            'password' => $convertPassword,
            'email' => $filter_email,
            'page_access' => '0',
            'premdays' => '0',
            'type' => '0',
            'coins' => '0',
            'recruiter' => '0',
        ];
        $accountId = EntityCreateAccount::createAccount($account);

        $playerSample = EntityCreateAccount::getPlayerSamples('vocation = "'.$filter_vocation.'"')->fetchObject();

        $character = [
            'name' => $filter_name,
            'group_id' => '1',
            'account_id' => $accountId,
            'main' => '1',
            'level' => $playerSample->level,
            'vocation' => $playerSample->vocation,
            'health' => $playerSample->health,
            'healthmax' => $playerSample->healthmax,
            'experience' => $playerSample->experience,
            'lookbody' => '',
            'lookfeet' => '',
            'lookhead' => '',
            'looklegs' => '',
            'looktype' => '',
            'lookaddons' => '',
            'maglevel' => $playerSample->maglevel,
            'mana' => $playerSample->mana,
            'manamax' => $playerSample->manamax,
            'manaspent' => $playerSample->manaspent,
            'soul' => $playerSample->soul,
            'town_id' => $playerSample->town_id,
            'world' => $filter_world,
            'posx' => $playerSample->posx,
            'posy' => $playerSample->posy,
            'posz' => $playerSample->posz,
            'cap' => $playerSample->cap,
            'sex' => $filter_sex,
            'balance' => $playerSample->balance,
            'istutorial' => '1',
        ];
        EntityCreateAccount::createCharacter($character);

        $confirmCharacter = [
            'name' => $filter_name,
            'vocation' => FunctionsPlayer::convertVocation($playerSample->vocation),
            'sex' => FunctionsPlayer::convertSex($filter_sex),
            'world' => FunctionServer::getWorldById($filter_world),
        ];

        $content = View::render('pages/account/createaccount_confirm', [
            'account' => $account,
            'character' => $confirmCharacter,
        ]);
        return parent::getBase('Create Account', $content, 'createaccount');
    }

}