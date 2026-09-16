<?php

declare(strict_types=1);

namespace Pure\Core;

enum SlotKind
{
    case Text;
    case Attr;
    case Raw;
    case Sub;
    case Each;
    case If;
    case EachAny;
}
