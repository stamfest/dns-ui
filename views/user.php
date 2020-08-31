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

try {
	$user = $user_dir->get_user_by_uid($router->vars['uid']);
} catch(UserNotFound $e) {
	require('views/error404.php');
	die;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(isset($_POST['update_user']) && $active_user->admin) {
		if ($config['authentication']['form_based'] == "database") {
			if ($_POST['password1'] !== $_POST['password2']) {
				$alert = new UserAlert;
				$alert->content = "Passwords do not match";
				$alert->class = 'danger';
				$active_user->add_alert($alert);
				redirect();
			}
			if ($_POST['password1'] !== '') {
				try {
					if ($user->is_password_complexity_ok($_POST['password1'])) {
						$user->password = password_hash($_POST['password1'], PASSWORD_DEFAULT);
					}
				} catch(PasswordComplexityException $e) {
					$alert = new UserAlert;
					$alert->content = $e->getMessage();
					$alert->class = 'danger';
					$active_user->add_alert($alert);
					redirect();
				}
			}
		}
		$user->name = $_POST['name'];
		$user->email = $_POST['email'];
		$user->active = isset($_POST['active']) ? 1 : 0;
		$user->admin = isset($_POST['admin']) ? 1 : 0;
		$user->update();
		$alert = new UserAlert;
		$alert->content = "User '{$user->uid}' updated.";
		$alert->class = 'success';
		$active_user->add_alert($alert);
		redirect();
	}
}
$changesets = $user->list_changesets();
$zones = $active_user->list_accessible_zones();
$visible_changesets = array();
foreach($changesets as $changeset) {
	if(isset($zones[$changeset->zone->pdns_id])) {
		$visible_changesets[] = $changeset;
	}
}
if(count($visible_changesets) == 0 && !$active_user->admin) {
	require('views/error404.php');
	die;
}

$content = new PageSection('user');
$content->set('user', $user);
$content->set('changesets', $visible_changesets);

$page = new PageSection('base');
$page->set('title', $user->name);
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());

echo $page->generate();
