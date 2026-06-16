<?php

namespace Modules\CommunityEngine\Enums;

enum PostStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Reported = 'reported';
}
