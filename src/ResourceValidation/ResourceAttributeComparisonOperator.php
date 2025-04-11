<?php

namespace Seier\Resting\ResourceValidation;

enum ResourceAttributeComparisonOperator
{
    case GreaterThan;
    case GreaterThanOrEqual;
    case LessThan;
    case LessThanOrEqual;
    case Equal;
}
