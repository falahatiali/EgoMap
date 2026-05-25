<?php

namespace App\Enums\Pdf;

enum PdfSectionType: string
{
    case Hero = 'hero';
    case SectionTitle = 'section_title';
    case Paragraph = 'paragraph';
    case DimensionBars = 'dimension_bars';
    case ChipGrid = 'chip_grid';
    case NoteGrid = 'note_grid';
    case TwoColumn = 'two_column';
    case PillList = 'pill_list';
    case Callout = 'callout';
    case Divider = 'divider';
    case StatRow = 'stat_row';
    case Overview = 'overview';
    case HighlightCard = 'highlight_card';
    case TypeLetters = 'type_letters';
}
