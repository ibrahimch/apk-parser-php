<?php
/**
 * Created by PhpStorm.
 * User: asopc
 * Date: 10/22/2015
 * Time: 8:07 PM
 */

namespace Parser\AndroidManifestParser;

class AndroidManifestParser {
    private static $RADIX_MULTS = [0.00390625, 3.051758E-005, 1.192093E-007, 4.656613E-010];
    private static $DIMENSION_UNITS = ["px", "dip", "sp", "pt", "in", "mm", "", ""];
    private static $FRACTION_UNITS = ["%", "%p", "", "", "", "", "", ""];
    private static $ANDROID_MANIFEST_NAME = 'AndroidManifest.xml';
    private $binaryXmlController;
    private $stringCount = 0;
    private $styleCount = 0;
    private $stringTable = array();
    private $styleTable = array();
    private $resourceIDs = array();
    private $namespace = array();
    private $currentNamespace = null;
    private $rootManifest = null;
    private $currentBundleInfo;
    private $isValid = false;

    public function __construct($apkFile){
        $this->currentBundleInfo = new BundleInfo(null);
        $this->binaryXmlController = new BinaryAXMLReader(null);
        $this->setApkFile($apkFile);
    }

    public function setApkFile($apkFile){
        $zipFile = new \ZipArchive();
        $fileResource = null;
        try{
            $zipFile->open($apkFile);
            $fileResource = $zipFile->getStream(self::$ANDROID_MANIFEST_NAME);
        }
        catch(\Exception $e){
        }
        if (empty($fileResource)) return false;
        $fileContent = '';
        while(!feof($fileResource)){
            $fileContent .= fread($fileResource, 2048);
        }
        $zipFile->close();
        if (empty($fileContent)) return false;
        $this->binaryXmlController = new BinaryAXMLReader($fileContent);
        $this->rootManifest = $this->parseManifest(ResChunkConstants::AXML_FILE);
        $this->currentBundleInfo = new BundleInfo($this);
        $this->isValid = !empty($this->rootManifest);
        return $this->isValid;
    }

    private function parseManifest($requiredType = 0){
        $typeChunk = $this->binaryXmlController->readInt32();
        if (($requiredType>0) && ($typeChunk != $requiredType)) return null;
        $totalSizeData = $this->binaryXmlController->readInt32();
        if (($totalSizeData < ResChunkConstants::MIN_CHUNK_SIZE) ||
            ($totalSizeData > $this->binaryXmlController->getContentLength())) return null;
        $remainDataLen = $this->binaryXmlController->getContentLength() - $totalSizeData;

        $properties = false;
        switch ($typeChunk) {
            case ResChunkConstants::AXML_FILE:
                $properties = array();
                $properties['lineNumber']   = 0;
                $properties['xmlTag']       = '<?xml version="1.0" encoding="utf-8"?>';
                break;
            case ResChunkConstants::STRING_BLOCK:
                $this->stringCount = $this->binaryXmlController->readInt32();
                $this->styleCount = $this->binaryXmlController->readInt32();
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $stringStart = $this->binaryXmlController->readInt32();
                $styleStart = $this->binaryXmlController->readInt32();
                $stringLists = $this->binaryXmlController->readInt32Array($this->stringCount);
                $styleLists = $this->binaryXmlController->readInt32Array($this->styleCount);
                $this->stringTable = ($this->stringCount > 0) ? $this->binaryXmlController->getStringTable($stringStart, $stringLists) : [];
                if (!is_array($this->stringTable)) return null;
                $this->styleTable = ($this->styleCount > 0)? $this->binaryXmlController->getStringTable($styleStart, $styleLists) : [];
                if (!is_array($this->stringTable)) return null;
                $this->binaryXmlController->moveTo($totalSizeData);
                break;
            case ResChunkConstants::RESOURCEIDS:
                $resCount = $totalSizeData / 4 - 2;
                $this->resourceIDs = $this->binaryXmlController->readInt32Array($resCount);
                break;
            case ResChunkConstants::START_NAMESPACE:
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $prefix = $this->binaryXmlController->readInt32();
                $uri = $this->binaryXmlController->readInt32();
                if (empty($this->currentNamespace)) {
                    $this->currentNamespace = array();
                    $this->namespace[] = &$this->currentNamespace;
                }
                $this->currentNamespace[$uri] = $prefix;
                break;
            case ResChunkConstants::END_NAMESPACE:
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $prefix = $this->binaryXmlController->readInt32();
                $uri = $this->binaryXmlController->readInt32();
                if (empty($this->currentNamespace)) return null;
                unset($this->currentNamespace[$uri]);
                break;
            case ResChunkConstants::START_TAG:
                $lineNumber = $this->binaryXmlController->readInt32();
                $flag = $this->binaryXmlController->readInt32(); // just for skip
                $arrAttributes = array();
                $properties = array();
                $properties['lineNumber']    = $lineNumber;
                $properties['namespace']     = $this->getNamespaceName($this->binaryXmlController->readInt32());
                $properties['name']          = $this->lookupStringTable($this->binaryXmlController->readInt32());
                $properties['namespaceName'] = $properties['namespace'] . $properties['name'];
                $properties['flag']          = $this->binaryXmlController->readInt32();
                $properties['count']         = $this->binaryXmlController->readInt16();
                $properties['id']            = $this->binaryXmlController->readInt16() - 1;
                $properties['class']         = $this->binaryXmlController->readInt16() - 1;
                $properties['style']         = $this->binaryXmlController->readInt16() - 1;
                $properties['attributes']    = &$arrAttributes;
                for ($index = 0; $index < $properties['count']; $index++) {
                    $attr = array();
                    $attr['namespace']     = $this->getNamespaceName($this->binaryXmlController->readInt32());
                    $attr['name']          = $this->lookupStringTable($this->binaryXmlController->readInt32());
                    $attr['namespaceName'] = $attr['namespace'] . $attr['name'];
                    $attr['string']        = $this->binaryXmlController->readInt32();
                    $attr['type']          = $this->binaryXmlController->readInt32() >> 24;
                    $attr['data']          = $this->binaryXmlController->readInt32();
                    $arrAttributes[]       = $attr;
                }
                // TAG string handling
                $xmlTag = "<{$properties['namespaceName']}";
                foreach ($this->currentNamespace as $uri => $prefix) {
                    $uri = $this->lookupStringTable($uri);
                    $prefix = $this->lookupStringTable($prefix);
                    $xmlTag .= " xmlns:$prefix=\"$uri\"";
                }
                foreach ($properties['attributes'] as $attr) {
                    $attrValue = $this->lookupAttributeValues($attr);
                    $xmlTag .= " {$attr['namespaceName']}=\"$attrValue\"";
                }
                $xmlTag .= '>';
                $properties['xmlTag'] = $xmlTag;
                unset($this->currentNamespace);
                $this->currentNamespace = array();
                $this->namespace[] = &$this->currentNamespace;
                $remainDataLen = -1;
                break;
            case ResChunkConstants::END_TAG:
                $lineNumber = $this->binaryXmlController->readInt32();
                $flag = $this->binaryXmlController->readInt32();  // just for skip
                $properties = array();
                $properties['lineNumber']   = $lineNumber;
                $properties['namespace']    = $this->getNamespaceName($this->binaryXmlController->readInt32());
                $properties['name']         = $this->lookupStringTable($this->binaryXmlController->readInt32());
                $properties['namespaceName']= $properties['namespace'] . $properties['name'];
                $properties['xmlTag']       = "</{$properties['namespaceName']}>";
                if (count($this->namespace) > 1) {
                    array_pop($this->namespace);
                    unset($this->currentNamespace);
                    $this->currentNamespace = array_pop($this->namespace);
                    $this->namespace[] = &$this->currentNamespace;
                }
                break;
            case ResChunkConstants::TEXT:
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $properties = array();
                $properties['tag'] = $this->lookupStringTable($this->binaryXmlController->readInt32());
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                $flag = $this->binaryXmlController->readInt32(); // just for skipping
                break;
            default:
                return null;
                break;
        }

        $this->binaryXmlController->trimTilCurrentPosition();
        $arrChildren = array();
        while ($this->binaryXmlController->getContentLength() > $remainDataLen) {
            $child = $this->parseManifest();
            if ($properties && $child) $arrChildren[] = $child;
            if ($remainDataLen == -1 && $child['typeChunk'] == ResChunkConstants::END_TAG) {
                $remainDataLen = $this->binaryXmlController->getContentLength();
                break;
            }
        }
        if ($this->binaryXmlController->getContentLength() != $remainDataLen) return null;
        if ($properties) {
            $properties['typeChunk'] = $typeChunk;
            $properties['totalSize'] = $totalSizeData;
            $properties['children']  = $arrChildren;
            return $properties;
        }
        return false;
    }

    private function getNamespaceName($uri) {
        for ($index = 0; $index < count($this->namespace); $index++) {
            $namespace = $this->namespace[$index];
            if (isset($namespace[$uri])) {
                $namespaceName = $this->lookupStringTable($namespace[$uri]);
                if (!empty($namespaceName)) $namespaceName .= ':';
                return $namespaceName;
            }
        }
        return null;
    }

    private function lookupStringTable($idx) {
        if (($idx > -1) && ($idx < $this->stringCount))
            return $this->stringTable[$idx];
        return null;
    }

    private function lookupAttributeValues($attributeName) {
        $type = &$attributeName['type'];
        $data = &$attributeName['data'];
        switch ($type) {
            case ResDataType::TYPE_STRING:
                return $this->lookupStringTable($attributeName['string']);
            case ResDataType::TYPE_ATTRIBUTE:
                $prefixAndroid = ($data >> 24 == 1) ? 'android:' : '';
                return sprintf('?%s%08X', $prefixAndroid, $data);
            case ResDataType::TYPE_REFERENCE:
                $prefixAndroid = ($data >> 24 == 1) ? 'android:' : '';
                return sprintf('@%s%08X', $prefixAndroid, $data);
            case ResDataType::TYPE_INT_HEX:
                return sprintf('0x%08X', $data);
            case ResDataType::TYPE_INT_BOOLEAN:
                return ($data != 0 ? 'true' : 'false');
            case ResDataType::TYPE_INT_COLOR_ARGB8:
            case ResDataType::TYPE_INT_COLOR_RGB8:
            case ResDataType::TYPE_INT_COLOR_ARGB4:
            case ResDataType::TYPE_INT_COLOR_RGB4:
                return sprintf('#%08X', $data);
            case ResDataType::TYPE_DIMENSION:
                $resultDimension = (float)($data & 0xFFFFFF00) * self::$RADIX_MULTS[ ($data >> 4) & 3 ];
                return $resultDimension . self::$DIMENSION_UNITS[ $data & ResDataType::UNIT_MASK ];
            case ResDataType::TYPE_FRACTION:
                $resultFraction = (float)($data & 0xFFFFFF00) * self::$RADIX_MULTS[ ($data >> 4) & 3 ];
                return $resultFraction . self::$FRACTION_UNITS[ $data & ResDataType::UNIT_MASK ];
            case ResDataType::TYPE_FLOAT:
                $float = ($data & ((1 << 23) - 1)) + (1 << 23) * ($data >> 31 | 1);
                $exp = ($data >> 23 & 0xFF) - 127;
                return $float * pow( 2, $exp - 23);
        }
        if ($type >= ResDataType::TYPE_INT_DEC && $type < ResDataType::TYPE_INT_COLOR_ARGB8) {
            return (string)$data;
        }
        return sprintf('<0x%X, type 0x%02X>', $data, $type);
    }

    private function recursiveAttributeValue($parentRoot, $tagNames, $attributeName){
        if (empty($this->rootManifest)) return null;
        $arrNamespaces = explode('/', $tagNames);
        $namespace = array_shift($arrNamespaces);
        $indexItem = 0;
        if (preg_match('/([^\[]+)\[([0-9]+)\]$/', $namespace, $explodedItems)) {
            $namespace = $explodedItems[1];
            $indexItem = $explodedItems[2];
        }
        $tagNames = join('/', $arrNamespaces);
        foreach($parentRoot as $child){
            if (($child['typeChunk'] == ResChunkConstants::START_TAG) && ($child['namespaceName'] == $namespace)) {
                if ($indexItem == 0){
                    if (empty($tagNames)){
                        if (isset($child['attributes'])) {
                            foreach ($child['attributes'] as $attribute) {
                                if ($attribute['namespaceName'] == $attributeName)
                                    return $this->lookupAttributeValues($attribute);
                            }
                        }
                        return null;
                    }
                    return $this->recursiveAttributeValue($child['children'], $tagNames, $attributeName);
                }
                else{
                    $indexItem--;
                }
            }
        }
        return null;
    }

    public function getAttributeValue($tagNames, $attributeName){

        return $this->recursiveAttributeValue($this->rootManifest['children'], $tagNames, $attributeName);
    }

    public function getAndroidManifestXML(){
            return $this->recursiveAndroidManifestXML();
    }

    private function recursiveAndroidManifestXML($child=null, $indentCount=0){
        if ($child['typeChunk'] == ResChunkConstants::END_TAG) {
            $indentCount -=4;
        }
        $strXML = str_pad(" ", $indentCount);
        if (empty($child)) $child = $this->rootManifest;
        if (isset($child['xmlTag'])) $strXML .= $child['xmlTag'];
        if ($child['typeChunk'] == ResChunkConstants::AXML_FILE) {
            $strXML.=PHP_EOL;
        }
        if ($child['typeChunk'] == ResChunkConstants::START_TAG){
            if ($child['children'][0]['typeChunk'] == ResChunkConstants::END_TAG){
                $strXML = substr($strXML, 0, count($strXML)-2).'/>'.PHP_EOL;
                return $strXML;
            }
            $strXML .= PHP_EOL;
            $indentCount += 4;
        }
        if ($child['typeChunk'] == ResChunkConstants::END_TAG) {
            $strXML.=PHP_EOL;
        }
        foreach ($child['children'] as $children) {
            $strXML .= $this->recursiveAndroidManifestXML($children, $indentCount);
        }
        return $strXML;
    }

    public function getBundleInfo(){
        return $this->currentBundleInfo;
    }

    public function isLoadedFile(){
        return $this->isValid;
    }

    public function __toString(){
        return $this->getAndroidManifestXML();
    }
}
