<?php

namespace NeoTransposer\Model;

/**
 * Represents a transposition of a song, with transposed chords, capo, etc.
 */
class PeopleCompatibleTransposition extends Transposition
{
	/**
	 * People's lowest and highest note after transposing.
	 * 
	 * @var NotesRange
	 * 
	 * @todo Esta propiedad no debería estar en Transposition? 
	 * 		 Todas las transposiciones causan un peopleRange. El tener 
	 * 		 peopleRange no depende del algoritmo usado sino de que la canción 
	 * 		 tenga people data o no. Si ese es el caso, la existencia de toda
	 * 		 esta clase ya no tiene sentido.
	 */
	public $peopleRange;

}
