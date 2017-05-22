<?php

use \NeoTransposer\Model\NotesNotation;

class NotesNotationTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var NotesNotation
	 */
	protected $nn;

	public function setUp()
	{
		$this->nn = new NotesNotation;
	}

	public function testGetNotation()
    {
        $this->assertEquals('Do', $this->nn->getNotation('C', 'latin'));
    }

    public function testGetVoiceRangeAsString()
    {
    	$transMock = $this->getMockBuilder(\Symfony\Component\Translation\Translator::class)
						  ->disableOriginalConstructor()
						  ->setMethods(['trans'])
						  ->getMock();

		$transMock->expects($this->once())
				  ->method('trans')
				  ->with($this->equalTo('oct'))
				  ->will($this->returnValue('octave'));

		$this->assertEquals(
			'A &rarr; A +1 octave',
			$this->nn->getVoiceRangeAsString($transMock, 'american', 'A1', 'A2')
		);
    }
}
