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

global $config;

if ($config['authentication']['form_based'] !== "database") {
        require('views/error403.php');
        die;
}




if($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($_POST['password1'] !== $_POST['password2']) {
		$alert = new UserAlert;
		$alert->content = "Passwords do not match";
		$alert->class = 'danger';
		$active_user->add_alert($alert);
		redirect();
	}
	if ($_POST['password1'] !== '') {
		try {
			if ($active_user->is_password_complexity_ok($_POST['password1'])) {
				$active_user->password = password_hash($_POST['password1'], PASSWORD_DEFAULT);
			}
		} catch(PasswordComplexityException $e) {
			$alert = new UserAlert;
			$alert->content = $e->getMessage();
			$alert->class = 'danger';
			$active_user->add_alert($alert);
			redirect();
		}
	}

	$active_user->update();
	$alert = new UserAlert;
	$alert->content = "Password changed";
	$alert->class = 'success';
	$active_user->add_alert($alert);

	require("views/zones.php");
	die;
}


$content = new PageSection('changepass');

$page = new PageSection('base');
$page->set('title', 'Change password');
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());


echo $page->generate();

