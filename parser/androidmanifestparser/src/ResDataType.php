<?php
/**
 * Created by PhpStorm.
 * User: asopc
 * Date: 10/22/2015
 * Time: 8:15 PM
 */

namespace Parser\AndroidManifestParser;


final class ResDataType {

    const TYPE_NULL       = 0;
    const TYPE_REFERENCE  = 1;

    const TYPE_ATTRIBUTE       = 2;
    const TYPE_STRING          = 3;
    const TYPE_FLOAT           = 4;
    const TYPE_DIMENSION       = 5;
    const TYPE_FRACTION        = 6;
    const TYPE_INT_DEC         = 16;
    const TYPE_INT_HEX         = 17;
    const TYPE_INT_BOOLEAN     = 18;
    const TYPE_INT_COLOR_ARGB8 = 28;
    const TYPE_INT_COLOR_RGB8  = 29;
    const TYPE_INT_COLOR_ARGB4 = 30;
    const TYPE_INT_COLOR_RGB4  = 31;
    const UNIT_MASK            = 15;
}
