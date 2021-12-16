<?php
/**
 * Created by PhpStorm.
 * User: asopc
 * Date: 10/22/2015
 * Time: 8:39 PM
 */

namespace Parser\AndroidManifestParser;


class BinaryAXMLReader {

    private $binaryContent = '';
    private $bytePointer = 0;

    public function __construct($binaryContent){
        $this->setContent($binaryContent);
    }

    public function setContent($binaryContent){
        $this->binaryContent = $binaryContent;
        $this->bytePointer = 0;
    }

    public function readInt32() {
        $intRead32B = unpack('V', substr($this->binaryContent, $this->bytePointer, 4));
        $this->bytePointer += 4;
        return array_shift($intRead32B);
    }

    public function readInt32Array($byteNumber) {
        if ($byteNumber <= 0) return null;
        $readArrayInt32B = unpack('V*', substr($this->binaryContent, $this->bytePointer, ($byteNumber*4)));
        if (count($readArrayInt32B) != $byteNumber) return null;
        $this->bytePointer += ($byteNumber*4);
        return $readArrayInt32B;
    }

    public function readInt16() {
        $intRead16B = unpack('v', substr($this->binaryContent, $this->bytePointer, 2));
        $this->bytePointer += 2;
        return array_shift($intRead16B);
    }

    public function readInt16At($bytePosition) {
        $intRead16B = unpack('v', substr($this->binaryContent, $bytePosition, 2));
        return array_shift($intRead16B);
    }

    public function readInt8() {
        $intRead8B = unpack('C', substr($this->binaryContent, $this->bytePointer, 1));
        $this->bytePointer += 2;
        return array_shift($intRead8B);
    }

    public function getCurrentPointerIndex(){
        return $this->bytePointer;
    }

    public function getContentLength(){
        return strlen($this->binaryContent);
    }

    public function canReadBytes($byteNumber){
        if (empty($this->binaryContent)) return false;
        $byteNumber = abs($byteNumber);
        $byteNumber += $this->bytePointer;
        if ($byteNumber>$this->getContentLength()) return false;
        return true;
    }

    public function moveTo($bytePosition){
        $this->bytePointer = $bytePosition;
    }

    public function trimTilCurrentPosition(){
        $this->binaryContent = substr($this->binaryContent, $this->bytePointer);
        $this->bytePointer = 0;
    }

    public function getStringTable($segBase, $offsetLists) {
        $arrStringTable = array();
        foreach ($offsetLists as $offset) {
            $offset += $segBase;
            $length = $this->readInt16At($offset);
            $offset += 2;
            $mask = ($length >> 0x8) & 0xFF;
            $length = $length & 0xFF;
            if ($length == $mask) {
                if (($offset + $length) > $this->getContentLength()) return null;
                $arrStringTable[] = substr($this->binaryContent, $offset, $length);
            }
            else {
                if (($offset + $length * 2) > $this->getContentLength()) return null;
                $strPiece = substr($this->binaryContent, $offset, $length * 2);
                $arrStringTable[] = $strPiece;
            }
        }
        return $arrStringTable;
    }
}
