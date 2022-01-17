<?php

namespace MicroweberPackages\ComposerClient;

use MicroweberPackages\App\Models\SystemLicenses;
use MicroweberPackages\ComposerClient\Traits\FileDownloader;

class Client
{
    use FileDownloader;

    public $licenses = [];
    public $packageServers = [
        'https://market.microweberapi.com/packages/microweber/packages.json',
    ];

    public function setLicense($license)
    {
        $this->licenses[] = $license;
    }

    public function search($filter = array())
    {
        $packages = [];
        foreach ($this->packageServers as $package) {

            $package = $this->getPackageFile($package);

            if (empty($filter)) {
                return $package;
            }

            foreach ($package as $name => $versions) {

                if (!is_array($versions)) {
                    continue;
                };

                if (isset($filter['require_name']) && ($filter['require_name'] == $name)) {

                    $versions['latest'] = end($versions);

                    foreach ($versions as $version => $versionData) {
                        if ($filter['require_version'] == $version) {
                            $packages[] = $versionData;
                            break;
                        }
                    }
                }

            }
        }

        return $packages;
    }

    public function getPackageFile($packageUrl)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $packageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode(json_encode($this->licenses))
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return ["error" => "cURL Error #:" . $err];
        } else {
            $getPackages = json_decode($response, true);
            if (isset($getPackages['packages']) && is_array($getPackages['packages'])) {
                return $getPackages['packages'];
            }
            return [];
        }
    }
    
}
