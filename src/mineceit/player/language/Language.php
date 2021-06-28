<?php

declare(strict_types=1);

namespace mineceit\player\language;

use mineceit\arenas\FFAArena;
use mineceit\kits\DefaultKit;
use mineceit\MineceitUtil;
use mineceit\player\ranks\Rank;
use pocketmine\utils\TextFormat;

class Language{

	public const CREATE_RANK_TITLE = "rank-create.form.title";
	public const CREATE_RANK_DESC = "rank-create.form.desc";
	public const CREATE_RANK_NAME = "rank-create.form.rank-name";
	public const CREATE_RANK_FORMAT = "rank-create.form.rank-format";
	public const CREATE_RANK_FLY = "rank-create.form.fly";
	public const CREATE_RANK_BUILDER_MODE = "rank-create.form.place-break";
	public const CREATE_RANK_PERMS = "rank-create.form.perms";

	public const KIT_EDIT_FORM_SETTINGS = "kit.form.edit-settings";
	public const KIT_EDIT_FORM_TITLE = "kit.form.edit-title";
	public const KIT_EDIT_FORM_DESC = "kit.form.edit-desc";
	public const KIT_EDIT_MODE = "kit.edit.message";
	public const KIT_EDIT_SUCCESS_MODE = "kit.edit.success-message";
	public const KIT_RECEIVE = 'kit.receive';
	public const KIT_FAIL_RECEIVE = 'kit.fail.receive';
	public const LIST_KITS = 'list.kits';
	public const KIT_NO_EXIST = 'kit.no-exist';

	public const SPAWN_SCOREBOARD_ONLINE = 'spawn.online';
	public const SPAWN_SCOREBOARD_INFIGHTS = 'spawn.infights';
	public const SPAWN_SCOREBOARD_INQUEUES = 'spawn.inqueues';
	public const SPAWN_SCOREBOARD_QUEUE = 'spawn.queue';
	public const SPAWN_SCOREBOARD_DEATHS = 'spawn.deaths';
	public const SPAWN_SCOREBOARD_KILLS = 'spawn.kills';

	public const PLAYER_CPS = 'player.cps';
	public const PLAYER_PING = 'player.ping';

	public const FFA_SCOREBOARD_ARENA = 'ffa.sb.arena';

	public const FFA_FORM_TITLE = 'ffa.form.title';
	public const FFA_FORM_DESC = 'ffa.form.content';
	public const FFA_FORM_NUMPLAYERS = 'ffa.form.num-players';

	public const CHANGE_SETTINGS_FORM_TITLE = 'change_settings.form.title';
	public const CHANGE_SETTINGS_FORM_SCOREBOARD = 'change_settings.form.scoreboard';
	public const CHANGE_SETTINGS_FORM_PEONLY = 'change_settings.form.pe-only';
	public const CHANGE_SETTINGS_FORM_CPSPOPUP = 'change_settings.form.cpspopup';
	public const CHANGE_SETTINGS_FORM_AUTORESPAWN = 'change_settings.form.autorespawn';
	public const CHANGE_SETTINGS_FORM_AUTOSPRINT = 'change_settings.form.autosprint';
	public const CHANGE_SETTINGS_FORM_AUTOGG = 'change_settings.form.autogg';
	public const CHANGE_SETTINGS_FORM_MORECRIT = 'change_settings.form.morecrit';
	public const CHANGE_SETTINGS_FORM_LIGHTNING = 'change_settings.form.lightning';
	public const CHANGE_SETTINGS_FORM_BLOOD = 'change_settings.form.blood';

	public const BUILDER_MODE_FORM_ENABLE = 'builder-mode.form.enable';
	public const BUILDER_MODE_FORM_DISABLE = 'builder-mode.form.disable';
	public const BUILDER_MODE_FORM_TITLE = 'builder-mode.form.title';
	public const BUILDER_MODE_FORM_DESC = 'builder-mode.form.desc';
	public const BUILDER_MODE_FORM_LEVEL_NONE = 'builder-mode.form.level-desc.none';
	public const BUILDER_MODE_FORM_LEVEL = 'builder-mode.form.level-desc.level';

	public const SETTINGS_FORM_TITLE = 'settings.form.title';
	public const SETTINGS_FORM_CHANGE_SETTINGS = 'settings.form.change_settings';

	public const LANGUAGE_FORM_TITLE = 'lang.form.title';

	public const ENTER_ARENA = 'arena.enter';

	public const ENTER_SPAWN = 'spawn.enter';

	public const ARENA_EXISTS = 'arena.exist';
	public const ARENA_NO_EXIST = 'arena.no-exist';
	public const CREATE_ARENA = 'arena.create';

	public const SET_IN_COMBAT = 'msg.combat.set';
	public const REMOVE_FROM_COMBAT = 'msg.combat.remove';

	public const DONT_INTERRUPT = 'msg.no.interrupt';
	public const INCORRECT_TARGET = 'msg.incorrect.target';

	public const SET_IN_ENDERPEARLCOOLDOWN = 'msg.epearl.set';
	public const REMOVE_FROM_ENDERPEARLCOOLDOWN = 'msg.epearl.remove';
	public const SET_IN_GAPPLECOOLDOWN = 'msg.gapple.set';
	public const REMOVE_FROM_GAPPLECOOLDOWN = 'msg.gapple.remove';
	public const SET_IN_ARROWCOOLDOWN = 'msg.arrow.set';
	public const REMOVE_FROM_ARROWCOOLDOWN = 'msg.arrow.remove';

	public const BREAK_UR_WOOL = 'msg.break.urwool';

	public const DUEL_ENTER_QUEUE = 'duel.queue.enter';
	public const DUEL_LEAVE_QUEUE = 'duel.queue.leave';

	public const RANK_CREATE_SUCCESS = 'rank.create.success';
	public const RANK_CREATE_FAIL = 'rank.create.fail';
	public const RANK_EXISTS = 'rank.exists';
	public const RANK_NO_EXIST = 'rank.no-exist';
	public const RANK_DELETE = 'rank.delete';

	public const DUELS_RANKED_FORM_TITLE = 'duels.form.ranked-title';
	public const DUELS_UNRANKED_FORM_TITLE = 'duels.form.unranked-title';
	public const DUELS_FORM_INQUEUES = 'duels.form.inqueues';
	public const DUELS_FORM_DESC = 'duels.form.desc';

	public const DUELS_SCOREBOARD_DURATION = 'duels.sb.duration';

	public const DUELS_MESSAGE_COUNTDOWN = 'duels.countdown.message';
	public const DUELS_MESSAGE_STARTING = 'duels.starting.message';

	public const DUELS_MESSAGE_WINNER = 'duels.message.winner';
	public const DUELS_MESSAGE_LOSER = 'duels.message.loser';
	public const DUELS_MESSAGE_ELOCHANGES = 'duels.message.elo-change';
	public const DUELS_MESSAGE_SPECTATORS = 'duels.message.spectators';

	public const DUELS_SPECTATOR_ADD = 'duels.add.spectator';
	public const DUELS_SPECTATOR_LEAVE = 'duels.leave.spectator';

	public const GAMES_MESSAGE_COUNTDOWN = 'games.countdown.message';

	public const PLAYER_NOT_ONLINE = 'players.not_online';
	public const PLAYER_IN_PARTY = 'players.in_party';
	public const PLAYER_JOIN_PARTY = 'players.join_party';
	public const PLAYER_LEAVE_PARTY = 'players.leave_party';
	public const PLAYER_DISBAND_PARTY = 'players.disband_party';
	public const PLAYER_PROMOTE_PARTY = 'players.promote_party';
	public const CANT_PROMOTE_PARTY = 'cant.promote_party';
	public const PLAYER_ADD_BLACKLIST = 'players.add_blacklist';
	public const PLAYER_REMOVE_BLACKLIST = 'players.remove_blacklist';
	public const CANT_USE_INPARTY = 'cant.use_inparty';

	public const INFO_OF = 'player.info';

	public const NONE = 'form.label.none';
	public const GO_BACK = 'form.go-back';

	public const DUEL_HISTORY_FORM_DESC = 'duel-history.form.desc';
	public const DUEL_HISTORY_FORM_TITLE = 'duel-history.form.title';

	public const DUEL_INVENTORY_FORM_VIEW = 'duel-inv.form.desc';
	public const DUEL_INVENTORY_TITLE = 'duel-inv.form.title';

	public const PLAYERS_LABEL = 'form.label.players';
	public const GAMEMODE_FORM_MENU_LABEL = 'form.label.gamemode';

	public const COMMAND_FAIL_IN_COMBAT = 'command.fail.in-combat';
	public const COMMAND_FAIL_IN_DUEL = 'command.fail.in-duel';

	public const FREEZE_FORM_ENABLE = 'freeze.form.enable';
	public const FREEZE_FORM_DISABLE = 'freeze.form.disable';

	public const UNFREEZE_TITLE = 'unfreeze.title';
	public const FREEZE_TITLE = 'freeze.title';

	public const UNFREEZE_SUBTITLE = 'unfreeze.subtitle';
	public const FREEZE_SUBTITLE = 'freeze.subtitle';

	public const GAMEMODE_CHANGE = 'msg.change.gamemode';

	public const REQUEST_FORM_SEND_TO = 'request.form.send-to';
	public const REQUEST_FORM_SELECT_QUEUE = 'request.form.select-queue';
	public const REQUEST_FORM_RANKED_OR_UNRANKED = 'request.form.unranked-ranked';
	public const REQUEST_FORM_NOBODY_ONLINE = 'request.form.nobody-online';
	public const REQUEST_FORM_TITLE = 'request.form.title';

	public const FORM_SENT_BY = 'form.label.sent-by';
	public const FORM_CLICK_REQUEST_ACCEPT = 'click-request.form';
	public const FORM_TITLE_REQUEST_ACCEPT = 'title-request.form';

	public const DUEL_ACCEPTED_REQUEST_TO = 'duel.msg.accept-req.to';
	public const DUEL_ACCEPTED_REQUEST_FROM = 'duel.msg.accept-req.from';
	public const DUEL_FOUND_MATCH = 'duel.found.match';

	public const SENT_REQUEST = 'duel.msg.sent-req';
	public const RECEIVE_REQUEST = 'duel.msg.receive-req';
	public const ACCEPT_FAIL_PLAYER_IN_DUEL = 'accept.fail.in-duel';
	public const ACCEPT_FAIL_PLAYER_IN_ARENA = 'accept.fail.in-arena';
	public const ACCEPT_FAIL_PLAYER_IN_PARTY = 'accept.fail.in-party';
	public const ACCEPT_FAIL_PLAYER_WATCH_REPLAY = 'accept.fail.player-replay';
	public const ACCEPT_FAIL_PLAYER_IN_EVENT = 'accept.fail.in-event';

	public const IN_HUB = 'msg.in-hub';

	public const PLAYER_HEAL = 'cmd.heal';

	public const DELETE_ARENA = 'arena.delete';

	public const NO_LONGER_FLYING = 'flying.stop';
	public const NOW_FLYING = 'flying.now';

	public const LIST_RANKS = 'list.ranks';

	public const STATS_OF = 'stats.of';
	public const ELO_OF = 'elo.of';

	public const ONLY_USE_IN_LOBBY = 'use-in-lobby';

	public const SPECTATE_FORM_TITLE = 'spectate.form.title';
	public const SPECTATE_FORM_DESC = 'spectate.form.desc';

	public const DUEL_ALREADY_ENDED = 'duel.ended-already';

	public const PLAYER_SET_RANKS = 'player.set-ranks';
	public const PLAYER_SET_RANKS_FAIL = 'player.set-ranks.fail';

	public const PLACE_LEADERBOARD_HOLOGRAM = 'player.place.leaderboard-hologram';

	public const ACCEPT_FAIL_NOT_IN_LOBBY = 'accept.fail.not-in-lobby';

	public const NO_SPAM = 'player.no-spam';
	public const MUTED = 'player.muted';

	public const SENT_INVITE = 'parties.msg.sent-inv';
	public const RECEIVE_INVITE = 'parties.msg.receive-inv';
	public const PARTIES_ACCEPTED_INVITE_TO = 'parties.msg.accept-inv.to';
	public const PARTIES_ACCEPTED_INVITE_FROM = 'parties.msg.accept-inv.from';

	public const FORM_PARTIES_DEFAULT_TITLE = 'parties.form.default.title';
	public const FORM_PARTIES_DEFAULT_CONTENT = 'parties.form.default.content';
	public const FORM_PARTIES_DEFAULT_LEAVE = 'parties.form.default.leave';
	public const FORM_PARTIES_DEFAULT_JOIN = 'parties.form.default.join';

	public const FORM_PARTIES_OPTION_TITLE = 'parties.form.option.title';
	public const FORM_PARTIES_OPTION_CONTENT = 'parties.form.option.content';
	public const FORM_PARTIES_OPTION_SETTINGS = 'parties.form.option.settings';
	public const FORM_PARTIES_OPTION_INVITE = 'parties.form.option.invite';
	public const FORM_PARTIES_OPTION_KICK = 'parties.form.option.kick';
	public const FORM_PARTIES_OPTION_BLACKLIST = 'parties.form.option.blacklist';
	public const FORM_PARTIES_OPTION_OWNER = 'parties.form.option.owner';

	public const FORM_PARTIES_CREATE_TITLE = 'parties.form.create.title';
	public const FORM_PARTIES_INBOX_TITLE = 'parties.form.inbox.title';
	public const FORM_PARTIES_JOIN_TITLE = 'parties.form.join.title';
	public const FORM_PARTIES_LEAVE_TITLE = 'parties.form.leave.title';
	public const FORM_PARTIES_KICK_TITLE = 'parties.form.kick.title';
	public const FORM_PARTIES_BLACKLIST_TITLE = 'parties.form.blacklist.title';
	public const FORM_PARTIES_BLACKLIST_ADD_TITLE = 'parties.form.blacklist.add.title';
	public const FORM_PARTIES_BLACKLIST_REMOVE_TITLE = 'parties.form.blacklist.remove.title';
	public const FORM_PARTIES_SETTINGS_TITLE = 'parties.form.settings.title';
	public const FORM_PARTIES_OWNER_TITLE = 'parties.form.owner.title';
	public const FORM_PARTIES_DUELS_TITLE = 'parties.form.duels.title';
	public const FORM_PARTIES_GAMES_TITLE = 'parties.form.games.title';

	public const FORM_PARTIES_CREATE = 'parties.form.create';
	public const FORM_PARTIES_INBOX = 'parties.form.inbox';
	public const FORM_PARTIES_INVITE = 'parties.form.invite';
	public const FORM_PARTIES_NO_PLAYERS = 'parties.form.noplayers';
	public const FORM_PARTIES_REASON = 'parties.form.reason';
	public const FORM_PARTIES_BLACKLIST = 'parties.form.blacklist';
	public const FORM_PARTIES_CANT_BLACKLIST = 'parties.form.cantblacklist';
	public const FORM_PARTIES_NO_BLACKLIST = 'parties.form.noblacklist';
	public const FORM_PARTIES_BLACKLIST_ADD = 'parties.form.blacklist.add';
	public const FORM_PARTIES_BLACKLIST_REMOVE = 'parties.form.blacklist.remove';

	public const FORM_PARTIES_PARTYNAME = 'parties.form.partyname';
	public const FORM_PARTIES_MAX_PLAYERS = 'parties.form.max_players';
	public const FORM_PARTIES_INVITE_ONLY = 'parties.form.invite-only';

	public const PARTIES_ALREADY_TAKEN = 'parties.already.taken';
	public const PARTIES_ALREADY_EXIST = 'parties.already.exist';
	public const PARTIES_ALREADY_FULL = 'parties.already.full';
	public const PARTIES_ALREADY_BLACKLIST = 'parties.already.blacklist';
	public const PARTIES_ALREADY_INQUEUE = 'parties.already.inqueue';
	public const PARTIES_ALREADY_INGAME = 'parties.already.ingame';
	public const PARTIES_INVITE_ONLY = 'parties.invite.only';
	public const PARTIES_PLAYER_NOT_ENOUGH = 'parties.players.not-enough';
	public const PARTIES_PLAYER_NOT_MATCH = 'parties.players.not-match';

	public const PARTIES_ENTER_QUEUE = 'parties.queue.enter';
	public const PARTIES_LEAVE_QUEUE = 'parties.queue.leave';
	public const PARTIES_FOUND_MATCH = 'parties.found.match';
	public const PARTIES_DUEL_ELIMINATED = 'parties.duel.eliminated';

	public const FORM_VIEW_REPLAY = 'history.form.replay';
	public const SCOREBOARD_REPLAY_PAUSED = 'replays.sb.paused';

	public const FORM_PARTIES_JOIN_LIST = 'parties.form.join.list';
	public const BLACKLISTED = 'form.label.blacklisted';
	public const OPEN = 'form.label.open';
	public const CLOSED = 'form.label.closed';

	public const FORM_QUESTION_LEAVE = 'party.form.question.leave';
	public const FORM_QUESTION_LEAVE_OWNER = 'party.form.question.leave-owner';

	public const YES = 'form.label.yes';
	public const NO = 'form.label.no';

	public const CHANGE_LANGUAGE_FORM = "settings.form.change-lang";
	public const FORM_PARTIES_SETTINGS = 'parties.form.settings';

	public const TRANSLATE_MESSAGES = "change_settings.form.translate";
	public const COINNOTI_MESSAGES = "change_settings.form.coinnoti";
	public const EXPNOTI_MESSAGES = "change_settings.form.expnoti";
	public const SILENT_MESSAGES = "change_settings.form.silent";

	public const LANGUAGE_CREDIT = "language.credit";

	public const GAMEMODE_SURVIVAL = "gamemode.survival";
	public const GAMEMODE_CREATIVE = "gamemode.creative";
	public const GAMEMODE_SPECTATOR = "gamemode.spectator";
	public const GAMEMODE_ADVENTURE = "gamemode.adventure";

	public const PLAYER_SET_MUTED = "player.mute.set.receiver";
	public const PLAYER_SET_UNMUTED = "player.unmute.set.receiver";
	public const PLAYER_SET_MUTED_SENDER = "player.mute.set.sender";
	public const PLAYER_SET_UNMUTED_SENDER = "player.unmute.set.sender";

	public const STATS_RANK_LABEL = "stats.label.rank";

	public const HUB_PLAY_FORM_UNRANKED_DUELS = "hub.form.play-ur-duels";
	public const HUB_PLAY_FORM_RANKED_DUELS = "hub.form.play-r-duels";
	public const HUB_PLAY_FORM_DUELS = "hub.form.play-duels";
	public const HUB_PLAY_DUEL_FORM_DESC = "hub.form.play.duel-desc";
	public const HUB_DUELS_ACCEPT = "hub.form.accept-duels";
	public const HUB_DUELS_REQUEST = "hub.form.request-duels";
	public const HUB_DUELS_SPEC = "hub.form.spec-duels";
	public const HUB_DUELS_HISTORY = "hub.form.history-duels";

	public const HUB_BOT_FORM_TITLE = "hub.form.bot-title";
	public const HUB_BOT_FORM_DESC = "hub.form.bot-desc";
	public const HUB_BOT_EASY_FORM = "hub.form.bot-easy";
	public const HUB_BOT_MEDIUM_FORM = "hub.form.bot-medium";
	public const HUB_BOT_HARD_FORM = "hub.form.bot-hard";
	public const HUB_BOT_HACKER_FORM = "hub.form.bot-hacker";
	public const HUB_BOT_CLUTCH_FORM = "hub.form.bot-clutch";

	public const EVENT_FORM_TITLE = "event.form.title";
	public const EVENT_FORM_DESC = "event.form.desc";
	public const EVENTHOST_FORM_TITLE = "eventhost.form.title";
	public const EVENTHOST_FORM_DESC = "eventhost.form.desc";
	public const EVENT_FORM_EVENT_FORMAT = "event.form.format";
	public const EVENT_FORM_LABEL_IN_PROGRESS = "event.form.label.in_progress";
	public const EVENT_FORM_LABEL_ENDING = "event.form.label.ending";

	public const EVENT_SCOREBOARD_STARTING_IN = "event.sb.starting-in";
	public const EVENT_SCOREBOARD_EVENT_TYPE = "event.sb.type";
	public const EVENT_SCOREBOARD_ELIMINATED = "event.sb.eliminated";

	public const EVENTS_MESSAGE_COUNTDOWN = "events.message.countdown";
	public const EVENTS_MESSAGE_RESULT = "events.message.result";
	public const EVENTS_MESSAGE_STARTING_NOW = "events.message.starting-now";

	public const EVENTS_MESSAGE_JOIN_FAIL_STARTED = "events.message.join.fail.started";
	public const EVENTS_MESSAGE_JOIN_FAIL_PLAYERS = "events.message.join.fail.players";
	public const EVENTS_MESSAGE_JOIN_FAIL_RUNNING = "events.message.join.fail.running";
	public const EVENTS_MESSAGE_JOIN_SUCCESS = "events.message.join.success";

	public const EVENTS_MESSAGE_LEAVE_EVENT_SENDER = "events.message.leave.sender";
	public const EVENTS_MESSAGE_LEAVE_EVENT_RECEIVER = "events.message.leave.receiver";

	public const EVENTS_MESSAGE_RUNNING = "events.message.running";
	public const EVENTS_MESSAGE_CANCELED = "events.message.canceled";
	public const EVENTS_MESSAGE_ELIMINATED = "events.message.eliminated";

	public const EVENTS_MESSAGE_DUELS_MATCHED = "events.message.duels.matched";

	public const REPORTS_MENU_FORM_TITLE = "reports.menu.form.title";
	public const REPORTS_MENU_FORM_BUTTON = "reports.menu.form.button";
	public const REPORTS_MENU_FORM_DESC = "reports.menu.form.desc";
	public const REPORTS_MENU_FORM_STAFF = "reports.menu.form.staff";
	public const REPORTS_MENU_FORM_BUG = "reports.menu.form.bug";
	public const REPORTS_MENU_FORM_TRANSLATION = "reports.menu.form.translation";
	public const REPORTS_MENU_FORM_HACKER = "reports.menu.form.hacker";
	public const REPORTS_MENU_FORM_YOUR_HISTORY = "reports.menu.form.your-report-history";
	public const REPORTS_MENU_FORM_VIEW_REPORTS = "reports.menu.form.view-reports";
	public const REPORTS_MENU_FORM_MANAGE_REPORTS = "reports.menu.form.manage-reports";

	public const STAFF_REPORT_FORM_TITLE = "staff.report.form.title";
	public const STAFF_REPORT_FORM_DESC = "staff.report.form.desc";
	public const STAFF_REPORT_FORM_MEMBERS = "staff.report.form.members";

	public const FORM_LABEL_REASON_FOR_REPORTING = "form.label.reason-report";

	public const BUG_REPORT_FORM_TITLE = "bug.report.form.title";
	public const BUG_REPORT_FORM_DESC = "bug.report.form.desc";
	public const BUG_REPORT_FORM_DESC_LABEL_HEADER = "bug.report.form.desc-label.header";
	public const BUG_REPORT_FORM_DESC_LABEL_FOOTER = "bug.report.form.desc-label.footer";
	public const BUG_REPORT_FORM_REPROD_LABEL_HEADER = "bug.report.form.reprod-label.header";
	public const BUG_REPORT_FORM_REPROD_LABEL_FOOTER = "bug.report.form.reprod-label.footer";

	public const TRANSLATION_REPORT_FORM_TITLE = "translate.report.form.title";
	public const TRANSLATION_REPORT_FORM_DESC = "translate.report.form.desc";
	public const TRANSLATION_REPORT_FORM_DROPDOWN = "translate.report.form.dropdown";
	public const TRANSLATION_REPORT_FORM_LABEL_ORIGINAL = "translate.report.form.label.original";
	public const TRANSLATION_REPORT_FORM_LABEL_NEW_TOP = "translate.report.form.label.new-top";
	public const TRANSLATION_REPORT_FORM_LABEL_NEW_BOTTOM = "translate.report.form.label.new-bottom";

	public const HACK_REPORT_FORM_TITLE = "hack.report.form.title";
	public const HACK_REPORT_FORM_DESC = "hack.report.form.desc";

	public const CHANGE_SETTINGS_FORM_HIT_SOUNDS = "change-settings.form.hit-sounds";

	public const REPORT_HISTORY_FORM_TITLE = "report-history.form.title";
	public const REPORT_HISTORY_FORM_DESC = "report-history.form.desc";
	public const REPORT_HISTORY_FORM_FORMAT = "report-history.form.format";

	public const FORM_LABEL_REPORTS = "form.label.reports";
	public const FORM_LABEL_RESOLVED = "form.label.resolved";
	public const FORM_LABEL_UNRESOLVED = "form.label.unresolved";
	public const FORM_LABEL_STATUS = "form.label.status";
	public const FORM_LABEL_AUTHOR = "form.label.author";
	public const FORM_LABEL_DATE = "form.label.date";
	public const FORM_LABEL_TIME = "form.label.time";
	public const FORM_LABEL_PLAYER = "form.label.player";
	public const FORM_LABEL_REASON = "form.label.reason";
	public const FORM_LABEL_LANGUAGE = "form.label.language";
	public const FORM_LABEL_STAFF_MEMBER = "form.label.staff-member";
	public const FORM_LABEL_ORIGINAL_MESSAGE = "form.label.original-msg";
	public const FORM_LABEL_NEW_MESSAGE = "form.label.new-msg";
	public const FORM_LABEL_BUG = "form.label.bug";
	public const FORM_LABEL_REPRODUCED = "form.label.reproduced";

	public const SEARCH_RESULTS_REPORTS_FORM_TITLE = "search-results-reports.form.title";
	public const SEARCH_RESULTS_REPORTS_FORM_DESC = "search-results-reports.form.desc";
	public const SEARCH_RESULTS_REPORTS_FORM_FORMAT = "search-results-reports.form.format";

	public const REPORT_INFO_FORM_CHANGE_STATUS = "report-info.form.status";

	public const FORM_LABEL_TIMESPAN = "form.label.timespan";
	public const FORM_LABEL_LAST_HOUR = "form.label.last-hour";
	public const FORM_LABEL_LAST_12_HOURS = "form.label.last-12-hours";
	public const FORM_LABEL_LAST_24_HOURS = "form.label.last-24-hours";
	public const FORM_LABEL_LAST_WEEK = "form.label.last-week";
	public const FORM_LABEL_LAST_MONTH = "form.label.last-month";
	public const FORM_LABEL_REPORT_TYPES = "form.label.report-types";
	public const FORM_LABEL_SEARCH = "form.label.search";

	public const SEARCH_REPORTS_FORM_DESC = "search-reports.form.desc";

	public const NO_STAFF_MEMBERS_ONLINE = "msg.staff.not-online";
	public const NO_OTHER_PLAYERS_ONLINE = "msg.players.not-online";

	public const REPORT_NO_REASON = "msg.report.no-reason";
	public const REPORT_PLAYER_ALREADY = "msg.report.player-already";
	public const REPORT_SUBMIT_SUCCESS = "msg.report.submit-success";
	public const REPORT_SUBMIT_FAILED = "msg.report.submit.fail";
	public const REPORT_FIVE_WORDS = "msg.report.five-words";
	public const REPORT_TRANSLATE_NEW_PHRASE = "msg.report.provide-new-phrase";
	public const REPORT_TRANSLATE_ORIG_PHRASE = "msg.report.provide-orig-phrase";

	public const COSMETIC_FORM = "cosmetic.form.settings";
	public const COSMETIC_FORM_TITLE = "cosmetic.form.title";
	public const ARTIFACT_FORM = "artifact.form.settings";
	public const STATS_FORM = "stats.form.settings";
	public const POT_COLORS = "potcolors.form.settings";
	public const RESET_SKIN = "resetskin.form.settings";
	public const CUSTOM_CAPES = "capes.form.settings";
	public const CUSTOM_SKINS = "skins.form.settings";
	public const CUSTOM_DISGUISE = "disguise.form.settings";

	public const REPLAY_FORM_TITLE = "replay.form.title";
	public const REPLAY_SKIP_SECONDS = "replay.skip.seconds";
	public const REPLAY_TIME_SCALE = "replay.time.scale";

	public const TAG_CATELOGY = 'msg.tag.category';
	public const TAG_CATELOGY_TITLE = "tag.form.title";
	public const ZEQA_SHOP_TITLE = 'zeqa.form.title';
	public const ARTIFACT_SHOP_TITLE = 'artifact.form.title';
	public const ARTIFACT_SHOP = 'msg.artifact.shop';
	public const CAPE_SHOP_TITLE = 'cape.form.title';
	public const CAPE_SHOP = 'msg.cape.shop';
	public const TAG_SHOP_TITLE = 'tags.form.title';
	public const TAG_SHOP = 'msg.tag.shop';
	public const SHARD_SHOP_TITLE = 'shard.form.title';
	public const BATTLE_PASS_TITLE = 'battle.form.title';
	public const SHARD_SHOP = 'msg.shard.shop';
	public const RESET_SKIN_MSG = 'msg.reset.skin';
	public const CHANGE_POT_COLOR_TO = 'msg.pot.color';
	public const SUCCESS_DISGUISE = 'msg.success.disguise';
	public const RESET_DISGUISE = 'msg.reset.disguise';
	public const DISGUISE_ONLINE_PLAYER = 'msg.disguise.asplayer';
	public const MORE_ALPHABETS_15 = 'msg.nomore.alphabet15';
	public const MORE_ALPHABETS_25 = 'msg.nomore.alphabet25';
	public const ONLY_ENG = 'msg.only.eng';
	public const CAPE_CONTENT = 'cape.content';
	public const SKIN_CONTENT = 'skin.content';
	public const DISGUISE_CONTENT = 'disguise.content';
	public const DISGUISE_BUTTON = 'disguise.button';
	public const UNDISGUISE_BUTTON = 'undisguise.button';
	public const DISGUISE_INPUT = 'disguise.input';
	public const CHANGE_TAG_TO = 'msg.change.tag';
	public const CHANGE_CAPE_TO = 'msg.change.cape';
	public const CHANGE_COSMETIC_TO = 'msg.change.cosmetic';
	public const CAPE_WHILE_DISGUISE = 'msg.cape.wdisguise';
	public const COSMETIC_WHILE_DISGUISE = 'msg.cosmetic.wdisguise';
	public const POT_WHILE_DISGUISE = 'msg.pot.wdisguise';

	public const CLUTCH_SETTING_TITLE = 'clutch.setting.title';
	public const CLUTCH_SETTING_KNOCKBACK = 'clutch.setting.knockback';
	public const CLUTCH_SETTING_HITREG = 'clutch.setting.hitreg';
	public const CLUTCH_SETTING_ATCOOLDOWN = 'clutch.setting.attackcooldown';

	// TODO MESSAGES FOR ALL OF THESE

	public const EVENT_WINNER_ANNOUNCEMENT = "announcement.event.winner";
	public const EVENT_MESSAGE_PLAYERS_LEFT = "msg.event.players-left";

	public const NEW_TRANSLATION_REPORT = "report.notice.translation";
	public const NEW_HACK_REPORT = "report.notice.hack";
	public const NEW_STAFF_REPORT = "report.notice.staff";
	public const NEW_BUG_REPORT = "report.notice.bug";

	// Auction Message
	public const AUCTION_HOUSE = 'auction.house';
	public const AUCTION_FORM_TITLE = 'auction.form.title';
	public const AUCTION_FORM_DESC = 'auction.form.desc';
	public const AUCTION_FORM_CREATE = 'auction.form.create';
	public const AUCTION_FORM_ONGOING = 'auction.form.ongoing';
	public const AUCTION_FORM_ENDED = 'auction.form.ended';
	public const AUCTION_FORM_CREATE_TITLE = 'auction.form-create.title';
	public const AUCTION_FORM_CREATE_ITEM = 'auction.form-create.item';
	public const AUCTION_FORM_CREATE_CURRENCY = 'auction.form-create.currency';
	public const AUCTION_FORM_CREATE_PRICE = 'auction.form-create.price';
	public const AUCTION_FORM_CREATE_BID = 'auction.form-create.bid';
	public const AUCTION_FORM_CREATE_DURATION = 'auction.form-create.duration';
	public const AUCTION_FORM_ONGOING_TITLE = 'auction.form-ongoing.title';
	public const AUCTION_FORM_BID_TITLE = 'auction.form-bid.title';
	public const AUCTION_FORM_ENDED_TITLE = 'auction.form-ended.title';
	public const NOT_ENOUGH_COINS = 'auction.msg.not-enough-coins';
	public const NOT_ENOUGH_SHARDS = 'auction.msg.not-enough-shards';
	public const NOT_ENOUGH_BID = 'auction.msg.not-enough-bid';
	public const BID_SUCCESS = 'auction.msg.bid-success';
	public const ITEM_ENDED = 'auction.msg.item-ended';
	public const COIN_BACK_AUCTION = 'auction.msg.coin-back';
	public const SHARD_BACK_AUCTION = 'auction.msg.shard-back';
	public const ITEM_BACK_AUCTION = 'auction.msg.item-back';
	public const AUCTION_ENDED = 'auction.msg.auction-ended';
	public const LIMIT_ONGOING_ITEM = 'auction.msg.limit-ongoing-item';
	public const AUCTION_CREATE_SUCCESS = 'auction.msg.create-success';
	public const CANNOT_BID_OWN_AUCTION = 'auction.msg.cant-bid-own-auction';
	public const ONLY_PUT_NUMBER = 'auction.msg.only-put-number';

	// Declare language locale constants.
	public const ENGLISH_US = "en_US";
	public const SIMPLIFIED_CHINESE = "zh_CN";
	public const THAI = "th_TH";

	/** @var string[]|array */
	protected $names;

	/** @var string[]|array */
	protected $translateName;

	/** @var string */
	protected $locale;

	/** @var array */
	protected $messages;

	/** @var string */
	protected $oldLocalName;

	/** @var array */
	protected $itemNames;

	/** @var string */
	protected $credit;

	/** @var bool */
	protected $shorten;

	/** @var array */
	protected $ordinals;

	/** @var bool */
	protected $shortenOrdinals;

	public function __construct(string $locale, array $translationNames, array $names, array $messages = [], array $itemNames = [], array $ordinals = [], string $oldLocalName = "", bool $shorten = true, string $credit = "", bool $shortenOrdinals = false){
		$this->names = $names;
		$this->locale = $locale;
		$this->translateName = $translationNames;
		$this->messages = $messages;
		$this->oldLocalName = $oldLocalName;
		$this->itemNames = $itemNames;
		$this->credit = $credit;
		$this->shorten = $shorten;
		$this->ordinals = $ordinals;
		$this->shortenOrdinals = $shortenOrdinals;
	}

	/**
	 * @return bool
	 *
	 * Shortens the ordinals.
	 */
	public function doesShortenOrdinals() : bool{
		return $this->shortenOrdinals;
	}

	/**
	 * @param $val
	 *
	 * @return string
	 *
	 * Gets the ordinal number of a value.
	 */
	public function getOrdinalOf($val) : string{

		$val = strval($val);

		if(isset($this->ordinals[$val])){
			return $this->ordinals[$val];
		}

		return $this->ordinals["other"];
	}

	/**
	 * @return bool
	 *
	 * Determines whether the language has a credit.
	 */
	public function hasCredit() : bool{
		return $this->credit !== "";
	}

	/**
	 * @return string
	 *
	 * The person who actually made the language.
	 */
	public function getCredit() : string{
		return $this->credit;
	}

	/**
	 * @return bool
	 *
	 * Shortens the string -> Use is for fixing UTF-8 encoding problems for forms.
	 */
	public function shortenString() : bool{
		return $this->shorten;
	}

	/**
	 * @param string $type
	 *
	 * @return string|null
	 *
	 * Gets the item name based on the type.
	 */
	public function getItemName(string $type) : ?string{

		if(isset($this->itemNames[$type])){
			return $this->convertString($this->itemNames[$type]);
		}

		return null;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 *
	 * Converts the string from data to a new string with colors.
	 */
	private function convertString(string $string) : string{

		$replaced = [
			"{BLUE}" => TextFormat::BLUE,
			"{GREEN}" => TextFormat::GREEN,
			"{RED}" => TextFormat::RED,
			"{DARK_RED}" => TextFormat::DARK_RED,
			"{PREFIX}" => MineceitUtil::getPrefix(),
			"{DARK_BLUE}" => TextFormat::DARK_BLUE,
			"{DARK_AQUA}" => TextFormat::DARK_AQUA,
			"{DARK_GREEN}" => TextFormat::DARK_GREEN,
			"{GOLD}" => TextFormat::GOLD,
			"{GRAY}" => TextFormat::GRAY,
			"{DARK_GRAY}" => TextFormat::DARK_GRAY,
			"{DARK_PURPLE}" => TextFormat::DARK_PURPLE,
			"{LIGHT_PURPLE}" => TextFormat::LIGHT_PURPLE,
			"{RESET}" => TextFormat::RESET,
			"{YELLOW}" => TextFormat::YELLOW,
			"{AQUA}" => TextFormat::AQUA,
			"{BOLD}" => TextFormat::BOLD,
			"{WHITE}" => TextFormat::WHITE
		];

		foreach($replaced as $search => $replace){

			if(strpos($string, $search) !== false){

				$string = str_replace($search, $replace, $string);
			}
		}

		return $string;
	}

	/**
	 * @param DefaultKit|string $kit
	 * @param string            $type
	 *
	 * @return string|null
	 */
	public function kitMessage($kit, string $type) : ?string{

		$kitName = ($kit instanceof DefaultKit) ? $kit->getName() : $kit;

		$name = "{name}";

		if(isset($this->messages[$type])){

			$string = $this->convertString($this->messages[$type]);

			if(strpos($string, $name) !== false){

				$string = str_replace($name, $kitName, $string);
			}

			return $string;
		}

		return null;
	}

	/**
	 * @param string               $type
	 * @param FFAArena|string|null $arena
	 *
	 * @return string|null
	 */
	public function arenaMessage(string $type, $arena = null) : ?string{

		$arenaName = "Unknown";

		if($arena !== null){
			$arenaName = $arena instanceof FFAArena ? $arena->getName() : $arena;
		}

		$name = "{name}";

		if(isset($this->messages[$type])){

			$message = $this->convertString($this->messages[$type]);

			if(strpos($message, $name) !== false){
				$message = str_replace($name, $arenaName, $message);
			}

			return $message;
		}

		return null;
	}

	/**
	 * @param string $type
	 *
	 * @return string|null
	 */
	public function scoreboard(string $type) : ?string{

		if(isset($this->messages[$type])){

			$message = $this->messages[$type];

			return $this->convertString($message);
		}

		return null;
	}

	/**
	 * @param string         $type
	 * @param array|string[] $replaceables
	 *
	 * @return string|null
	 */
	public function formWindow(string $type, array $replaceables = []) : ?string{
		return $this->getMessage($type, $replaceables);
	}

	/**
	 * @param string $type
	 * @param array  $replaceables
	 *
	 * @return string|null
	 *
	 * Gets a message in general based on its type. Used for future use so
	 * we don't need to use the other functions here.
	 *
	 */
	public function getMessage(string $type, array $replaceables = []) : ?string{

		if(isset($this->messages[$type])){

			$message = $this->convertString($this->messages[$type]);

			foreach($replaceables as $key => $value){

				$search = "{{$key}}";

				if(strpos($message, $search) !== false){

					$message = str_replace($search, $value, $message);
				}
			}

			return $message;
		}

		return null;
	}

	/**
	 * @param string         $type
	 * @param array|string[] $list
	 *
	 * @return string|null
	 */
	public function listMessage(string $type, array $list = []) : ?string{

		$listString = join(", ", $list);

		if($listString === ""){

			$listString = $this->getMessage(self::NONE);
		}

		return $this->getMessage($type, ["list" => $listString]);
	}

	/**
	 * @param string $type
	 * @param array  $replaceables
	 *
	 * @return string|null
	 */
	public function generalMessage(string $type, $replaceables = []) : ?string{

		return $this->getMessage($type, $replaceables);
	}

	/**
	 * @param string      $type
	 * @param string      $queue
	 * @param bool        $ranked
	 * @param string|null $player
	 *
	 * @return string|null
	 */
	public function getDuelMessage(string $type, string $queue = "", bool $ranked = false, string $player = null) : ?string{

		$ranked = $this->getRankedStr($ranked);

		return $this->getMessage($type, ["ranked" => $ranked ?? "Unranked", "queue" => $queue, "name" => $player ?? "{name}"]);
	}

	/**
	 * @param bool $ranked
	 *
	 * @return string|null
	 */
	public function getRankedStr(bool $ranked) : ?string{

		$result = ($ranked ? "ranked" : "unranked");

		return $this->getMessage("label.{$result}");
	}

	/**
	 * @param string      $type
	 * @param string      $queue
	 * @param int         $size
	 * @param string|null $player
	 *
	 * @return string|null
	 */
	public function getPartyDuelMessage(string $type, string $queue = "", int $size = 2, string $player = null) : ?string{

		$size = $size . 'vs' . $size;

		return $this->getMessage($type, ["size" => $size, "queue" => $queue, "name" => $player ?? "{name}"]);
	}

	/**
	 * @param Rank|string $rank
	 * @param string      $type
	 *
	 * @return string|null
	 */
	public function rankMessage($rank, string $type) : ?string{

		$name = $rank instanceof Rank ? $rank->getName() : $rank;

		return $this->getMessage($type, ["name" => $name]);
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function hasTranslationName(string $type) : bool{
		return $this->getTranslationName($type) !== '';
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 *
	 * Gets the name used by google translate api.
	 */
	public function getTranslationName(string $type) : string{
		return isset($this->translateName[$type]) ? $this->translateName[$type] : "";
	}

	/**
	 * @param string $locale
	 *
	 * @return string
	 *
	 * Gets the name based on the locale.
	 */
	public function getNameFromLocale(string $locale = self::ENGLISH_US) : string{
		return isset($this->names[$locale]) ? $this->names[$locale] : $this->getName();
	}

	/**
	 * @return string
	 *
	 * Gets the default name.
	 */
	public function getName() : string{
		return $this->names[$this->locale];
	}

	/**
	 * @return array|string[]
	 */
	public function getNames() : array{
		return $this->names;
	}

	/**
	 * @param string $name
	 * @param string $locale
	 *
	 * @return bool
	 *
	 * Determines if the language contains the name. Can be strict based on locale.
	 */
	public function hasName(string $name, string $locale = "") : bool{

		if($locale !== "" && isset($this->names[$locale])){
			$resultingName = $this->names[$locale];
			return $resultingName === $name;
		}

		$values = array_values($this->names);

		return array_search($name, $values) !== false;
	}

	/**
	 * @return string
	 *
	 * Gets the old local name used, returns "" if no old local name exists.
	 */
	public function getOldLocalName() : string{
		return $this->oldLocalName;
	}


	/**
	 * @return string|null
	 *
	 * Gets the general permission message.
	 */
	public function getPermissionMessage() : ?string{
		return $this->getMessage("general.permission.message");
	}

	/**
	 * @param Language $lang
	 *
	 * @return bool
	 *
	 * Determines if a language is equal to another.
	 */
	public function equals(Language $lang) : bool{
		return $this->locale === $lang->getLocale();
	}

	/**
	 * @return string
	 *
	 * Gets the locale of the language.
	 */
	public function getLocale() : string{
		return $this->locale;
	}
}
