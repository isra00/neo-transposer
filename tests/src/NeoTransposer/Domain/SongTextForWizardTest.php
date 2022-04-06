<?php

namespace NeoTransposer\Tests\Domain;

use NeoTransposer\Domain\SongTextForWizard;
use PHPUnit\Framework\TestCase;

class SongTextForWizardTest extends TestCase
{
    /**
     * @dataProvider providerText
     */
    public function testGetHtmlTextWithChords($text, $chords, $expectedOutput)
    {
        $sut = new SongTextForWizard($text);
        $this->assertEquals($expectedOutput, $sut->getHtmlTextWithChords($chords));
    }

    public function providerText(): array
    {
        return [
            [
                <<<SONG
%0                 %1
YAHVEH, TÚ ERES MI DIOS,

             %0
YO TE ENSALZARÉ.
SONG
                ,
                ['<span class="chord-sans">Mi-</span>', '<span class="chord-sans">Si7</span>'],
                <<<HTML
<span class="chord-sans">Mi-</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="chord-sans">Si7</span><br>
YAHVEH,&nbsp;TÚ&nbsp;ERES&nbsp;MI&nbsp;DIOS,<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="chord-sans">Mi-</span><br>
YO&nbsp;TE&nbsp;ENSALZARÉ.
HTML
            ],
            [
                <<<SONG
%0                  %1     %2
SI EL SEÑOR NO CONSTRUYE LA CASA,

   %3                        %0
EN VANO SE CANSAN LOS CONSTRUCTORES
SONG
                ,
                ['<span class="chord-sans">Do</span>', '<span class="chord-sans">La-</span>', '<span class="chord-sans">Fa</span>', '<span class="chord-sans">Sol</span>'],
                <<<HTML
<span class="chord-sans">Do</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="chord-sans">La-</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="chord-sans">Fa</span><br>
SI&nbsp;EL&nbsp;SEÑOR&nbsp;NO&nbsp;CONSTRUYE&nbsp;LA&nbsp;CASA,<br>
<br>
&nbsp;&nbsp;&nbsp;<span class="chord-sans">Sol</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="chord-sans">Do</span><br>
EN&nbsp;VANO&nbsp;SE&nbsp;CANSAN&nbsp;LOS&nbsp;CONSTRUCTORES
HTML
            ]
        ];
    }
}
