<?php
/**
 * Created by PhpStorm.
 * User: asopc
 * Date: 10/22/2015
 * Time: 8:10 PM
 */

namespace Parser\AndroidManifestParser;


final class ResChunkConstants {
    const AXML_FILE       = 0x00080003;
    const STRING_BLOCK    = 0x001C0001;
    const RESOURCEIDS     = 0x00080180;
    const START_NAMESPACE = 0x00100100;
    const END_NAMESPACE   = 0x00100101;
    const START_TAG       = 0x00100102;
    const END_TAG         = 0x00100103;
    const TEXT            = 0x00100104;

    const MIN_CHUNK_SIZE = 8;
}
