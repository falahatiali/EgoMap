<?php

namespace App\Enums;

enum QuestionType: string
{
    case Likert = 'likert';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Boolean = 'boolean';
    case Text = 'text';
    case Slider = 'slider';
}
