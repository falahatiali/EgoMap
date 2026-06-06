<?php

namespace Modules\AetherEngine\Enums;

enum PrimaryGoal: string
{
    case FatLoss = 'fat_loss';
    case MuscleGain = 'muscle_gain';
    case Recomposition = 'recomposition';
    case Strength = 'strength';
    case Endurance = 'endurance';
    case Aesthetics = 'aesthetics';
    case Health = 'health';
}
