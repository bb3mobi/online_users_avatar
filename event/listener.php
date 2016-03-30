<?php

/**
*
* @package Online users avatars
* @copyright Anvar 2016 (c) http://bb3.mobi
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace bb3mobi\online_users_avatar\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $user;

	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	static public function getSubscribedEvents()
	{
		return array(
			/**
			* Modify SQL query to obtain online users data
			*
			* @event core.obtain_users_online_string_sql
			* @var	array	online_users	Array with online users data
			*								from obtain_users_online()
			* @var	int		item_id			Restrict online users to item id
			* @var	string	item			Restrict online users to a certain
			*								session item, e.g. forum for
			*								session_forum_id
			* @var	array	sql_ary			SQL query array to obtain users online data
			* @since 3.1.4-RC1
			* @changed 3.1.7-RC1			Change sql query into array and adjust var accordingly. Allows extension authors the ability to adjust the sql_ary.
			*/
			'core.obtain_users_online_string_sql'		=> 'obtain_users_online_string_sql',
			/**
			* Modify online userlist data
			*
			* @event core.obtain_users_online_string_modify
			* @var	array	online_users		Array with online users data
			*									from obtain_users_online()
			* @var	int		item_id				Restrict online users to item id
			* @var	string	item				Restrict online users to a certain
			*									session item, e.g. forum for
			*									session_forum_id
			* @var	array	rowset				Array with online users data
			* @var	array	user_online_link	Array with online users items (usernames)
			* @var	string	online_userlist		String containing users online list
			* @var	string	l_online_users		String with total online users count info
			* @since 3.1.4-RC1
			*/
			'core.obtain_users_online_string_modify'	=> 'obtain_users_online_string_modify',

			/**
			* Modify SQL query to obtain wwhlight users data
			*
			* @event wwhlight.obtain_users_online_string_sql
			* @var	array	sql_ary		SQL query array to obtain users online data
			*/
			'wwhlight.obtain_users_online_string_sql'	=> 'obtain_users_online_string_sql',
			/**
			* Modify wwhlight userlist data
			*
			* @event wwhlight.obtain_users_online_string_modify
			* @var	array	rowset				Array with wwhlight users data
			* @var	array	user_online_link	Array with wwhlight users usernames
			* @var	string	online_userlist		String containing users online list
			*/
			'wwhlight.obtain_users_online_string_modify'	=> 'obtain_users_online_string_modify',
		);
	}

	/** Добавляем запрос данных об аватарах пользователей */
	public function obtain_users_online_string_sql($event)
	{
		$sql_ary = $event['sql_ary'];
		$sql_ary['SELECT'] .= ', u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height';
		$event['sql_ary'] = $sql_ary;
	}

	/** Переписывем существующие списки онлайн пользователей*/
	public function obtain_users_online_string_modify($event)
	{
		$u_online = $event['user_online_link'];
		$online_users = $event['online_users'];

		/** Вариант 1 */
		$online_userlist = $event['online_userlist'];

		$to = $replace = array();
		foreach ($event['rowset'] as $row)
		{
			if (!isset($u_online[$row['user_id']]))
			{
				continue;
			}

			$replace_avatar = '<span title="' . $row['username'] . '">' . $this->a_img($row) .  '</span>';

			if (isset($online_users['hidden_users'][$row['user_id']]))
			{
				$row['username'] = '<em>' . $row['username'] . '</em>';
			}

			$to[] = $row['username'];
			$replace[] = $replace_avatar;
		}

		if (sizeof($to))
		{
			// Заменяем имя пользователя на изображение аватара
			$online_userlist = str_replace($to, $replace, $online_userlist);
			// Зарегистрированные пользователи и запятые под снос..
			$online_userlist = str_replace(array($this->user->lang['REGISTERED_USERS'], ','), '', $online_userlist);
		}

		/** Вариант 2 */
		/*
		foreach ($event['rowset'] as $row)
		{
			if (!isset($u_online[$row['user_id']]))
			{
				continue;
			}

			// Аватар пользователя со ссылкой на профиль
			if ($row['user_type'] <> USER_IGNORE || !isset($online_users['hidden_users'][$row['user_id']]))
			{
				$u_online[$row['user_id']] = '<a class="lastpostavatar" href="' . get_username_string('profile', $row['user_id'], $row['username'], $row['user_colour']) . '" title="' . $row['username'] . '">' . $this->a_img($row) . '</a>';
			}
			else
			{
				$u_online[$row['user_id']] = '<span class="lastpostavatar" title="' . $row['username'] . '">' . $this->a_img($row) . '</span>';
			}
		}
		$online_userlist = implode(' ', $u_online);*/

		$event['online_userlist'] = $online_userlist;
	}

	private function a_img($avatar)
	{
		if (!empty($avatar['user_avatar']))
		{
			// Выравниваем по высоте
			$avatar['user_avatar_width'] = round(40/$avatar['user_avatar_height']*$avatar['user_avatar_width']);
			$avatar['user_avatar_height'] = 40;

			return phpbb_get_user_avatar($avatar);
		}

		$theme = generate_board_url() . "/styles/" . rawurlencode($this->user->style['style_path']) . '/theme';
		return '<img class="avatar" src="' . $theme . '/images/no_avatar.gif" width="40" height="40" alt="" />';
	}
}
