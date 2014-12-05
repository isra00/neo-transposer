<?php include 'header.view.php' ?>

<h1 class="song-title"><small class="page_number"><?php echo $song_details['page'] . "</small> " . $song_details['title'] ?></h1>

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

<div class="transpositions-list ovhid">
<?php foreach ($transpositions as $i=>$transposition) : ?>
	<h4><?php echo ($i == 0) ? 'The best transposition matching your voice:' : 'Equivalent transposition using capo (it\'s exactly the same for your voice)' ?></h4>
	<table class="transposition" style="float: none">
		<thead>
			<th colspan="3">
				<!-- <?php echo $transposition->score ?> -->
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
<?php endforeach ?>
</div>

<!--<h4>These other transpositions are different, but they may fit your voice and have easy chords:</h4>

<p>[not implemented yet]</p>-->

<?php include 'foot.view.php' ?>
