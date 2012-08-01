<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Osmium\Page\ViewProfile;

require __DIR__.'/../inc/root.php';

if(!isset($_GET['accountid'])) {
	\Osmium\fatal(404, 'No accountid given.');
}

$row = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT accountid, creationdate, lastlogindate, apiverified, nickname, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator, flagweight, reputation FROM osmium.accounts WHERE accountid = $1', array($_GET['accountid'])));

if($row === false) {
	\Osmium\fatal(404, 'Invalid accountid.');
}

$a = \Osmium\State\get_state('a', array());
$myprofile = \Osmium\State\is_logged_in() && $a['accountid'] == $_GET['accountid'];
$ismoderator = isset($a['ismoderator']) && $a['ismoderator'] === 't';

$name = \Osmium\Chrome\get_name($row, $rname);
\Osmium\Chrome\print_header(htmlspecialchars($rname)."'s profile", '..');

echo "<div id='vprofile'>\n";
echo "<header>\n";
$sep = "<tr class='sep'><td colspan='3'> </td></tr>\n";

$moderator = ($row['ismoderator'] === 't') ? 'Moderator '.\Osmium\Flag\MODERATOR_SYMBOL : '';
$isthisme = $myprofile ? '(this is you!)' : '';
echo "<h2>".$name." <small>$isthisme $moderator</small></h2>\n";

if($row['apiverified'] === 't') {
	$allianceid = (($row['allianceid'] == null) ? 1 : $row['allianceid']);
	$alliancename = ($allianceid === 1) ? '(no alliance)' : $row['alliancename'];

	echo "<p>\n<img src='http://image.eveonline.com/Character/".$row['characterid']."_512.jpg' alt='portrait' /><br />";
	echo "<img src='http://image.eveonline.com/Corporation/".$row['corporationid']."_256.png' alt='corporation logo' title='".htmlspecialchars($row['corporationname'], ENT_QUOTES)."' />";
	echo "<img src='http://image.eveonline.com/Alliance/".$allianceid."_128.png' alt='alliance logo' title='".htmlspecialchars($alliancename, ENT_QUOTES)."' /></p>\n";
}

echo "<table>\n<tbody>\n";

if($row['apiverified'] === 't') {
	echo "<tr>\n<th rowspan='2'>character</th>\n<td>corporation</td>\n<td>".htmlspecialchars($row['corporationname'])."</td>\n</tr>\n<tr>\n<td>alliance</td>\n<td>".htmlspecialchars($alliancename)."</td>\n</tr>\n";
	echo $sep;
}

echo "<tr>\n<th rowspan='2'>visits</th>\n<td>member for</td>\n<td>".\Osmium\Chrome\format_long_duration(time() - $row['creationdate'])."</td>\n</tr>\n<tr>\n<td>last seen</td>\n<td>".(($s = \Osmium\Chrome\format_long_duration(time() - $row['lastlogindate'], null)) === null ? 'today' : $s.' ago')."</td>\n</tr>\n";

echo $sep;

echo "<tr>\n<th rowspan='2'>meta</th>\n<td>api key verified</td>\n<td>".(($row['apiverified'] === 't') ? 'yes' : 'no')."</td>\n</tr>\n<tr>\n<td>reputation score</td>\n<td>".number_format($row['reputation'])."</td>\n</tr>\n";

if($myprofile || $ismoderator) {
	echo $sep;
	echo "<tr>\n<th>private</th>\n<td>flag weight</td>\n<td>".$row['flagweight']." <a href='../flagginghistory/".$row['accountid']."'>(see flagging history)</a></td>\n</tr>\n";
}

echo "</tbody>\n</table>\n</header>\n";

echo "<ul class='tabs'>\n";
if($myprofile) {
	echo "<li><a href='#pfavorites'>Favorites</a></li>\n";
	echo "<li><a href='#phidden'>Hidden</a></li>\n";
}
echo "<li><a href='#ploadouts'>Recent</a></li>\n";
echo "<li><a href='#reputation'>Reputation</a></li>\n";
//echo "<li><a href='#votes'>Votes</a></li>\n";
//echo "<li><a href='#comment'>Comments</a></li>\n";
echo "</ul>\n";

echo "<section id='ploadouts' class='psection'>\n";
echo "<h2>Loadouts recently submitted <small><a href=\"../search?q=".urlencode('@author "'.htmlspecialchars($rname, ENT_QUOTES).'"')."\">(browse all)</a></small></h2>\n";
\Osmium\Search\print_pretty_results("..", '@author "'.$rname.'"', 'ORDER BY creationdate DESC', false, 5, 'p', htmlspecialchars($rname).' does not have submitted any loadouts.');
echo "</section>\n";

if($myprofile) {
	$a = \Osmium\State\get_state('a');
	/* TODO: paginate this */

	echo "<section id='pfavorites' class='psection'>\n<h2>My favorite loadouts</h2>\n";
	$favorites = array();
	$favq = \Osmium\Db\query_params('SELECT loadoutid FROM osmium.accountfavorites WHERE accountid = $1 ORDER BY favoritedate DESC', array($a['accountid']));
	while($r = \Osmium\Db\fetch_row($favq)) {
		$favorites[] = $r[0];
	}
	\Osmium\Search\print_loadout_list($favorites, '..', 0, 'You have no favorited loadouts.');
	echo "</section>\n";

	echo "<section id='phidden' class='psection'>\n<h2>My hidden loadouts</h2>\n";
	$hidden = array();
	$hiddenq = \Osmium\Db\query_params('SELECT loadoutid FROM osmium.loadouts WHERE accountid = $1 AND visibility = $2 ORDER BY loadoutid DESC', array($a['accountid'], \Osmium\Fit\VISIBILITY_PRIVATE));
	while($r = \Osmium\Db\fetch_row($hiddenq)) {
		$hidden[] = $r[0];
	}
	\Osmium\Search\print_loadout_list($hidden, '..', 0, 'You have no hidden loadouts.');	
	echo "</section>\n";
}

echo "<section id='reputation' class='psection'>\n";
echo "<h2>Reputation changes this month</h2>\n";

$repchangesq = \Osmium\Db\query_params(
	'SELECT v.creationdate, reputationgiventodest, type, targettype, targetid1, targetid2, targetid3,
		sl.loadoutid, f.name
	FROM osmium.votes AS v
	LEFT JOIN osmium.searchableloadouts AS sl ON ((v.targettype = $3 AND v.targetid1 = sl.loadoutid
		AND v.targetid2 IS NULL AND v.targetid3 IS NULL)
		OR (v.targettype = $4 AND v.targetid2 = sl.loadoutid AND v.targetid3 IS NULL))
	LEFT JOIN osmium.loadoutslatestrevision AS llr ON llr.loadoutid = sl.loadoutid
	LEFT JOIN osmium.loadouthistory AS lh ON lh.loadoutid = sl.loadoutid AND lh.revision = llr.latestrevision
	LEFT JOIN osmium.fittings AS f ON f.fittinghash = lh.fittinghash
	WHERE v.accountid = $1 AND v.creationdate >= $2 AND reputationgiventodest <> 0
	ORDER BY creationdate DESC',
	array($_GET['accountid'],
	      time() - 86400 * 365.25 / 12,
	      \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT,
	      \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
		)
	);
echo "<ul>\n";
$lastday = null;
$first = true;
$data = array();

function print_reputation_day($day, $data) {
	$net = 0;
	foreach($data as $d) $net += $d['reputationgiventodest'];
	$class = ($net >= 0) ? 'positive' : 'negative';

	echo "<li>\n<h4>$day <span class='$class'>$net</span></h4>\n";
	echo "<table class='d'>\n<tbody>\n";

	foreach($data as $d) {
		echo "<tr>\n";
		$rep = $d['reputationgiventodest'];
		$class = ($rep >= 0) ? 'positive' : 'negative';
		if($rep > 0) $rep = '+'.$rep;
		echo "<td class='rep $class'>$rep</td>\n";

		$time = date('H:i', $d['creationdate']);
		echo "<td class='time'>$time</td>\n";

		if($d['type'] == \Osmium\Reputation\VOTE_TYPE_UP) $type = 'upvote';
		else if($d['type'] == \Osmium\Reputation\VOTE_TYPE_DOWN) $type = 'downvote';
		else $type = 'unknown';
		echo "<td class='type'>$type</td>\n";

		echo "<td class='l'>";
		if($d['name'] !== null) {
			echo "<a href='../loadout/".$d['loadoutid']."'>".htmlspecialchars($d['name'])."</a>";
		} else {
			echo "<small>Private/hidden loadout</small>";
		}
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "</tbody>\n</table>\n</li>\n";
}

while($r = \Osmium\Db\fetch_assoc($repchangesq)) {
	$day = date('Y-m-d', $r['creationdate']);
	if($lastday !== $day) {
		if($first) $first = false;
		else {
			print_reputation_day($lastday, $data);
		}

		$lastday = $day;
		$data = array();
	}

	$data[] = $r;
}
if(!$first) print_reputation_day($day, $data);
echo "</ul>\n";
if($first) echo "<p class='placeholder'>No reputation changes this month.</p>\n";
echo "</section>\n";

echo "</div>\n";
\Osmium\Chrome\print_js_snippet('tabs');
\Osmium\Chrome\print_js_snippet('view_profile');
\Osmium\Chrome\print_js_code('osmium_tabify($("div#vprofile > ul.tabs"), '.($myprofile ? 2 : 0).');');
\Osmium\Chrome\print_footer();
