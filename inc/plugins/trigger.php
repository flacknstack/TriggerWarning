<?php

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function trigger_info()
{
    return array(
        "name"            => "Trigger System",
        "description"    => "Enables users to set triggers and alerts them to them.",
        "author"        => "aheartforspinach",
        "authorsite"    => "https://github.com/aheartforspinach",
        "version"        => "1.0",
        "compatibility" => "18*"
    );
}

function trigger_install()
{
    global $db;

    if ($db->engine == 'mysql' || $db->engine == 'mysqli') {
        $db->query("CREATE TABLE `" . TABLE_PREFIX . "trigger` (
        `uid` int(11) unsigned NOT NULL,
        `trigger` VARCHAR(1000),
        PRIMARY KEY (`uid`)
        ) ENGINE=MyISAM" . $db->build_create_table_collation());
    }

    if (!$db->field_exists('threadTrigger', 'threads')) {
        $db->add_column('threads', 'threadTrigger', 'VARCHAR(1000)');
    }

    // SETTINGS
    $setting_group = array(
        'name' => 'trigger',
        'title' => 'Trigger',
        'description' => 'Trigger Plugin Settings',
        'isdefault' => 0
    );
    $db->insert_query('settinggroups', $setting_group);
    $gid = $db->insert_id();

    $setting_array = array(
        'trigger_player_fid' => array(
            'title' => 'Profilfeld: Player Name',
            'description' => 'What is the ID for the player name profile field?',
            'optionscode' => 'numeric',
            'value' => -99,
            'disporder' => 1
        ),
        'trigger_fid' => array(
            'title' => 'Profilfeld: Profile Trigger',
            'description' => 'What is the ID for the profile field used for the trigger specification in the profile?',
            'optionscode' => 'numeric',
            'value' => -99,
            'disporder' => 2
        ),
        'trigger_excludedUids' => array(
            'title' => 'Excluded UIDs',
            'description' => 'Which UIDs should be excluded?',
            'optionscode' => 'text',
            'value' => '-99, -98',
            'disporder' => 3
        ),
        'trigger_forums' => array(
            'title' => 'Forum',
            'description' => 'In which forums should trigger warnings be provided?',
            'optionscode' => 'forumselect',
            'value' => '0',
            'disporder' => 4
        ),
    );

    // INSERT SETTINGS
    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }

    rebuild_settings();

    // CSS	
    $css = array(
        'name' => 'trigger.css',
        'tid' => 1,
        'attachedto' => 'misc.php?trigger|showthread.php|forumdisplay.php',
        "stylesheet" =>    '/* The Overlay (background) */
.overlay {
    height: 100%;
    width: 0;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    background-color: #1c1c1c;
    opacity: .9;
    color: #d9d9d9;
    overflow-x: hidden;
    transition: 0.5s;
}

/* Position the content inside the overlay */
.overlay-content {
    position: relative;
    top: 25%;
    width: 100%;
    text-align: center;
    margin-top: 30px;
}

.trigger-warning {
    color: #BC0000;
}
        
/* Style the tab */
.tab {
    overflow: hidden;
}

/* Style the buttons that are used to open the tab content */
.tab button {
        cursor: pointer;
        padding: 14px 16px;
        transition: 0.3s;
    background: #fff;
    box-shadow: none;
}

/* Create an active/current tablink class */
.tab button.active {
        background: #0076c5;
    color: #fff;
}

/* Style the tab content */
.tabcontent {
    display: none;
    padding-top: 10px;
} ',
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    // create templates
    $templategroup = array(
        'prefix' => 'trigger',
        'title' => $db->escape_string('Trigger'),
    );

    $db->insert_query("templategroups", $templategroup);

    $insert_array = array(
        'title'        => 'trigger_misc',
        'template'    => $db->escape_string('<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Trigger</title>
    {$headerinclude}
</head>

<body>
    {$header}
    <div class="panel" id="panel">
        <div id="panel">$menu</div>
        <h2 class="text-center">{$lang->trigger_trigger}</h2>

        <!-- Tab links -->
        <div class="tab" style="width:max-content;margin:auto;">
            <button class="tablinks" onclick="openTab(event, \'overview\')" id="defaultOpen">{$lang->trigger_tabOverview}</button>
            <if $mybb->user[\'uid\'] != 0 then><button class="tablinks" onclick="openTab(event, \'trigger\')">{$lang->trigger_tabManage}</button></if>
        </div>

        {$banner}

        <!-- Tab content -->
        <div id="overview" class="tabcontent">
            <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
                <tr>
                    <td class="thead" width="30%">{$lang->trigger_overviewName}</td>
                    <td class="thead" width="70%">{$lang->trigger_overviewTrigger}</td>
                </tr>
                {$entries}
            </table>
        </div>

        <div id="trigger" class="tabcontent">
            <div style="text-align:center;">
                <div style="width:75%;margin:auto;">{$lang->trigger_manageInfo}</div><br>

                <form method="post">
                    <input type="hidden" value="true" name="save_trigger" />
                    <input type="text" name="triggers" class="textbox" style="width:75%;" value="{$ownTrigger}" placeholder="{$lang->trigger_examples}" /><br><br>
                    <input type="submit" value="{$lang->trigger_manageButton}" class="button text-center">
                </form>
            </div>
        </div>

    </div>
    {$footer}
</body>

</html>

<script>
document.getElementById("defaultOpen").click();
function openTab(evt, tabname) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(tabname).style.display = "block";
    evt.currentTarget.className += " active";
} 
</script>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_misc_row',
        'template'    => $db->escape_string('<tr><td><b><center>{$name}</center></b></td><td>{$triggerFromUser}</td></tr>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_show_box_warning',
        'template'    => $db->escape_string('<!-- The overlay -->
<div id="triggerwarning" class="overlay">
    
    <!-- Overlay content -->
    <div class="overlay-content">
        <h1>{$lang->trigger_warning}</h1>
    {$lang->trigger_warningText} {$ownTriggers}<br>
        <br>
        <a href="{$mybb->settings[\'bburl\']}"><button>{$lang->trigger_backToIndex}</button></a> <button onclick="closeWarning()">{$lang->trigger_readThread}</button>
    </div>

</div>

<script>
    document.getElementById("triggerwarning").style.width = "100%";
    
    function closeWarning() {
        document.getElementById("triggerwarning").style.width = "0%";
    } 
</script>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_forumdisplay',
        'template'    => $db->escape_string('<font class="trigger-warning">{$lang->trigger_trigger}: {$triggerScene}</font><br>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_memberprofile',
        'template'    => $db->escape_string('{$lang->trigger_trigger}: {$triggerProfile}'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_showthread',
        'template'    => $db->escape_string('<font class="trigger-warning">{$lang->trigger_trigger}: {$triggerScene}</font><br>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title'        => 'trigger_newthread',
        'template'    => $db->escape_string('<tr>
    <td class="trow1" style="width: 20% !important;">
        <strong>{$lang->trigger_newthread}</strong>
    </td>
    <td class="trow1">
        <input type="text" class="textbox" name="threadTrigger" size="40" maxlength="155" value="{$trigger}" placeholder="{$lang->trigger_examples}" /> 
    </td>
</tr>'),
        'sid'        => '-2',
        'version'    => '',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    rebuild_settings();
}

function trigger_is_installed()
{
    global $db;
    return $db->table_exists("trigger");
}

function trigger_uninstall()
{
    global $db;
    $db->delete_query("templategroups", 'prefix = "trigger"');
    $db->delete_query("templates", "title like 'trigger%'");
    $db->delete_query('settings', "name LIKE 'trigger_%'");
    $db->delete_query('settinggroups', "name = 'trigger'");
    if ($db->table_exists("trigger")) $db->drop_table("trigger");
    if ($db->field_exists('threadTrigger', 'threads')) $db->drop_column('threads', 'threadTrigger');

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'trigger.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }
    rebuild_settings();
}

function trigger_activate()
{
    global $db, $cache;
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("showthread", "#" . preg_quote('{$header}') . "#i", '{$trigger_box_warning} {$header}');
    find_replace_templatesets("showthread", "#" . preg_quote('{$ratethread}') . "#i", '{$ratethread}
    {$trigger}');
    find_replace_templatesets("member_profile", "#" . preg_quote('{$header}') . "#i", '{$header} {$trigger}');
    find_replace_templatesets("newthread", "#" . preg_quote('{$posticons}') . "#i", '{$posticons} {$trigger}');
    find_replace_templatesets("editpost", "#" . preg_quote('{$posticons}') . "#i", '{$posticons} {$trigger}');
    find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('<div><span class="author smalltext">') . "#i", '{$trigger}
    <div><span class="author smalltext">');
}

function trigger_deactivate()
{
    global $db, $cache;
    include MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("showthread", "#" . preg_quote('{$trigger_box_warning}') . "#i", '', 0);
    find_replace_templatesets("showthread", "#" . preg_quote('{$trigger}') . "#i", '', 0);
    find_replace_templatesets("member_profile", "#" . preg_quote('{$trigger}') . "#i", '', 0);
    find_replace_templatesets("newthread", "#" . preg_quote('{$trigger}') . "#i", '', 0);
    find_replace_templatesets("editpost", "#" . preg_quote('{$trigger}') . "#i", '', 0);
    find_replace_templatesets("forumdisplay_thread", "#" . preg_quote('{$trigger}') . "#i", '', 0);
}

// 
// add new trigger and offer an overview
// 
$plugins->add_hook('misc_start', 'trigger_misc_start');
function trigger_misc_start()
{
    global $db, $mybb, $templates, $theme, $headerinclude, $header, $footer, $cache, $lang;
    if ($mybb->get_input('action') != 'trigger') return;
    if ($mybb->user['uid'] == 0) error_no_permission();
    if ($mybb->settings['trigger_player_fid'] == -99) error('Fülle bitte zunächst die FID zum Spielernamen aus');

    $lang->load('trigger');
    $ownTrigger = '';
    $ownUids = array();

    // 
    // overview
    // 
    $entries = '';
    $users = trigger_getAllUsers();
    foreach ($users as $name => $uids) {
        $row = $db->fetch_array($db->simple_select('trigger', '*', 'find_in_set(uid, "' . $uids . '")'));
        if (in_array($mybb->user['uid'], explode(',', $uids)) && $row['trigger'] != null) {
            $ownTrigger = $row['trigger'];
            $ownUids = $uids;
        }
        $triggerFromUser = $row['trigger'] == null ? '-' : $row['trigger'];
        eval("\$entries .= \"" . $templates->get('trigger_misc_row') . "\";");
    }

    // 
    // save trigger
    // 
    if ($_POST['save_trigger'] == 'true') {
        if (strlen($db->escape_string($_POST['triggers'])) == 0) { // edit own trigger -> input field is empty -> delete row
            $db->delete_query('trigger', 'find_in_set(uid, "' . $ownUids . '")');
        } else if (strlen($ownTrigger) > 1) { // edit own trigger -> new input field
            $entry = array('trigger' => $db->escape_string($_POST['triggers']));
            $db->update_query('trigger', $entry, 'find_in_set(uid, "' . $ownUids . '")');
        } else {
            $entry = array('uid' => $mybb->user['uid'], 'trigger' => $db->escape_string($_POST['triggers']));
            $db->insert_query('trigger', $entry);
        }
        redirect('misc.php?action=trigger', $lang->trigger_redirect);
    }

    eval("\$page = \"" . $templates->get('trigger_misc') . "\";");
    output_page($page);
}

// 
// show trigger in profile
// 
$plugins->add_hook('member_profile_start', 'trigger_member_profile_start');
function trigger_member_profile_start()
{
    global $mybb, $db, $trigger, $lang, $templates;
    if ($mybb->settings['trigger_fid'] == -99) return;
    $lang->load('trigger');

    $triggerField = intval($mybb->settings['trigger_fid']);
    $profile = intval($mybb->input['uid']);
    $row = $db->fetch_array($db->simple_select('userfields', 'fid' . $triggerField, 'ufid = ' . $profile));
    if ($row['fid' . $triggerField] != null) {
        $triggerProfile = $row['fid' . $triggerField];
        eval("\$trigger = \"" . $templates->get("trigger_memberprofile") . "\";");
    }
}

// 
// show trigger when doing new thread
// 
$plugins->add_hook('newthread_start', 'trigger_newthread_start');
function trigger_newthread_start()
{
    global $templates, $mybb, $lang, $fid, $post_errors, $trigger;
    $areas = explode(',', $mybb->settings['trigger_forums']);
    $lang->load('trigger');

    if (in_array($fid, $areas)) {
        // previewing new thread?
        if (isset($mybb->input['previewpost']) || $post_errors) {
            $trigger = htmlspecialchars_uni($mybb->get_input('threadTrigger'));
        }

        eval("\$trigger = \"" . $templates->get("trigger_newthread") . "\";");
    }
}

$plugins->add_hook('newthread_do_newthread_end', 'trigger_do_newthread');
function trigger_do_newthread()
{
    global $mybb, $db, $fid, $tid;
    $areas = explode(',', $mybb->settings['trigger_forums']);

    if (in_array($fid, $areas)) {
        $update = array(
            'threadTrigger' => $db->escape_string($mybb->input['threadTrigger'])
        );
        $db->update_query('threads', $update, 'tid = ' . $tid);
    }
}

// 
// show trigger when editing
// 
$plugins->add_hook('editpost_end', 'trigger_editpost_end');
function trigger_editpost_end()
{
    global $templates, $mybb, $lang, $thread, $forum, $trigger;
    $areas = explode(',', $mybb->settings['trigger_forums']);
    $lang->load('trigger');

    if (in_array($forum['fid'], $areas)) {
        $trigger = $thread['threadTrigger'];
        eval("\$trigger = \"" . $templates->get("trigger_newthread") . "\";");
    }
}

$plugins->add_hook('editpost_do_editpost_end', 'trigger_do_editpost_end');
function trigger_do_editpost_end()
{
    global $mybb, $db, $forum, $tid, $lang;
    $areas = explode(',', $mybb->settings['trigger_forums']);
    $lang->load('trigger');

    if (in_array($forum['fid'], $areas)) {
        $update = array(
            'threadTrigger' => $db->escape_string($mybb->input['threadTrigger'])
        );

        $db->update_query('threads', $update, 'tid = ' . $tid);
    }
}

// 
// show trigger in thread
// 
$plugins->add_hook('showthread_start', 'trigger_showthread_forumdisplay');
function trigger_showthread_forumdisplay(&$thread)
{
    global $db, $trigger, $tid, $fid, $templates, $mybb, $trigger_box_warning, $lang;

    $lang->load('trigger');
    trigger_getTextWarning($thread, 'trigger_showthread');

    // 
    // trigger warning window
    $threadTrigger = explode(', ', $db->fetch_array($db->simple_select('threads', 'threadTrigger', 'tid = ' . $tid))['threadTrigger']);

    $trigger_box_warning = '';
    $ownUids = trigger_getOwnUids();
    $row = $db->fetch_array($db->simple_select('trigger', '*', 'find_in_set(uid, "' . $ownUids . '")'));
    $ownTrigger = explode(', ', $row['trigger']);
    if ($ownTrigger[0] == '') return;

    $triggerList = array();
    foreach ($ownTrigger as $single_trigger) {
        if (in_array($single_trigger, $threadTrigger)) array_push($triggerList, $single_trigger);
    }

    if (empty($triggerList)) return;
    $ownTriggers = implode(', ', $triggerList);
    eval("\$trigger_box_warning = \"" . $templates->get('trigger_show_box_warning') . "\";");
}

$plugins->add_hook('forumdisplay_thread_end', 'trigger_forumdisplay_thread_end');
function trigger_forumdisplay_thread_end(&$thread)
{
    trigger_getTextWarning($thread, 'trigger_forumdisplay');
}

function trigger_getTextWarning(&$thread, $template) {
    global $db, $trigger, $thread, $tid, $templates, $fid, $lang;
    $lang->load('trigger');

    $templateName = $template == null ? 'trigger_forumdisplay' : $template;
    $tid = $thread == null ? $tid : $thread['tid'];
    $fid = $thread == null ? $fid : $thread['fid'];

    $trigger = '';
    $triggerScene = $db->fetch_array($db->simple_select('threads', 'threadTrigger', 'tid = ' . $tid))['threadTrigger'];

    if ($triggerScene == null) return;
    eval("\$trigger = \"" . $templates->get($templateName) . "\";");
}

// return an array with name => uids
function trigger_getAllUsers()
{
    global $db, $mybb;
    $playerFid = 'fid' . $mybb->settings['trigger_player_fid'];
    $excludeUids = $mybb->settings['trigger_excludedUids'];
    $users = array();
    $query = $db->simple_select('userfields', 'ufid, ' . $playerFid, 'not ufid in (' . $excludeUids . ') and not ' . $playerFid . ' = ""', array('order_by' => $playerFid, 'order_dir' => 'asc'));
    while ($row = $db->fetch_array($query)) {
        if ($users[$row[$playerFid]] == null) {
            $users[$row[$playerFid]] = $row['ufid'];
        } else {
            $users[$row[$playerFid]] .=  ',' . $row['ufid'];
        }
    }
    return $users;
}

// return own uids() 
function trigger_getOwnUids()
{
    global $mybb;
    $ownName = $mybb->user['fid' . $mybb->settings['trigger_player_fid']];
    $allUids = trigger_getAllUsers();
    return $allUids[$ownName];
}
