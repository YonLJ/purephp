<?php

declare(strict_types=1);

namespace Pure\Core;

enum SlotKind
{
    case Value;
    case Raw;
    case Child;
    case Each;
    case If;
    case EachKind;
}
