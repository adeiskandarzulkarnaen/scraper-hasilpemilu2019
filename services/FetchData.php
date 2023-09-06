<?php

namespace app\services;

use Buzz\Client\MultiCurl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class FetchData extends Service
{
    public function processProv($prov)
    {
        echo "\n" . __FUNCTION__ . "\n";
        $kab_list = $this->getWilayah([$prov]);

        foreach ($kab_list as $id => $kab) {
            if ($this->start['kab_id'] and $id < $this->start['kab_id']) continue;
            if ($this->end['kab_id'] and $id > $this->end['kab_id']) break;
            $kab['id'] = $id;
            $this->processKab($kab, $prov);
        }
    }

    protected function processKab($kab, $prov)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $kec_list = $this->getWilayah([$prov, $kab]);

        foreach ($kec_list as $id => $kec) {
            if ($this->start['kec_id'] and $id < $this->start['kec_id']) continue;
            if ($this->end['kec_id'] and $id > $this->end['kec_id']) break;
            $kec['id'] = $id;
            $this->processKec($kec, $prov, $kab);
        }
    }

    protected function processKec($kec, $prov, $kab)
    {
        $id = "$prov[id]/$kab[id]/$kec[id]";
        echo "\n" . __FUNCTION__ ." $id\n";
        $desa_list = $this->getWilayah([$prov, $kab, $kec]);

        foreach ($desa_list as $id => $desa) {
            if ($this->start['desa_id'] and $id < $this->start['desa_id']) continue;
            if ($this->end['desa_id'] and $id > $this->end['desa_id']) break;
            $desa['id'] = $id;
            $this->processDesa($desa, $prov, $kab, $kec);
        }
    }

    protected function processDesa($desa, $prov, $kab, $kec)
    {
        $id = "$prov[id]/$kab[id]/$kec[id]/$desa[id]";
        echo "\n" . __FUNCTION__ ." $id\n";
        $tps_list = $this->getWilayah([$prov, $kab, $kec, $desa]);

        foreach ($tps_list as $id => $tps) {
            $tps['id'] = $id;
            $this->processTps($tps, $prov, $kab, $kec, $desa);
        }
    }

    protected function processTps($tps, $prov, $kab, $kec, $desa)
    {
        $tps_id = "$prov[id]/$kab[id]/$kec[id]/$desa[id]/$tps[id]";
        echo __FUNCTION__.' '.$tps_id.PHP_EOL;
        $file = __DIR__ . "/../res/w/$prov[nama]/$kab[nama]/$kec[nama]/$desa[nama]/{$this->type}/$tps[nama].json";

        if (is_file($file) && !$this->overwrite) {
            return;
        }


        $data = $this->fetchData(BASE . "/hhcw/{$this->type}/$prov[id]/$kab[id]/$kec[id]/$desa[id]/$tps[id].json");
        FileHelper::createDirectory(dirname($file));
        file_put_contents($file, Json::encode($data));
    }

    public function getWilayah($prefix = [])
    {
        $prefix_id = $prefix_type = null;
        foreach ($prefix as $wilayah) {
            $prefix_id .= '/' . $wilayah['id'];
            $prefix_type .= '/' . $wilayah['nama'];
        }

        $file = __DIR__ . "/../res/w$prefix_type/index.json";

        echo PHP_EOL . __FUNCTION__ . PHP_EOL;
        echo '$file = ' . $file . PHP_EOL;
        echo '$prefix_id = '. $prefix_id . PHP_EOL;

        if (is_file($file)) {
            $list = json_decode(file_get_contents($file), true);
            echo  "Get from cache\n";
        } else {
            $list = $this->fetchData(BASE . "/wilayah$prefix_id.json");
            FileHelper::createDirectory(dirname($file));
            file_put_contents($file, Json::encode($list));
        }

        return $list;
    }

    protected function fetchData($url)
    {
        echo PHP_EOL . __FUNCTION__ . " " . $url . PHP_EOL;

        sleep(1);
        $request = new Request('GET', $url);
        $client = new MultiCurl(new Psr17Factory(), ['allow_redirects' => true]);
        $response = $client->sendRequest($request, ['timeout' => 10]);
        $stream = $response->getBody();
        $list = json_decode($stream->getContents(), true);
        ksort($list);
        return $list;
    }
}
