<?php
/**
 * Created by PhpStorm.
 * User: asopc
 * Date: 10/23/2015
 * Time: 5:04 PM
 */

namespace Parser\AndroidManifestParser;


class BundleInfo {
    private $applicationName = '';
    private $isGame = false;
    private $appVersionCode = 0;
    private $appVersionName = '';
    private $packageName = '';
    private $platformBuildVersionCode = 0;
    private $platformBuildVersionName = '';
    private $usesPermission = array();
    private $newPermissions = array();
    private $activities = array();
    private $receivers = array();
    private $providers = array();
    private $services = array();
    private $minSdkVersion = 0;
    private $targetSdkVersion = 0;
    private $maxSdkVersion = 0;
    private $mainActivities = array();

    public function __construct($androidManifestParser){
        if($androidManifestParser instanceof AndroidManifestParser){
            $this->applicationName = $androidManifestParser->getAttributeValue('manifest/application', 'android:name');
            $this->isGame = $androidManifestParser->getAttributeValue('manifest/application', 'android:isGame');
            $this->appVersionCode = (int)$androidManifestParser->getAttributeValue('manifest', 'android:versionCode');
            $this->appVersionName = $androidManifestParser->getAttributeValue('manifest', 'android:versionName');
            $this->packageName = $androidManifestParser->getAttributeValue('manifest', 'package');
            $this->platformBuildVersionCode = (int)$androidManifestParser->getAttributeValue('manifest', 'platformBuildVersionCode');
            $this->platformBuildVersionName = $androidManifestParser->getAttributeValue('manifest', 'platformBuildVersionName');
            $this->minSdkVersion = (int)$androidManifestParser->getAttributeValue('manifest/uses-sdk', 'android:minSdkVersion');
            $this->targetSdkVersion = (int)$androidManifestParser->getAttributeValue('manifest/uses-sdk', 'android:targetSdkVersion');
            $this->maxSdkVersion = (int)$androidManifestParser->getAttributeValue('manifest/uses-sdk', 'android:maxSdkVersion');
            for($index=0; true; $index++){
                $activity = $androidManifestParser->getAttributeValue("manifest/application/activity[$index]", 'android:name');
                if (empty($activity)) break;
                $this->activities[] = $activity;
            }
            for($index=0; true; $index++){
                $receiver = $androidManifestParser->getAttributeValue("manifest/application/receiver[$index]", 'android:name');
                if (empty($receiver)) break;
                $this->receivers[] = $receiver;
            }
            for($index=0; true; $index++){
                $provider = $androidManifestParser->getAttributeValue("manifest/application/provider[$index]", 'android:name');
                if (empty($provider)) break;
                $this->providers[] = $provider;
            }
            for($index=0; true; $index++){
                $service = $androidManifestParser->getAttributeValue("manifest/application/service[$index]", 'android:name');
                if (empty($service)) break;
                $this->services[] = $service;
            }
            for($index=0; true; $index++){
                $permission = $androidManifestParser->getAttributeValue("manifest/uses-permission[$index]", 'android:name');
                if (empty($permission)) break;
                $this->usesPermission[] = $permission;
            }
            for($index=0; true; $index++){
                $permission = $androidManifestParser->getAttributeValue("manifest/permission[$index]", 'android:name');
                if (empty($permission)) break;
                $this->newPermissions[] = $permission;
            }
            for($index=0; true; $index++){
                $activity = $androidManifestParser->getAttributeValue("manifest/application/activity[$index]", 'android:name');
                if (empty($activity)) break;
                $action = $androidManifestParser->getAttributeValue("manifest/application/activity[$index]/intent-filter/action", 'android:name');
                $category = $androidManifestParser->getAttributeValue("manifest/application/activity[$index]/intent-filter/category", 'android:name');
                if (empty($action) && empty($category)) continue;
                if (($action == 'android.intent.action.MAIN') && ($category=='android.intent.category.LAUNCHER'))
                    $this->mainActivities[] = $androidManifestParser->getAttributeValue("manifest/application/activity[$index]", 'android:name');
            }
        }

    }

    public function getPackageName(){
        return $this->packageName;
    }

    public function isGame(){
        return (boolean)$this->isGame;
    }

    public function getApplicationName(){
        return $this->applicationName;
    }

    public function getApplicationVersionCode(){
        return $this->appVersionCode;
    }

    public function getApplicationVersionName(){
        return $this->appVersionName;
    }

    public function getPlatformBuildVersionCode(){
        return $this->platformBuildVersionCode;
    }

    public function getPlatformBuildVersionName(){
        return $this->platformBuildVersionName;
    }

    public function getUsesPermission(){
        return $this->usesPermission;
    }

    public function getNewPermission(){
        return $this->newPermissions;
    }

    public function getMainActivities(){
        return $this->mainActivities;
    }

    public function getAllActivities(){
        return $this->activities;
    }

    public function getAllRecievers(){
        return $this->receivers;
    }

    public function getAllProviders(){
        return $this->providers;
    }

    public function getAllServices(){
        return $this->services;
    }

    public function getMinSdkVersion(){
        return $this->minSdkVersion;
    }

    public function getMaxSdkVersion(){
        return $this->maxSdkVersion;
    }

    public function getTargetSdkVersion(){
        return $this->targetSdkVersion;
    }


}
