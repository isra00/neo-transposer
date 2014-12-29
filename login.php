<?php

namespace NeoTransposer;

include 'init.php';

if (isset($_POST['sent']))
{
	$form_is_valid = false;

	$regexp = <<<REG
[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?
REG;

	if (preg_match("/$regexp/i", $_POST['email']))
	{
		$form_is_valid = true;
	}

	if ($form_is_valid)
	{
		if (!$_SESSION['user'] = User::getUserFromDb($_POST['email']))
		{
			$_SESSION['user'] = new User($_POST['email']);
			$_SESSION['user']->persist();
		}

		if ($redirect = $_SESSION['user']->isRedirectionNeeded())
		{
			header("Location: $redirect.php");
			die;
		}
		else
		{
			header("Location: index.php?book=" . $_SESSION['user']->id_book);
			die;
		}
	}
}
else
{
	session_destroy();
}

if (!isset($_POST['sent']) || !$form_is_valid)
{
	$tpl_vars = array();

	if (isset($form_is_valid))
	{
		$tpl_vars['form_is_valid'] = $form_is_valid;
		$tpl_vars['post'] = array('email' => $_POST['email']);
	}

	echo $twig->render('login.tpl', $tpl_vars);
}