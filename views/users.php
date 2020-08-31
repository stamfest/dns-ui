<?php
##
## Copyright 2013-2018 Opera Software AS
##
## Licensed under the Apache License, Version 2.0 (the "License");
## you may not use this file except in compliance with the License.
## You may obtain a copy of the License at
##
## http://www.apache.org/licenses/LICENSE-2.0
##
## Unless required by applicable law or agreed to in writing, software
## distributed under the License is distributed on an "AS IS" BASIS,
## WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
## See the License for the specific language governing permissions and
## limitations under the License.
##

$users = $user_dir->list_users();

if(!$active_user->admin) {
	require('views/error403.php');
	die;
}

$user = new User;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(isset($_POST['add_user']) && $active_user->admin) {
		$user->auth_realm = 'local';
		$user->uid = $_POST['uid'];
		$user->name = $_POST['name'];
		$user->email = $_POST['email'];
		$user->active = 1;
		$user->admin = isset($_POST['admin']) ? 1 : 0;

		// use a flag-based approach to handle the complicated control-flow. We 
		// do this to be able to fall through to the setup of the next view in
		// order to preserve as much entered data as possible in case of an error
		$ok_to_add = false;
		if ($config['authentication']['form_based'] == "database") {
			if ($_POST['password1'] !== $_POST['password2']) {
				$alert = new UserAlert;
				$alert->content = "Passwords do not match";
				$alert->class = 'danger';
				$active_user->add_alert($alert);

				$ok_to_add = false;
			} else if ($_POST['password1'] !== '') {



				try {
					if ($user->is_password_complexity_ok($_POST['password1'])) {
						$user->password = password_hash($_POST['password1'], PASSWORD_DEFAULT);
						$ok_to_add = true;
					}
				} catch(PasswordComplexityException $e) {
					$alert = new UserAlert;
					$alert->content = $e->getMessage();
					$alert->class = 'danger';
					$active_user->add_alert($alert);
					$ok_to_add = false;
				}
			} else {
				$alert = new UserAlert;
				$alert->content = "Password MUST NOT be empty";
				$alert->class = 'danger';
				$active_user->add_alert($alert);

				$ok_to_add = false;
			}
		} else {
			// not a database-based login setup
			$ok_to_add = true;
		}
		if ($ok_to_add) {
			try {
				$user_dir->add_user($user);
				$alert = new UserAlert;
				$alert->content = 'User \'<a href="'.rrurl('/users/'.urlencode($user->uid)).'" class="alert-link">'.hesc($user->uid).'</a>\' added.';
				$alert->escaping = ESC_NONE;
				$alert->class = 'success';
				$active_user->add_alert($alert);

				// update users list
				$users = $user_dir->list_users();
			} catch(UserAlreadyExistsException $e) {
				$alert = new UserAlert;
				$alert->content = 'A user with user ID of \'<a href="'.rrurl('/users/'.urlencode($user->uid)).'" class="alert-link">'.hesc($user->uid).'</a>\' already exists.';
				$alert->escaping = ESC_NONE;
				$alert->class = 'danger';
				$active_user->add_alert($alert);
			}
		}
	}
}

$content = new PageSection('users');
$content->set('users', $users);
$content->set('newuser', $user);

$page = new PageSection('base');
$page->set('title', 'Users');
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());

echo $page->generate();
