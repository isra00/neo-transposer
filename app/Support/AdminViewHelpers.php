<?php

namespace App\Support;

final class AdminViewHelpers
{
    public static function feedbackGraph(?int $yes, ?int $no, int $width = 100): string
    {
        $yes = (int) $yes;
        $no = (int) $no;
        $total = $yes + $no;
        if ($total === 0) {
            return '';
        }

        $html = '<div class="feedback-graph">';
        if ($yes) {
            $w = min(96, (int) round(($yes / $total) * $width));
            $html .= '<span class="yes" style="width: ' . $w . 'px"><span>' . $yes . '</span></span>';
        }
        if ($no) {
            $w = min(96, (int) round(($no / $total) * $width));
            $html .= ' <span class="no" style="width: ' . $w . 'px"><span>' . $no . '</span></span>';
        }

        return $html . '</div>';
    }
}
