<?php

namespace App\Enums;

enum QuizType: string
{
    case Likert = 'likert';
    case Mbti = 'mbti';
    case Mixed = 'mixed';
    case Custom = 'custom';
}
