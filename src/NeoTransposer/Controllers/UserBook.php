<?php

namespace NeoTransposer\Controllers;

class UserBook
{
	public function get(\NeoTransposer\NeoApp $app)
	{
		return $app->render('user_book.tpl', array(
			'page_title' => $app->trans('Select language'),
		));
	}
}