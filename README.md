# Triggers

This plugin allows users to specify triggers for topics and keep a list of their own custom triggers. If a user tries to access a topic marked by a trigger that the user has also specified, a notification will appear. The user can then decide whether to read the topic anyway or return to the index.


``` ## Functions
__General Functions__
* Overview page of triggers used by other users
* Ability to specify and manage your own triggers
* Ability to specify triggers in new topics (if they belong to the relevant section)
* Display of triggers in the thread overview
* Display of triggers in the topic overview
* Warning if a topic has a trigger that you have also specified
* Output in a profile field
* Overview page can be accessed via `misc.php?action=trigger`; I have added a link in the UserCP

__Functions for Admins__
* Definition of areas in which triggers can be specified

## Prerequisites
None

## Template Changes
Additionally, a CSS file named trigger.css will be created

__New Templates:__
* `trigger_forumdisplay`
* `trigger_memberprofile`
* `trigger_misc`
* `trigger_misc_row`
* `trigger_newthread`
* `trigger_show_box_warning`
* `trigger_showthread`

__Modified Templates:__

* `showthread` (extended with the variables `$trigger_box_warning` and `$trigger`)
* `member_profile` (extended with the variable `$trigger`)
* `newthread` (extended with the variable `$trigger`)
* `editpost` (extended with the variable `$trigger`)
* `forumdisplay_thread` (extended with the variable `$trigger`)

## Preview Images
__View on the Overview Page__

![overview](https://aheartforspinach.de/upload/plugins/trigger_misc_overview.png)

__View on the Overview Page, Trigger Edit__

![managetrigger](https://aheartforspinach.de/upload/plugins/trigger_misc_manage.png)

__View in Thread Overview__

![threadoverview](https://aheartforspinach.de/upload/plugins/trigger_Forumdisplay.png)

__View in Thread View__

![showthread](https://aheartforspinach.de/upload/plugins/trigger_showthread.png)

__Warning in Thread View__

![warning](https://aheartforspinach.de/upload/plugins/trigger_warning.png)
