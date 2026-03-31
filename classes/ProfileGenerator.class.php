<?php

class ProfileGenerator {

    private $db;
    private $badgeList;

    function __construct() {
        $this->db = PDO_DB::factory();
        $jsonData = file_get_contents(BASE_PATH . 'json/badges.json');
        $this->badgeList = json_decode($jsonData, true);
    }

    private function getBadgeInfo($badgeID) {
        $badgeID = (string) $badgeID;
        return array(
            $this->badgeList[$badgeID]['name'],
            $this->badgeList[$badgeID]['desc'],
            $this->badgeList[$badgeID]['collectible']
        );
    }

    private function formatNumber($number) {
        if ($number > 0 && $number < 1) {
            return (string) $number;
        }
        return number_format((int) $number, 0, '.', ',');
    }

    private function playTime($seconds) {
        $days = intval($seconds / 86400);
        $seconds -= 86400 * $days;
        $hrs = intval($seconds / 3600);
        $seconds -= 3600 * $hrs;

        if ($days == 0) {
            $mins = intval($seconds / 60);

            $ret = sprintf(ngettext('%d hour', '%d hours', $hrs), $hrs);
            if ($hrs == 0) {
                $ret = '';
            } elseif ($mins != 0) {
                $ret .= ' ' . _('and') . ' ';
            }

            if ($mins != 0) {
                $ret .= sprintf(ngettext('%d minute', '%d minutes', $mins), $mins);
            }
        } else {
            $ret = sprintf(ngettext('%d day', '%d days', $days), $days);
            if ($hrs != 0) {
                $ret .= ' ' . _('and') . ' ';
                $ret .= sprintf(ngettext('%d hour', '%d hours', $hrs), $hrs);
            }
        }

        return $ret;
    }

    public function generate($userID, $lang = 'en') {

        $userID = (string) $userID;
        $db = $this->db;

        // Main query: user info with joins
        $stmt = $db->prepare("
            SELECT
                users.login, users.premium, clan.clanID, clan.name, clan.nick, clan.createdBy, ranking_user.rank, DATEDIFF(CURDATE(), users_stats.dateJoined) AS gameAge,
                users_stats.exp, users_stats.timeplaying, users_stats.hackCount, users_stats.ddosCount, users_stats.warezSent, users_stats.spamSent,
                users_stats.ipResets, users_stats.moneyEarned, users_stats.moneyTransfered, users_stats.moneyHardware, users_stats.moneyResearch, users_stats.profileViews,
                (SELECT COUNT(*) FROM missions_history WHERE missions_history.userID = users.id AND completed = 1), users_admin.userID
            FROM users
            LEFT JOIN clan_users
            ON clan_users.userID = users.id
            LEFT JOIN clan
            ON clan.clanID = clan_users.clanID
            INNER JOIN users_stats
            ON users_stats.uid = users.id
            LEFT JOIN ranking_user
            ON ranking_user.userID = users.id
            LEFT JOIN users_admin
            ON users_admin.userID = users.id
            WHERE users.id = :userID
            LIMIT 1
        ");
        $stmt->execute(array(':userID' => $userID));
        $row = $stmt->fetch(PDO::FETCH_NUM);

        if (!$row) {
            return;
        }

        list($login, $premium, $clanID, $clanName, $clanTag, $clanOwner, $ranking, $gameAge,
             $reputation, $timePlaying, $hackCount, $ddosCount, $warezSent, $spamSent,
             $ipResets, $moneyEarned, $moneyTransfered, $moneyHardware, $moneyResearch, $profileViews,
             $missionCount, $admin) = $row;

        if (!$gameAge) {
            $gameAge = 0;
        }

        if ($ranking == -1) {
            $stmt2 = $db->prepare("SELECT COUNT(*) AS total FROM ranking_user");
            $stmt2->execute();
            $rankRow = $stmt2->fetch(PDO::FETCH_NUM);
            $ranking = $rankRow[0];
        }

        if ($clanName) {
            $masterBadge = '';
            if ((string) $clanOwner === $userID) {
                $masterBadge = '<span class="label label-info right">' . _('Master') . '</span>';
            }

            $nick = '[' . $clanTag . '] ' . $login;
            $clan = '<tr>' . "\n" .
                    '						<td><span class="item">' . _('Clan') . '</span></td>' . "\n" .
                    '						<td><a href="clan?id=' . $clanID . '" class="black">[' . $clanTag . '] ' . $clanName . '</a>' . $masterBadge . '</td>' . "\n" .
                    '					</tr>' . "\n";
        } else {
            $nick = $login;
            $clan = "\n";
        }

        // COUNT friends
        $stmt3 = $db->prepare("
            SELECT COUNT(*) AS total
            FROM users_friends
            WHERE userID = :uid1 OR friendID = :uid2
        ");
        $stmt3->execute(array(':uid1' => $userID, ':uid2' => $userID));
        $friendCountRow = $stmt3->fetch(PDO::FETCH_NUM);
        $totalFriends = (int) $friendCountRow[0];

        // SELECT first 5 friends
        $stmt4 = $db->prepare("
            SELECT userID, friendID
            FROM users_friends
            WHERE userID = :uid1 OR friendID = :uid2
            ORDER BY dateAdd ASC
            LIMIT 5
        ");
        $stmt4->execute(array(':uid1' => $userID, ':uid2' => $userID));
        $friendRows = $stmt4->fetchAll(PDO::FETCH_NUM);

        $friendsHTML = '';
        foreach ($friendRows as $friendRow) {
            $friendID1 = (string) $friendRow[0];
            $friendID2 = (string) $friendRow[1];

            if ($friendID1 === $userID) {
                $friendID = $friendID2;
            } else {
                $friendID = $friendID1;
            }

            $stmt5 = $db->prepare("
                SELECT
                    login,
                    cache.reputation,
                    ranking_user.rank,
                    clan.name, clan.clanID
                FROM users
                LEFT JOIN cache
                ON cache.userID = users.id
                LEFT JOIN ranking_user
                ON ranking_user.userID = users.id
                LEFT JOIN clan_users
                ON clan_users.userID = users.id
                LEFT JOIN clan
                ON clan.clanID = clan_users.clanID
                WHERE users.id = :friendID
                LIMIT 1
            ");
            $stmt5->execute(array(':friendID' => $friendID));

            foreach ($stmt5->fetchAll(PDO::FETCH_NUM) as $fRow) {
                list($friendName, $friendReputation, $friendRank, $friendClanName, $friendClanID) = $fRow;

                $friendClanHTML = "\n";
                if ($friendClanName) {
                    $friendClanHTML = "\n" .
                        '											<span class="he16-clan heicon"></span>' . "\n" .
                        '											<small><a href="clan?id=' . $friendClanID . '">' . $friendClanName . '</a></small>' . "\n";
                }

                if (!$friendReputation) {
                    $friendReputation = 0;
                }

                $friendPic = 'images/profile/thumbnail/' . md5($friendName . $friendID) . '.jpg';
                if (!file_exists(BASE_PATH . $friendPic)) {
                    $friendPic = 'images/profile/thumbnail/unsub.jpg';
                }

                $friendsHTML .= '
                        <ul class="list">
                            <a href="profile?id=' . $friendID . '">
                                <li  class="li-click">
                                    <div class="span2 hard-ico">
                                        <img src="' . $friendPic . '">
                                    </div>
                                    <div class="span10">
                                        <div class="list-ip">
                                            ' . $friendName . '
                                        </div>
                                        <div class="list-user">
                                            <span class="he16-reputation heicon"></span>
                                            <small>' . $this->formatNumber($friendReputation) . '</small>
                                            <span class="he16-ranking heicon"></span>
                                            <small>#' . $this->formatNumber($friendRank) . '</small>' . $friendClanHTML . '                                        </div>
                                    </div>
                                    <div style="clear: both;"></div>
                                </li>
                            </a>
                        </ul>
                        ';
            }

            $friendsHTML .= '<div class="center">';
        }

        if ($totalFriends > 5) {
            $friendsHTML .= '<a href="profile?id=' . $userID . '&view=friends" class="btn btn-inverse">View all</a>&nbsp;&nbsp;';
        } elseif ($totalFriends == 0) {
            $friendsHTML .= _('Oh no! This user has no friends :(') . '<br/><br/>';
        }

        $friendsHTML .= '<a href="profile?view=friends&add=' . $userID . '" class="btn btn-success add-friend" value="' . $userID . '">' . _('Add friend') . '</a></div>';

        // Badges
        $totalBadges = 0;
        $htmlBadges = '';

        $stmt6 = $db->prepare("
            SELECT
                users_badge.badgeID,
                COUNT(users_badge.badgeID)
            FROM users_badge
            JOIN badges_users
            ON badges_users.badgeID = users_badge.badgeID
            WHERE users_badge.userID = :userID
            GROUP BY users_badge.badgeID
            ORDER BY badges_users.priority, badges_users.badgeID
        ");
        $stmt6->execute(array(':userID' => $userID));
        $badgeRows = $stmt6->fetchAll(PDO::FETCH_NUM);

        foreach ($badgeRows as $bRow) {
            $badgeID = $bRow[0];
            $badgeTotal = $bRow[1];

            $totalBadges += 1;
            $badgeInfo = $this->getBadgeInfo($badgeID);

            $badgeStr = '<strong>' . _($badgeInfo[0]) . '</strong>';
            if ($badgeInfo[1]) {
                $badgeStr .= ' - ' . _($badgeInfo[1]);
            }

            if ($badgeInfo[2]) {
                $badgeStr .= '<br/><br/>' . sprintf(ngettext('Awarded %d time', 'Awarded %d times', $badgeTotal), $badgeTotal);
            }

            $htmlBadges .= '<img src="images/badges/' . $badgeID . '.png" class="profile-tip" title="' . $badgeStr . '" value="' . $badgeID . '"/>';
        }

        if (!$totalBadges) {
            $htmlBadges = _('This player have no badges.');
        }

        $staffBadge = '';
        if ($admin) {
            $staffBadge = '<span class="label label-important">' . _('Staff') . '</span>';
        }

        $pic = 'images/profile/' . md5($login . $userID) . '.jpg';
        if (!file_exists(BASE_PATH . $pic)) {
            $pic = 'images/profile/unsub.jpg';
        }

        // Build HTML
        $html = '
	<span id="modal"></span>
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><i class="he16-pda"></i></span>
			<h5>' . $nick . '</h5>
			' . $staffBadge . '
		</div>
		<div class="widget-content nopadding">
			<table class="table table-cozy table-bordered table-striped table-fixed">
				<tbody>
					<tr>
						<td><span class="item">' . _('Reputation') . '</span></td>
						<td>' . $this->formatNumber($reputation) . ' <span class="small">(' . _('Ranked') . ' #' . $this->formatNumber($ranking) . ')</span></td>
					</tr>
					<tr>
						<td><span class="item">' . _('Age') . '</span></td>
						<td>' . $this->playTime($gameAge * 86400) . '</td>
					</tr>
					<tr>
						<td><span class="item">' . _('Time playing') . '</span></td>
						<td>' . $this->playTime((int) $timePlaying * 60) . '</td>
					</tr>
					' . $clan . '
				</tbody>
			</table>
		</div>
	</div>
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><i class="he16-stats"></i></span>
			<h5>' . _('Stats') . '</h5>
		</div>
		<table class="table table-cozy table-bordered table-striped table-fixed">
			<tbody>
				<tr>
					<td><span class="item">' . _('Hack count') . '</span></td>
					<td>' . $this->formatNumber($hackCount) . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('IP Resets') . '</span></td>
					<td>' . $this->formatNumber($ipResets) . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Servers used to DDoS') . '</span></td>
					<td>' . $this->formatNumber($ddosCount) . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Spam sent') . '</span></td>
					<td>' . $this->formatNumber($spamSent) . ' ' . _('mails') . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Warez uploaded') . '</span></td>
					<td>' . $this->formatNumber($warezSent) . ' GB</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Missions completed') . '</span></td>
					<td>' . $this->formatNumber($missionCount) . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Profile clicks') . '</span></td>
					<td>' . $this->formatNumber($profileViews) . '</td>
				</tr>
				<tr>
					<td><span class="item">' . _('Money earned') . '</span></td>
					<td><font color="green">$' . $this->formatNumber($moneyEarned) . '</font></td>
				</tr>
				<tr>
					<td><span class="item">' . _('Money transfered') . '</span></td>
					<td><font color="green">$' . $this->formatNumber($moneyTransfered) . '</font></td>
				</tr>
				<tr>
					<td><span class="item">' . _('Money spent on hardware') . '</span></td>
					<td><font color="green">$' . $this->formatNumber($moneyHardware) . '</font></td>
				</tr>
				<tr>
					<td><span class="item">' . _('Money spent on research') . '</span></td>
					<td><font color="green">$' . $this->formatNumber($moneyResearch) . '</font></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="center"><a class="btn btn-inverse center" type="submit">' . _('Switch to All-Time stats') . '</a></div>
</div>
<div class="span4">
	<div class="widget-box">
		<div class="widget-title">
			<span class="icon"><span class="he16-profile"></span></span>
			<h5>' . _('Photo & Badges') . '</h5>
			<span class="label label-info">' . $totalBadges . '</span>
		</div>
		<div class="widget-content padding noborder">
	        <div class="span12">
				<div class="span12" style="text-align: center; margin-right: 15px; margin-bottom: 5px;">
					<img src="' . $pic . '">
				</div>
                <div class="row-fluid">
                    <div class="span12 badge-div">
                        ' . $htmlBadges . '                	</div>
            	</div>
            </div>
		</div>
		<div style="clear: both;" class="nav nav-tabs">&nbsp;</div>
	</div>
<div class="widget-box">
	<div class="widget-title">
		<span class="icon"><i class="he16-clan"></i></span>
		<h5>' . _('Friends') . '</h5>
		<a href="profile?id=' . $userID . '&view=friends"><span class="label label-info">' . $totalFriends . '</span></a>
	</div>
	<div class="widget-content padding">
	' . $friendsHTML . '	</div>
	';

        // Write HTML to file
        $dir = BASE_PATH . 'html/profile/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir . $userID . '_' . $lang . '.html', $html);

        // INSERT/UPDATE cache_profile
        $stmt7 = $db->prepare("
            INSERT INTO cache_profile
                (userID, expireDate)
            VALUES
                (:userID, NOW())
            ON DUPLICATE KEY UPDATE expireDate = NOW()
        ");
        $stmt7->execute(array(':userID' => $userID));

        // UPDATE cache SET reputation
        $stmt8 = $db->prepare("UPDATE cache SET reputation = :reputation WHERE userID = :userID");
        $stmt8->execute(array(':reputation' => $reputation, ':userID' => $userID));
    }
}

?>
