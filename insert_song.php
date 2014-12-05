<?php

include 'init.php';

if (isset($_POST['sent']))
{
	$sql = <<<SQL
INSERT INTO song (
	id_book,
	page,
	title,
	lowest_note,
	highest_note
) VALUES (
	'{$_POST['book']}',
	'{$_POST['page']}',
	'{$_POST['title']}',
	'{$_POST['lowest_note']}',
	'{$_POST['highest_note']}'
)
SQL;

	mysql_query($sql);
	$id_song = mysql_insert_id();

	if (mysql_affected_rows())
	{
		foreach ($_POST['chords'] as $i=>$chord)
		{
			if (strlen($chord))
			{
				mysql_query("INSERT INTO song_chord (id_song, chord, position) VALUES ('$id_song', '$chord', '$i')");
			}
		}

		echo "Succesfully inserted!";
	}
	else
	{
		echo mysql_error();
	}
}

$current_book = isset($_POST['book']) ? $_POST['book'] : DEFAULT_BOOK;

include 'insert_song.view.php';