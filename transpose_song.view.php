<?php include 'header.view.php' ?>

<?php function printTransposition($transposition, $original_chords) { ?>
<table class="transposition">
		<thead>
			<th colspan="3">
				<!--<?php echo $transposition->score ?>-->
				<?php echo '<strong>' . $transposition->chords[0] . '</strong>' . ($transposition->capo ? ' with capo ' . $transposition->capo : ' (no capo)') ?>
			</th>
		</thead>
		<tbody>
		<?php if ($transposition->getAsBook()) : ?>
			<tr><td>(same chords as in the book)</td></tr>
		<?php else : ?>
		<?php foreach ($original_chords as $i=>$chord) : ?>
			<tr>
				<td><?php echo $chord ?></td>
				<td class="center">&rarr;</td>
				<td><?php echo $transposition->chords[$i] ?></td>
			</tr>
		<?php endforeach ?>
		<?php endif ?>
		</tbody>
	</table>
<?php } ?>

<h1 class="song-title"><small class="page_number"><?php echo $song_details['page'] . "</small> " . $song_details['title'] ?></h1>

<div class="your-voice">
	<em>Your voice:</em>
	<?php echo $your_voice ?>
	<a href="wizard.php" class="small-button">Change</a>
</div>

<h4>These two transpositions match your voice (they are equivalent):</h4>
<div class="transpositions-list ovhid">
<?php foreach ($transpositions as $i=>$transposition) : ?>
	<?php printTransposition($transposition, $original_chords) ?>
<?php endforeach ?>
	</div>

<?php if (isset($not_equivalents[0])) : ?>
<h4>This other transposition is a bit <?php echo ($not_equivalents[0]->deviationFromPerfect > 0) ? 'higher' : 'lower' ?>, but it has easier chords and may also fit your voice:</h4>
<div class="transpositions-list ovhid">
	<?php printTransposition($not_equivalents[0], $original_chords) ?>
</div>
<?php endif ?>

<div class="voicechart-container">
	<table class="voicechart">
	<?php foreach ($voice_chart as $voice) : ?>
		<tr class="<?php echo $voice['css'] ?>">
			<th><?php echo $voice['caption'] ?></tb>
			<?php echo str_repeat('<th>&nbsp;</th>', $voice['offset']) ?>
			<td class="note"><?php echo $voice['lowest'] ?></td>
			<?php echo str_repeat('<td>██</td>', $voice['length']) ?>
			<td class="note"><?php echo $voice['highest'] ?></td>
		</tr>
	<?php endforeach ?>
	</table>
</div>
<?php include 'foot.view.php' ?>
