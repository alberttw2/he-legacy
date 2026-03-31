<?php

class Forum {

    public function __construct(){
    }

    public function showPosts($type){
        echo '<ul class="recent-posts"></ul>';
    }

    public function externalRegister($username, $pass, $email, $gameID){
    }

    public function login($user, $pass, $special){
    }

    public function logout(){
    }

    public function createForum($forum_name, $forum_clan){
        return array('error' => false, 'forum_id' => 0);
    }

    public function getForumIDByGameID($gameID){
        return 0;
    }

    public function getForumClanID($clanID){
        return array('forum_id' => 0, 'parent_id' => 0);
    }

    public function setPermission($userID, $permissionType, $forumID){
    }

}
